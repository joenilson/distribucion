<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('almacenes.php');
require_model('articulo.php');
require_model('familia.php');
require_model('cliente.php');
require_model('grupo_clientes.php');
require_model('distribucion_clientes.php');
require_model('distribucion_transportes.php');
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
require_model('distribucion_faltantes.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_rutas.php');
require_model('facturas_cliente.php');
require_model('facturas_proveedor.php');
require_model('forma_pago.php');
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
require_once 'plugins/distribucion/vendors/tcpdf/tcpdf.php';
/**
 * Description of dashboard_distribucion
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class dashboard_distribucion extends fs_controller {
    public $almacenes;
    public $familias;
    public $articulos;
    public $articulos_top_cantidad;
    public $articulos_top_valor;
    public $cantidad_supervisores;
    public $cantidad_vendedores;
    public $cantidad_unidades;
    public $cantidad_articulos;
    public $cantidad_clientes;
    public $clientes_activos;
    public $clientes_inactivos;
    public $clientes_nuevos;
    public $clientes_debaja;
    public $clientes_grupo;
    public $clientes_visitados;
    public $clientes_por_visitar;
    public $clientes_top;
    public $clientes_rutas;
    public $grupos_clientes;
    public $grupos_clientes_lista;
    public $facturascli;
    public $facturaspro;
    public $transportes;
    public $conductores;
    public $unidades;
    public $distribucion_clientes;
    public $organizacion;
    public $supervisores;
    public $vendedores;
    public $mesa_trabajo;
    public $rutas;
    public $faltantes;
    public $f_desde;
    public $f_hasta;
    public $codalmacen;
    public $total;
    public $total_ingresos;
    public $total_egresos;
    public $total_cobros;
    public $total_pendientes_cobro;
    public $fileNameXLS;
    public $fileNamePDF;
    public $pathNameXLS;
    public $pathNamePDF;
    public $documentosDir;
    public $distribucionDir;
    public $publicPath;
    public $pdf;
    public $procesado;
    public $lista_familia;
    public $lista_fecha;
    public $lista_referencia;
    public $suma_familia;
    public $suma_fecha;
    public $suma_referencia;
    public $resumen_familia;
    public function __construct() {
        parent::__construct(__CLASS__,'Dashboard Distribución', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core() {
        $this->shared_extensions();
        $this->almacenes = new almacen();
        $this->articulos = new articulo();
        $this->familias = new familia();
        $this->facturascli = new factura_cliente();
        $this->facturaspro = new factura_proveedor();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->organizacion = new distribucion_organizacion();
        $this->faltantes = new distribucion_faltantes();
        $this->unidades = new distribucion_unidades();
        $this->rutas = new distribucion_rutas();
        $this->fp = new forma_pago();
        $this->grupos_clientes = new grupo_clientes();
        $this->resultados_formas_pago = false;
        $this->procesado = false;
        //Si el usuario es admin puede ver todos los recibos, pero sino, solo los de su almacén designado
        if(!$this->user->admin){
            $this->agente = new agente();
            $cod = $this->agente->get($this->user->codagente);
            $user_almacen = $this->almacenes->get($cod->codalmacen);
            $this->user->codalmacen = $user_almacen->codalmacen;
            $this->user->nombrealmacen = $user_almacen->nombre;
        }
        
        //Creamos o validamos las carpetas para grabar los informes de caja
        $this->fileName = '';
        $basepath = dirname(dirname(dirname(__DIR__)));
        $this->documentosDir = $basepath . DIRECTORY_SEPARATOR . FS_MYDOCS . 'documentos';
        $this->distribucionDir = $this->documentosDir . DIRECTORY_SEPARATOR . "distribucion";
        $this->publicPath = FS_PATH . FS_MYDOCS . 'documentos' . DIRECTORY_SEPARATOR . 'distribucion';

        if (!is_dir($this->documentosDir)) {
            mkdir($this->documentosDir);
        }

        if (!is_dir($this->distribucionDir)) {
            mkdir($this->distribucionDir);
        }

        $f_desde = filter_input(INPUT_POST, 'f_desde');
        $this->f_desde = ($f_desde)?$f_desde:\date('01-m-Y');
        $f_hasta = filter_input(INPUT_POST, 'f_hasta');
        $this->f_hasta = ($f_hasta)?$f_hasta:\date('d-m-Y');
        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $this->codalmacen = (isset($this->user->codalmacen))?$this->user->codalmacen:$codalmacen;
        $accion = filter_input(INPUT_POST, 'accion');
        if($accion){
            switch ($accion){
                case "buscar":
                    $this->generar_resumen();
                    $this->cobertura_articulos();
                    $this->top_clientes();
                    $this->top_articulos();
                    $this->procesado = TRUE;
                break;
            }
        }
    }
    
    public function cobertura_articulos(){
        $this->lista_familia = array();
        $this->lista_fecha = array();
        $this->lista_referencia = array();
        $this->suma_familia = array();
        $this->suma_fecha = array();
        $this->suma_referencia = array();
        $diffdesde = new \DateTime(\date('d-m-Y',strtotime($this->f_desde)));
        $diffhasta = new \DateTime(\date('d-m-Y',strtotime($this->f_hasta)));
        $lista = array();
        //Buscamos los productos en la fecha dada y los agrupamos por familia
        $sql = "SELECT a.codfamilia,lf.referencia,fc.fecha,sum(lf.cantidad) as cantidad ".
                "FROM facturascli AS fc, articulos AS a, familias AS f, lineasfacturascli as lf ". 
                "WHERE fecha between ".$this->empresa->var2str($this->f_desde)." AND ".$this->empresa->var2str($this->f_hasta)." ".
                "AND fc.codalmacen = ".$this->empresa->var2str($this->codalmacen)." AND pvptotal != 0 AND fc.idfactura = lf.idfactura ".
                "AND lf.referencia = a.referencia AND f.codfamilia = a.codfamilia and fc.anulada = FALSE ".
                "GROUP BY a.codfamilia,lf.referencia,fc.fecha LIMIT 11".
               ";";
        //$this->new_advice($sql);
        $data = $this->db->select($sql);
        if($data){
            foreach($data as $d){
                $arbol = array();
                if(!empty($d['codfamilia'])){
                    $this->arbol_familia($d['codfamilia'], $arbol);
                }                
                $item = new stdClass();
                $item->codfamilia = $d['codfamilia'];
                $item->arbol = $arbol;
                $item->referencia = $d['referencia'];
                $item->fecha = $d['fecha'];
                $item->cantidad = $d['cantidad'];
                /*
                $this->lista_familia[$d['codfamilia']][$d['referencia']][] = $item;
                $this->lista_fecha[$d['fecha']][$d['codfamilia']][] = $item;
                $this->lista_referencia[$d['referencia']][] = $item;
                $this->suma_familia[$d['codfamilia']] += $d['cantidad'];
                $this->suma_fecha[$d['fecha']][$d['codfamilia']] += $d['cantidad'];
                $this->suma_referencia[$d['fecha']][$d['referencia']] += $d['cantidad'];
                 * 
                 */
            }
        }
        //Generamos el listado de familias
        $this->arbol_familia(FALSE,$this->resumen_familia,'DESC');
    }
    
    
    /**
     * Extraemos el arbol de familias para armar un reporte
     * @param type $codfamilia string
     * @param type $resultado array
     * @return array
     */
    public function arbol_familia($codfamilia,&$resultado = array(),$orden = 'ASC'){
        $data = ($orden == 'ASC')?$this->familias->get($codfamilia):$this->familias->hijas($codfamilia);
        if ($data) {
            $resultado[] = array('codigo' => $data->codfamilia, 'descripcion' => $data->descripcion);
            if ($data->madre) {
                $resultado[] = array('codigo' => $data->codfamilia, 'descripcion' => $data->descripcion);
                $this->arbol_familia($data->madre, $resultado, $orden);
            }else{
                return $resultado;
            }
        }
    }
    
    public function generar_resumen(){
        $diffdesde = new \DateTime(\date('d-m-Y',strtotime($this->f_desde)));
        $diffhasta = new \DateTime(\date('d-m-Y',strtotime($this->f_hasta)));
        //Obtenemos la información de los supervisores
        $this->supervisores = $this->organizacion->activos_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'SUPERVISOR');
        $this->cantidad_supervisores = count($this->supervisores);
        $this->mesa_trabajo = array();
        foreach($this->supervisores as $sup){
            $vendedores = $this->organizacion->get_asignados($this->empresa->id, $sup->codagente);
            $this->mesa_trabajo[$sup->codagente] = $vendedores;
        }
        //Obtenemos la información de los vendedores
        $this->vendedores = $this->organizacion->activos_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'VENDEDOR');
        $this->cantidad_vendedores = count($this->vendedores);
        $unidades = $this->unidades->activos_almacen($this->empresa->id, $this->codalmacen);
        $this->cantidad_unidades = count($unidades);
        $articulos = $this->articulos->all();
        $this->cantidad_articulos = 0;
        foreach($articulos as $art){
            if($art->sevende AND !$art->nostock){
                $this->cantidad_articulos++;
            }
        }
        //Obtenemos la información de los Clientes
        $this->clientes_activos = 0;
        $this->clientes_inactivos = 0;
        $this->clientes_nuevos = 0;
        $this->clientes_debaja = 0;
        $this->clientes_visitados = 0;
        $this->clientes_por_visitar = 0;
        $this->clientes_grupo = array();
        $clientes_almacen = $this->distribucion_clientes->clientes_almacen($this->empresa->id,$this->codalmacen);
        foreach($clientes_almacen as $cli){
            $dtalta = new \DateTime(\date('d-m-Y',strtotime($cli->fechaalta))); 
            $dtbaja = new \DateTime(\date('d-m-Y',strtotime($cli->fechabaja)));
            if($cli->debaja and $dtbaja>=$diffdesde AND $dtbaja<=$diffhasta){
                $this->clientes_debaja++;
            }elseif($cli->debaja and $dtbaja<$diffdesde){
                $this->clientes_inactivos++;
            }elseif(!$cli->debaja and $dtalta>=$diffdesde AND $dtalta<=$diffhasta){
                $this->clientes_nuevos++;
            }elseif(!$cli->debaja and $dtalta<$diffdesde){
                $this->clientes_activos++;  
        }
        
            //Buscamos la atención de clientes del rango de fechas
            $sql = "SELECT COUNT(*) as count FROM facturascli WHERE codcliente = ".$this->empresa->var2str($cli->codcliente)." and fecha between '".\date('d-m-Y',strtotime($this->f_desde))."' AND '".\date('d-m-Y',strtotime($this->f_hasta))."' AND anulada = FALSE;";
            $data = $this->db->select($sql);
            if(!empty($data[0]['count'])){
                $this->clientes_visitados++;
            }elseif(!$cli->debaja){
                $this->clientes_por_visitar++;
            }
                    
            //Guardamos la cantidad total de clientes
            $this->cantidad_clientes++;
            
            //Agrupamos los clientes en sus grupos
            if($cli->codgrupo){
                if(!isset($this->clientes_grupo[$cli->codgrupo])){
                    $this->clientes_grupo[$cli->codgrupo]=0;
                }
                $this->clientes_grupo[$cli->codgrupo]++;
            }
        }
        
        //Guardamos la cantidad de clientes por cada grupo
        $this->grupos_clientes_lista = array();
        foreach($this->grupos_clientes->all() as $gc){
            $gc->clientes = (isset($this->clientes_grupo[$gc->codgrupo]))?$this->clientes_grupo[$gc->codgrupo]:0;
            $this->grupos_clientes_lista[] = $gc;
        }
        
        
        //Generamos la efectividad de visitas
        //La efectividad es el porcentaje de clientes visitados entre la cantidad de clientes totales
        $this->clientes_rutas = array();
        $this->clientes_rutas['total'] = array();
        $this->clientes_rutas['atendidos'] = array();
        $this->clientes_rutas['no_atendidos'] = array();
        $this->clientes_rutas['efectividad'] = array();
        $this->clientes_rutas['efectividad_vendedor'] = array();
        $this->clientes_rutas['total_rutas'] = array();
        $this->clientes_rutas['total_clientes'] = array();
        $this->clientes_rutas['total_atendidos'] = array();
        $this->clientes_rutas['total_no_atendidos'] = array();
        //La mesa es el grupo de vendedores que tiene un supervisor
        $this->clientes_rutas['mesa_rutas'] = array();
        $this->clientes_rutas['mesa_vendedores'] = array();
        $this->clientes_rutas['mesa_clientes'] = array();
        $this->clientes_rutas['mesa_atendidos'] = array();
        $this->clientes_rutas['mesa_no_atendidos'] = array();
        $this->clientes_rutas['mesa_efectividad'] = array();
        //Generamos los totales por supervisor
        foreach($this->supervisores as $supervisor){
            $this->clientes_rutas['mesa_rutas'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_vendedores'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_clientes'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_atendidos'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_no_atendidos'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_efectividad'][$supervisor->codagente] = 0;
        } 
        foreach($this->vendedores as $vendedor){
            $rutasagente = $this->rutas->all_rutasporagente($this->empresa->id, $this->codalmacen, $vendedor->codagente);
            $this->clientes_rutas['total_rutas'][$vendedor->codagente] = count($rutasagente);
            $this->clientes_rutas['total_clientes'][$vendedor->codagente] = 0;
            $this->clientes_rutas['total_atendidos'][$vendedor->codagente] = 0;
            $this->clientes_rutas['total_no_atendidos'][$vendedor->codagente] = 0;
            $this->clientes_rutas['mesa'][$vendedor->codsupervisor] = 0;
            if($rutasagente){
                foreach($rutasagente as $ruta){
                    $clientes_ruta = $this->rutas->cantidad_asignados($this->empresa->id, $this->codalmacen, $ruta->ruta);
                    $this->clientes_rutas['total'][$ruta->ruta] = $clientes_ruta;
                    $this->clientes_rutas['total_clientes'][$vendedor->codagente] += $clientes_ruta;
                    if(!isset($this->clientes_rutas['atendidos'][$ruta->ruta])){
                        $this->clientes_rutas['atendidos'][$ruta->ruta] = 0;
                    }
                    if(!isset($this->clientes_rutas['no_atendidos'][$ruta->ruta])){
                        $this->clientes_rutas['no_atendidos'][$ruta->ruta] = $clientes_ruta;
                    }
                    
                    //A corregir , se debe generar una consulta join entre facturascli y distribucion_clientes
                    $sql = "SELECT T1.ruta,count(DISTINCT T2.codcliente) as clientes_visitados ".
                        "FROM distribucion_clientes AS T1 ".
                        "LEFT JOIN facturascli as T2 ".
                        "ON T1.codcliente = T2.codcliente ".
                        "WHERE fecha between '".\date('d-m-Y',strtotime($this->f_desde))."' AND '".\date('d-m-Y',strtotime($this->f_hasta))."' ".
                        "AND T1.codalmacen = ".$this->empresa->var2str($this->codalmacen)." and ruta = ".$this->empresa->var2str($ruta->ruta)." and anulada = FALSE ".
                        "GROUP by T1.ruta;";
                    $data = $this->db->select($sql);
                    if($data){
                        $this->clientes_rutas['atendidos'][$ruta->ruta] = $data[0]['clientes_visitados'];
                        $this->clientes_rutas['no_atendidos'][$ruta->ruta] -= $data[0]['clientes_visitados'];
                    }
                    $efectividad = round(($this->clientes_rutas['atendidos'][$ruta->ruta]/$clientes_ruta)*100,0);
                    $this->clientes_rutas['efectividad'][$ruta->ruta] = $efectividad;
                    $efectividad_color = ($efectividad<=30)?'danger':'success';
                    $efectividad_color = ($efectividad>30 AND $efectividad<65)?'warning':$efectividad_color;
                    $this->clientes_rutas['efectividad_color'][$ruta->ruta] = $efectividad_color;
                    $this->clientes_rutas['total_atendidos'][$vendedor->codagente] += $this->clientes_rutas['atendidos'][$ruta->ruta];
                    $this->clientes_rutas['total_no_atendidos'][$vendedor->codagente] += $this->clientes_rutas['no_atendidos'][$ruta->ruta];
                }
            }
            if($this->clientes_rutas['total_clientes'][$vendedor->codagente]){
                $efectividad_vendedor = round(($this->clientes_rutas['total_atendidos'][$vendedor->codagente]/$this->clientes_rutas['total_clientes'][$vendedor->codagente])*100,0);
            }else{
                $efectividad_vendedor = 0;
            }
            $this->clientes_rutas['efectividad_vendedor'][$vendedor->codagente] = $efectividad_vendedor;
            $efectividad_color = ($efectividad_vendedor<=30)?'danger':'success';
            $efectividad_color = ($efectividad_vendedor>30 AND $efectividad_vendedor<65)?'warning':$efectividad_color;
            $this->clientes_rutas['efectividad_vendedor_color'][$vendedor->codagente] = $efectividad_color;
            $this->clientes_rutas['mesa_rutas'][$vendedor->codsupervisor] += $this->clientes_rutas['total_rutas'][$vendedor->codagente];
            $this->clientes_rutas['mesa_vendedores'][$vendedor->codsupervisor]++;
            $this->clientes_rutas['mesa_clientes'][$vendedor->codsupervisor] += $this->clientes_rutas['total_clientes'][$vendedor->codagente];
            $this->clientes_rutas['mesa_atendidos'][$vendedor->codsupervisor] += $this->clientes_rutas['total_atendidos'][$vendedor->codagente];
            $this->clientes_rutas['mesa_no_atendidos'][$vendedor->codsupervisor] += $this->clientes_rutas['total_no_atendidos'][$vendedor->codagente];
        }
        
        //Generamos la estadistica por supervisor
        foreach($this->supervisores as $supervisor){
            $efectividad_supervisor = round(($this->clientes_rutas['mesa_atendidos'][$supervisor->codagente]/$this->clientes_rutas['mesa_clientes'][$supervisor->codagente])*100,0);
            $this->clientes_rutas['mesa_efectividad'][$supervisor->codagente] = $efectividad_supervisor;
            $efectividad_color = ($efectividad_supervisor<=30)?'danger':'success';
            $efectividad_color = ($efectividad_supervisor>30 AND $efectividad_supervisor<65)?'warning':$efectividad_color;
            $this->clientes_rutas['efectividad_mesa_color'][$supervisor->codagente] = $efectividad_color;
        }
    }
    
    //Generamos el listado de los 10 clientes que más compran
    public function top_clientes($cantidad=10,$excluidos=false){
        $clientes = ($excluidos)?" AND codcliente NOT IN (".$excluidos.")":"";
        $sql = "SELECT codcliente,nombrecliente,sum(total) as suma FROM facturascli where anulada = false $clientes and idfacturarect is null and fecha between '".\date('Y-m-d',strtotime($this->f_desde))."' AND '".\date('Y-m-d',strtotime($this->f_hasta))."' GROUP BY codcliente,nombrecliente ORDER BY suma DESC LIMIT $cantidad;";
        $data = $this->db->select($sql);
        $this->clientes_top = array();
        $i=0;
        if($data){
            foreach($data as $d){
                $cliente_top = new stdClass();
                $cliente_top->codcliente = $d['codcliente'];
                $cliente_top->nombrecliente = $d['nombrecliente'];
                $cliente_top->totalventa = $d['suma'];
                $this->clientes_top[] = $cliente_top;
                $i++;
            }
        }
    }

    
    //Generamos el listado de los 10 productos mas vendidos
    public function top_articulos_oferta($cantidad=10,$excluidos=false){
        $this->articulos_oferta_top_cantidad = array();
        $this->articulos_oferta_top_valor = array();
        //Buscamos primero la suma por cantidad
        $referencias = ($excluidos)?" AND referencia NOT IN (".$excluidos.")":"";
        $sql1 = "select referencia, descripcion, sum(cantidad) as cantidad from lineasfacturascli ".
                "WHERE idfactura IN (select idfactura from facturascli ".
                "where fecha between '".\date('Y-m-d',strtotime($this->f_desde))."' and '".\date('Y-m-d',strtotime($this->f_hasta)).
                "' and anulada = FALSE) AND pvptotal = 0".
                " $referencias group by referencia, descripcion order by cantidad DESC limit $cantidad;";
        $data1 = $this->db->select($sql1);
        
        $i=0;
        if($data1){
            foreach($data1 as $d){
                $articulo_top = new stdClass();
                $articulo_top->referencia = $d['referencia'];
                $articulo_top->descripcion = $d['descripcion'];
                $articulo_top->totalventa = $d['cantidad'];
                $this->articulos_top_cantidad[] = $articulo_top;
                $i++;
            }
        }
    }    
    
    //Generamos el listado de los 10 productos mas vendidos
    public function top_articulos($cantidad=10,$excluidos=false){
        $this->articulos_top_cantidad = array();
        $this->articulos_top_valor = array();
        //Buscamos primero la suma por cantidad
        $referencias = ($excluidos)?" AND referencia NOT IN (".$excluidos.")":"";
        $sql1 = "select referencia, descripcion, sum(cantidad) as cantidad from lineasfacturascli ".
                "WHERE idfactura IN (select idfactura from facturascli ".
                "where fecha between '".\date('Y-m-d',strtotime($this->f_desde))."' and '".\date('Y-m-d',strtotime($this->f_hasta)).
                "' and anulada = FALSE) AND pvptotal != 0".
                " $referencias group by referencia, descripcion order by cantidad DESC limit $cantidad;";
        $data1 = $this->db->select($sql1);
        
        $i=0;
        if($data1){
            foreach($data1 as $d){
                $articulo_top = new stdClass();
                $articulo_top->referencia = $d['referencia'];
                $articulo_top->descripcion = $d['descripcion'];
                $articulo_top->totalventa = $d['cantidad'];
                $this->articulos_top_cantidad[] = $articulo_top;
                $i++;
            }
        }
        
        //Buscamos la suma por previo de venta total
        $sql2 = "select referencia, descripcion, sum(pvptotal) as total from lineasfacturascli ".
                "WHERE idfactura IN (select idfactura from facturascli where fecha between '".\date('Y-m-d',strtotime($this->f_desde))."' and '".\date('Y-m-d',strtotime($this->f_hasta))."' and anulada = FALSE) ".
                " $referencias group by referencia, descripcion order by total DESC limit $cantidad;";
        $data2 = $this->db->select($sql2);
        
        $ii=0;
        if($data2){
            foreach($data2 as $d){
                $articulo_top = new stdClass();
                $articulo_top->referencia = $d['referencia'];
                $articulo_top->descripcion = $d['descripcion'];
                $articulo_top->totalventa = $d['total'];
                $this->articulos_top_valor[] = $articulo_top;
                $ii++;
            }
        }
        
    }
    
    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => 'dashboard_distribucion_momentjs',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/moment-with-locales.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'dashboard_distribucion_chartjs',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/z/Chart.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
        );
        foreach ($extensiones as $ext) {
            $fsext = new fs_extension($ext);
            if (!$fsext->save()) {
                $this->new_error_msg('Error al guardar la extensión ' . $ext['name']);
            }
        }
    }
    
    /**
     * @url http://snippets.khromov.se/convert-comma-separated-values-to-array-in-php/
     * @param $string - Input string to convert to array
     * @param string $separator - Separator to separate by (default: ,)
     *
     * @return array
     */
    private function comma_separated_to_array($string, $separator = ',') {
        //Explode on comma
        $vals = explode($separator, $string);

        //Trim whitespace
        foreach ($vals as $key => $val) {
            $vals[$key] = trim($val);
        }
        //Return empty array if no items found
        //http://php.net/manual/en/function.explode.php#114273
        return array_diff($vals, array(""));
    }
}
