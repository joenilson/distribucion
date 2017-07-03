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
require_once 'plugins/distribucion/vendors/FacturaScripts/Seguridad/SeguridadUsuario.php';
use FacturaScripts\Seguridad\SeguridadUsuario;
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
        $seguridadUsuario = new SeguridadUsuario();
        $this->user = $seguridadUsuario->accesoAlmacenes($this->user);

        $f_desde = filter_input(INPUT_POST, 'f_desde');
        $this->f_desde = ($f_desde)?$f_desde:\date('01-m-Y');
        $f_hasta = filter_input(INPUT_POST, 'f_hasta');
        $this->f_hasta = ($f_hasta)?$f_hasta:\date('d-m-Y');
        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $this->codalmacen = ($this->user->codalmacen)?$this->user->codalmacen:$codalmacen;

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
                " AND f.codcliente = dc.codcliente ".
                " AND f.codalmacen = ".$this->empresa->var2str($this->codalmacen).
                " AND f.codagente = ".$this->empresa->var2str($linea->codagente).
                " AND f.idfactura = lf.idfactura ".
                " AND f.anulada = FALSE ".
                " AND lf.dtopor != 100 ".
                " AND f.fecha >= ".$this->empresa->var2str(\date('d-m-Y',strtotime($this->f_desde))).
                " AND f.fecha <= ".$this->empresa->var2str(\date('d-m-Y',strtotime($this->f_hasta))).
                " GROUP BY fecha ".
                " ORDER BY fecha ";
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
        $header_format = array("string","string","string","0","0","0.00","0");
        foreach($this->rango_fechas as $fecha){
            array_push($header_format,"@");
            array_push($header_format,"@");
            array_push($header_format,"@");
        }

        $this->estilo_cabecera = array('border'=>'left,right,top,bottom','font-style'=>'bold','halign'=>'center');
        $this->estilo_merged = array( array('halign'=>'left','valign'=>'center','font-style'=>'bold'),array('halign'=>'left','valign'=>'center','font-style'=>'bold'),array('halign'=>'center','font-style'=>'bold'),array('halign'=>'right','font-style'=>'bold'),array('halign'=>'right','font-style'=>'bold'),array('halign'=>'right','font-style'=>'bold'),array('halign'=>'right','font-style'=>'bold'));
        foreach($this->rango_fechas as $fecha){
            array_push($this->estilo_merged,array('halign'=>'right'));
            array_push($this->estilo_merged,array('halign'=>'right'));
            array_push($this->estilo_merged,array('halign'=>'right'));
        }
        $this->estilo_subtotal_merged = array( array('halign'=>'left','valign'=>'center','font-style'=>'bold'),array('halign'=>'left','valign'=>'center','font-style'=>'bold'),array('halign'=>'center','font-style'=>'bold'),array('halign'=>'right','font-style'=>'bold'),array('halign'=>'right','font-style'=>'bold'),array('halign'=>'right','font-style'=>'bold'),array('halign'=>'right','font-style'=>'bold'));
        foreach($this->rango_fechas as $fecha){
            array_push($this->estilo_subtotal_merged,array('halign'=>'right','font-style'=>'bold'));
            array_push($this->estilo_subtotal_merged,array('halign'=>'right','font-style'=>'bold'));
            array_push($this->estilo_subtotal_merged,array('halign'=>'right','font-style'=>'bold'));
        }
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

        foreach($this->rango_fechas as $fecha){
            $header[]=$fecha->format('d-m-Y');
            $header[]="";
            $header[]="";
        }

        $subheader= array();
        $subheader[] = "";
        $subheader[] = "";
        $subheader[] = "";
        $subheader[] = "";
        $subheader[] = "";
        $subheader[] = "";
        $subheader[] = "";
        foreach($this->rango_fechas as $fecha) {
            $subheader[]="Cantidad";
            $subheader[]="Importe";
            $subheader[]="Oferta";
        }

        $this->writer = new XLSXWriter();
        /**
         * Cabecera del archivo
         */
        $almacen0 = $this->almacenes->get($this->codalmacen);
        $this->writer->writeSheetHeader($almacen0->nombre, array(), true);
        $this->writer->writeSheetRow($almacen0->nombre, $header,$this->estilo_cabecera);
        //Hacemos un merge de filas
        for($x=0; $x<7;$x++){
            $this->writer->markMergedCell($almacen0->nombre, 0, $x, 1, $x);
        }
        //creamos un merge de las columnas
        $col = 7;
        foreach($this->rango_fechas as $fecha){
            $this->writer->markMergedCell($almacen0->nombre, 0, $col, 0, $col+2);
            $col = $col+3;
        }
        //Agregamos el subheader para las lineas debajo de fecha
        $this->writer->writeSheetRow($almacen0->nombre, $subheader,$this->estilo_cabecera);

        /**
         * Contenido del archivo
         */
        $col_ini_s = 2;
        foreach($this->supervisores as $sup){
            $contador_s = 1;
            $col_ini_v = 2;
            foreach($this->organizacion->get_asignados($this->empresa->id,$sup->codagente) as $vendedor){
                $contador_v = 1;
                foreach($this->rutas->all_rutasporagente($this->empresa->id, $vendedor->codalmacen, $vendedor->codagente) as $rutas){
                    $linea = array();
                    $linea[] = $sup->nombre;
                    $linea[]=$vendedor->nombre;
                    $linea[]=$rutas->ruta;
                    $linea[]=$this->clientes_rutas['total'][$rutas->ruta];
                    $linea[]=$this->ruta_cantidad[$rutas->ruta];
                    $linea[]=round($this->ruta_importe[$rutas->ruta],2);
                    $linea[]=$this->ruta_ofertas[$rutas->ruta];
                    foreach($this->rango_fechas as $fecha){
                        $linea[]=$this->lista_ruta[$rutas->ruta][$fecha->format('dmY')]['cantidad'];
                        $linea[]=round($this->lista_ruta[$rutas->ruta][$fecha->format('dmY')]['importe'],FS_NF0);
                        $linea[]=$this->lista_ofertas[$rutas->ruta][$fecha->format('dmY')]['cantidad'];
                    }
                    $this->writer->writeSheetRow($almacen0->nombre, $linea,$this->estilo_merged);
                    if($contador_s==1){
                        $col_fin = $this->clientes_rutas['mesa_rutas'][$sup->codagente]+$this->clientes_rutas['mesa_vendedores'][$sup->codagente]+2;
                        $this->writer->markMergedCell($almacen0->nombre, $col_ini_s, 0, $col_fin, 0);
                        $col_ini_s = $col_fin+1;
                    }
                    if($contador_v==1){
                        $col_fin = $col_ini_v+$this->clientes_rutas['total_rutas'][$vendedor->codagente]-1;
                        $this->writer->markMergedCell($almacen0->nombre, $col_ini_v, 1, $col_fin, 1);
                        $col_ini_v = $col_fin+2;
                    }
                    $contador_v++;
                    $contador_s++;
                }
                //Linea del total del vendedor
                $linea_subtotal = array();
                $linea_subtotal[]="";
                $linea_subtotal[]="";
                $linea_subtotal[]="Total de ".$vendedor->nombre;
                $linea_subtotal[]=$this->clientes_rutas['total_clientes'][$vendedor->codagente];
                $linea_subtotal[]=($this->vendedor_cantidad[$vendedor->codagente])?$this->vendedor_cantidad[$vendedor->codagente]:0;
                $linea_subtotal[]=($this->vendedor_importe[$vendedor->codagente])?round($this->vendedor_importe[$vendedor->codagente],FS_NF0):0;
                $linea_subtotal[]=($this->vendedor_ofertas[$vendedor->codagente])?$this->vendedor_ofertas[$vendedor->codagente]:0;
                foreach($this->rango_fechas as $fecha){
                    $linea_subtotal[]=($this->vendedor_total_cantidad[$vendedor->codagente][$fecha->format('dmY')])?$this->vendedor_total_cantidad[$vendedor->codagente][$fecha->format('dmY')]:0;
                    $linea_subtotal[]=($this->vendedor_total_importe[$vendedor->codagente][$fecha->format('dmY')])?round($this->vendedor_total_importe[$vendedor->codagente][$fecha->format('dmY')],FS_NF0):0;
                    $linea_subtotal[]=($this->vendedor_total_ofertas[$vendedor->codagente][$fecha->format('dmY')])?$this->vendedor_total_ofertas[$vendedor->codagente][$fecha->format('dmY')]:0;
                }
                $this->writer->writeSheetRow($almacen0->nombre, $linea_subtotal,$this->estilo_subtotal_merged);
            }
            //Linea del total del vendedor
            $linea_total = array();
            $linea_total[]="";
            $linea_total[]="Total de ".$sup->nombre;
            $linea_total[]="";
            $linea_total[]=$this->clientes_rutas['mesa_clientes'][$sup->codagente];
            $linea_total[]=$this->mesa_cantidad[$sup->codagente];
            $linea_total[]=round($this->mesa_importe[$sup->codagente],FS_NF0);
            $linea_total[]=$this->mesa_ofertas[$sup->codagente];
            foreach($this->rango_fechas as $fecha){
                $linea_total[]=$this->mesa_total_cantidad[$sup->codagente][$fecha->format('dmY')];
                $linea_total[]=round($this->mesa_total_importe[$sup->codagente][$fecha->format('dmY')],FS_NF0);
                $linea_total[]=$this->mesa_total_ofertas[$sup->codagente][$fecha->format('dmY')];
            }
            $this->writer->writeSheetRow($almacen0->nombre, $linea_total,$this->estilo_subtotal_merged);
        }

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
