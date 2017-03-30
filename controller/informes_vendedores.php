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
 * Description of informes_vendedores
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informes_vendedores extends fs_controller {
    public $almacenes;
    public $familias;
    public $articulos;
    public $clientes_rutas;
    public $distribucion_clientes;
    public $organizacion;
    public $supervisores;
    public $vendedores;
    public $mesa_trabajo;
    public $rutas;
    public $f_desde;
    public $f_hasta;
    public $rango_fechas;
    public $codalmacen;
    public $total;
    public $fileNameXLS;
    public $pathNameXLS;
    public $documentosDir;
    public $distribucionDir;
    public $publicPath;
    public $pdf;
    public $procesado;
    public $lista_ruta;
    public $lista_fecha;
    public $lista_ofertas;    
    public $ruta_cantidad;
    public $ruta_importe;
    public $ruta_ofertas;
    public $ofertas_fecha;
    public $vendedor_cantidad;
    public $vendedor_importe;
    public $vendedor_ofertas;
    public $mesa_cantidad;
    public $mesa_importe;
    public $mesa_ofertas;
    public $vendedor_total_cantidad;
    public $vendedor_total_importe;
    public $vendedor_total_ofertas;
    public $mesa_total_cantidad;
    public $mesa_total_importe;
    public $mesa_total_ofertas;
    
    public function __construct() {
        parent::__construct(__CLASS__, 'Vendedores', 'informes', FALSE, TRUE, FALSE);
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
        
        //Si el usuario es admin puede ver todos los recibos, pero sino, solo los de su almacén designado
        if(!$this->user->admin){
            $this->agente = new agente();
            $cod = $this->agente->get($this->user->codagente);
            $user_almacen = $this->almacenes->get($cod->codalmacen);
            $this->user->codalmacen = $user_almacen->codalmacen;
            $this->user->nombrealmacen = $user_almacen->nombre;
        }
        
        $f_desde = filter_input(INPUT_POST, 'f_desde');
        $this->f_desde = ($f_desde)?$f_desde:\date('01-m-Y');
        $f_hasta = filter_input(INPUT_POST, 'f_hasta');
        $this->f_hasta = ($f_hasta)?$f_hasta:\date('d-m-Y');
        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $this->codalmacen = (isset($this->user->codalmacen))?$this->user->codalmacen:$codalmacen;

        //Ragno de fechas según los datos enviados
        $desde = new DateTime($this->f_desde);
        $hasta_f = new DateTime($this->f_hasta);
        $hasta = $hasta_f->modify( '+1 day' ); 
        //intervalo de aumento es 1 día        
        $intervalo = new \DateInterval('P1D');
        $this->rango_fechas = new \DatePeriod($desde, $intervalo, $hasta);
        //Verificamos el intervalo
        //$this->new_advice($desde->diff($hasta)->days);
        $accion = filter_input(INPUT_POST, 'accion');
        if($accion){
            switch ($accion){
                case "buscar":
                    $this->generar_resumen();
                    //$this->top_clientes();
                    //$this->top_articulos();
                    $this->procesado = TRUE;
                break;
            }
        }
        
    }
    
    public function generar_resumen(){
        //Obtenemos la información de las rutas
        $this->ruta_cantidad = array();
        $this->ruta_ofertas = array();
        $this->ruta_importe = array();
        $this->lista_ofertas = array();
        $this->lista_ruta = array();
        $this->lista_fecha = array();
        $this->ofertas_fecha = array();
        $this->vendedor_cantidad = array();
        $this->vendedor_importe = array();
        $this->vendedor_ofertas = array();
        $this->mesa_cantidad = array();
        $this->mesa_importe = array();
        $this->mesa_ofertas = array();
        $this->vendedor_total_cantidad = array();
        $this->vendedor_total_importe = array();
        $this->vendedor_total_ofertas = array();
        $this->mesa_total_cantidad = array();
        $this->mesa_total_importe = array();
        $this->mesa_total_ofertas = array();
        
        //Obtenemos la información de los supervisores
        $this->supervisores = $this->organizacion->activos_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'SUPERVISOR');
        $this->cantidad_supervisores = count($this->supervisores);
        $this->mesa_trabajo = array();
        foreach($this->supervisores as $linea){
            $vendedores = $this->organizacion->get_asignados($this->empresa->id, $linea->codagente);
            //inicializamos variables de supervisor
            $this->mesa_cantidad[$linea->codagente] = 0;
            $this->mesa_importe[$linea->codagente] = 0;
            $this->mesa_ofertas[$linea->codagente] = 0;
            $this->vendedor_total_cantidad[$linea->codagente] = array();
            $this->vendedor_total_importe[$linea->codagente] = array();
            $this->vendedor_total_ofertas[$linea->codagente] = array();
            $this->mesa_trabajo[$linea->codagente] = $vendedores;
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
        
        $rutas_almacen = $this->rutas->all_rutasporalmacen($this->empresa->id,$this->codalmacen);
        foreach($rutas_almacen as $linea){
            //Inicializamos los colectores de informacion por ruta y fecha
            if(!isset($this->lista_ruta[$linea->ruta])){
                $this->lista_ruta[$linea->ruta] = array();
                $this->lista_ofertas[$linea->ruta] = array();
                $this->ruta_cantidad[$linea->ruta] = 0;
                $this->ruta_importe[$linea->ruta] = 0;
                $this->ruta_ofertas[$linea->ruta] = 0;                
            }
            
            if(!isset($this->vendedor_cantidad[$linea->codagente])){
                //Inicializamos variables de vendedores
                $this->vendedor_cantidad[$linea->codagente] = 0;
                $this->vendedor_importe[$linea->codagente] = 0;
                $this->vendedor_ofertas[$linea->codagente] = 0;
            }            
            
            foreach($this->rango_fechas as $fecha){
                $fecha_idx = $fecha->format('dmY');
                $this->lista_ruta[$linea->ruta][$fecha_idx] = array('cantidad'=>NULL,'importe'=>NULL);
                $this->lista_ofertas[$linea->ruta][$fecha_idx] = array('cantidad'=>NULL,'importe'=>NULL);
                $this->lista_fecha[$fecha_idx] = array('cantidad'=>NULL,'importe'=>NULL);
                $this->ofertas_fecha[$fecha_idx] = array('cantidad'=>NULL,'importe'=>NULL);
                
                if(!isset($this->mesa_total_cantidad[$linea->codsupervisor][$fecha_idx])){
                    $this->mesa_total_cantidad[$linea->codsupervisor][$fecha_idx] = 0;
                    $this->mesa_total_importe[$linea->codsupervisor][$fecha_idx] = 0;
                    $this->mesa_total_ofertas[$linea->codsupervisor][$fecha_idx] = 0;
                }
                
                if(!isset($this->vendedor_total_cantidad[$linea->codagente][$fecha_idx])){
                    $this->vendedor_total_cantidad[$linea->codagente][$fecha_idx] = 0;
                    $this->vendedor_total_importe[$linea->codagente][$fecha_idx] = 0;
                    $this->vendedor_total_ofertas[$linea->codagente][$fecha_idx] = 0;
                }
            }
            
            //sumamos las ventas efectivas sin bonificaciones
            $sql = "SELECT fecha,SUM(cantidad) as cantidad,sum(pvptotal) as importe FROM ".
                "facturascli as f, distribucion_clientes as dc, lineasfacturascli as lf ".
                " WHERE ".
                " dc.ruta = ".$this->empresa->var2str($linea->ruta).
                " AND f.codcliente = dc.codcliente AND f.codalmacen = ".$this->empresa->var2str($this->codalmacen).
                " AND f.idfactura = lf.idfactura ".
                " AND f.anulada = FALSE ".
                " AND lf.dtopor != 100 ".
                " AND f.fecha >= ".$this->empresa->var2str(\date('d-m-Y',strtotime($this->f_desde))).
                " AND f.fecha <= ".$this->empresa->var2str(\date('d-m-Y',strtotime($this->f_hasta))).
                " GROUP BY fecha ";
            //$this->new_advice($sql);
            $data = $this->db->select($sql);
            $fecha_cantidad = array();
            $fecha_importe = array();
            if($data){
                foreach($data as $d){
                    $fecha = \date('d-m-Y',strtotime($d['fecha']));
                    $fecha_idx = \date('dmY',strtotime($d['fecha']));
                    if(!isset($fecha_cantidad[$fecha_idx])){
                        $fecha_cantidad[$fecha_idx] = 0;
                        $fecha_importe[$fecha_idx] = 0;
                    }
                    $cantidad = floatval($d['cantidad']);
                    $importe = floatval($d['importe']);
                    $fecha_cantidad[$fecha_idx] += floatval($d['cantidad']);
                    $fecha_importe[$fecha_idx] += floatval($d['importe']);
                    $this->ruta_cantidad[$linea->ruta] += $cantidad;
                    $this->ruta_importe[$linea->ruta] += $importe;
                    $this->lista_ruta[$linea->ruta][$fecha_idx]=array('cantidad'=>$cantidad,'importe'=>$importe);
                    $this->lista_fecha[$fecha_idx]=array('cantidad'=>$fecha_cantidad[$fecha_idx],'importe'=>$fecha_importe[$fecha_idx]);
                    $this->vendedor_cantidad[$linea->codagente] += $cantidad;
                    $this->vendedor_importe[$linea->codagente] += $importe;
                    $this->mesa_cantidad[$linea->codsupervisor] += $cantidad;
                    $this->mesa_importe[$linea->codsupervisor] += $importe;
                    $this->vendedor_total_cantidad[$linea->codagente][$fecha_idx] += $cantidad;
                    $this->vendedor_total_importe[$linea->codagente][$fecha_idx] += $importe;
                    $this->mesa_total_cantidad[$linea->codsupervisor][$fecha_idx] += $cantidad;
                    $this->mesa_total_importe[$linea->codsupervisor][$fecha_idx] += $importe;
                }
            }
            
            //sumamos las bonificaciones
            $sql = "SELECT fecha,SUM(cantidad) as cantidad FROM ".
                "facturascli as f, distribucion_clientes as dc, lineasfacturascli as lf ".
                " WHERE ".
                " dc.ruta = ".$this->empresa->var2str($linea->ruta).
                " AND f.codcliente = dc.codcliente AND f.codalmacen = ".$this->empresa->var2str($this->codalmacen).
                " AND f.idfactura = lf.idfactura ".
                " AND f.anulada = FALSE ".
                " AND lf.dtopor = 100 ".
                " AND f.fecha >= ".$this->empresa->var2str(\date('d-m-Y',strtotime($this->f_desde))).
                " AND f.fecha <= ".$this->empresa->var2str(\date('d-m-Y',strtotime($this->f_hasta))).
                " GROUP BY fecha ";
            //$this->new_advice($sql);
            $data = $this->db->select($sql);
            $fecha_cantidad = array();
            if($data){
                foreach($data as $d){
                    $fecha = \date('d-m-Y',strtotime($d['fecha']));
                    $fecha_idx = \date('dmY',strtotime($d['fecha']));
                    if(!isset($fecha_cantidad[$fecha_idx])){
                        $fecha_cantidad[$fecha_idx] = 0;
                    }
                    $cantidad = floatval($d['cantidad']);
                    $fecha_cantidad[$fecha_idx] += floatval($d['cantidad']);
                    $this->ruta_ofertas[$linea->ruta] += $cantidad;
                    $this->lista_ofertas[$linea->ruta][$fecha_idx]=array('cantidad'=>$cantidad);
                    $this->ofertas_fecha[$fecha_idx]=array('cantidad'=>$fecha_cantidad[$fecha_idx]);
                    $this->vendedor_ofertas[$linea->codagente] += $cantidad;
                    $this->mesa_ofertas[$linea->codsupervisor] += $cantidad;
                    $this->vendedor_total_ofertas[$linea->codagente][$fecha_idx] += $cantidad;
                    $this->mesa_total_ofertas[$linea->codsupervisor][$fecha_idx] += $cantidad;
                }
            }
        }
        
        
        //Generamos la efectividad de visitas
        //La efectividad es el porcentaje de clientes visitados entre la cantidad de clientes totales
        $this->clientes_rutas = array();
        $this->clientes_rutas['total'] = array();
        $this->clientes_rutas['total_rutas'] = array();
        $this->clientes_rutas['total_clientes'] = array();
        //La mesa es el grupo de vendedores que tiene un supervisor
        $this->clientes_rutas['mesa_rutas'] = array();
        $this->clientes_rutas['mesa_vendedores'] = array();
        $this->clientes_rutas['mesa_clientes'] = array();
        //Generamos los totales por supervisor
        foreach($this->supervisores as $supervisor){
            $this->clientes_rutas['mesa_rutas'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_vendedores'][$supervisor->codagente] = 0;
            $this->clientes_rutas['mesa_clientes'][$supervisor->codagente] = 0;
        }
        foreach($this->vendedores as $vendedor){
            $rutasagente = $this->rutas->all_rutasporagente($this->empresa->id, $this->codalmacen, $vendedor->codagente);
            $this->clientes_rutas['total_rutas'][$vendedor->codagente] = count($rutasagente);
            $this->clientes_rutas['total_clientes'][$vendedor->codagente] = 0;
            $this->clientes_rutas['mesa'][$vendedor->codsupervisor] = 0;
            if($rutasagente){
                foreach($rutasagente as $ruta){
                    $clientes_ruta = $this->rutas->cantidad_asignados($this->empresa->id, $this->codalmacen, $ruta->ruta);
                    $this->clientes_rutas['total'][$ruta->ruta] = $clientes_ruta;
                    $this->clientes_rutas['total_clientes'][$vendedor->codagente] += $clientes_ruta;
                }
            }

            $this->clientes_rutas['mesa_rutas'][$vendedor->codsupervisor] += $this->clientes_rutas['total_rutas'][$vendedor->codagente];
            $this->clientes_rutas['mesa_vendedores'][$vendedor->codsupervisor]++;
            $this->clientes_rutas['mesa_clientes'][$vendedor->codsupervisor] += $this->clientes_rutas['total_clientes'][$vendedor->codagente];
        }
        $this->generar_excel();
    }
    
    public function generar_excel(){
        $this->pathNameXLS = $this->distribucionDir . DIRECTORY_SEPARATOR . 'Ventas_Vendedores' . "_" . $this->user->nick . ".xlsx";
        $this->fileNameXLS = $this->publicPath . DIRECTORY_SEPARATOR . 'Ventas_Vendedores' . "_" . $this->user->nick . ".xlsx";
        if (file_exists($this->fileNameXLS)) {
            unlink($this->fileNameXLS);
        }
        
        $this->estilo_cabecera = array('border'=>'left,right,top,bottom','font-style'=>'bold');
        $this->estilo_cuerpo = array( array('halign'=>'left'),array('halign'=>'right'),array('halign'=>'center'),array('halign'=>'none'));
        $this->estilo_pie = array('border'=>'left,right,top,bottom','font-style'=>'bold','color'=>'#FFFFFF','fill'=>'#000000');
        $header=array();
        $header[]="Supervisor";
        $header[]="Vendedor";
        $header[]="Ruta";
        $header[]="Clientes";
        $header[]="Qdad Venta";
        $header[]="Importe";
        $header[]="Qdad Oferta";
        //creamos un merge de las columnas
        $col = 7;
        foreach($this->rango_fechas as $fecha){
            $header[]=$fecha->format('d-m-Y');
        }
        
        $this->writer = new XLSXWriter();
        $almacen0 = $this->almacenes->get($this->codalmacen);
        $this->writer->writeSheetHeader($almacen0->nombre, array(), true);
        $this->writer->writeSheetRow($almacen0->nombre, $header,$this->estilo_cabecera);
        $this->writer->writeToFile($this->pathNameXLS);
        gc_collect_cycles();
    }
    
    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => '008_informes_vendedores_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script type="text/javascript" src="' . FS_PATH . 'plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            ),
            array(
                'name' => '009_informes_vendedores_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/distribucion/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
        );
        
        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
}
