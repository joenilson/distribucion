<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Affero General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */
require_model('factura_cliente');
require_model('linea_factura_cliente');
require_model('almacen');
require_model('agencia_transporte.php');
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
require_model('distribucion_ordenescarga.php');
require_model('distribucion_lineasordenescarga.php');
require_model('cliente.php');
require_model('articulo');
/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_ordencarga extends fs_controller {

    public $facturas_cliente;
    public $linea_factura_cliente;
    public $almacen;
    public $resultados;
    public $agencia_transporte;
    public $distrib_conductores;
    public $distrib_unidades;
    public $distrib_ordenescarga;
    public $distrib_lineasordenescarga;
    public $mostrar;
    public $order;
    public $cliente;
    public $total_resultados;
    public $total_resultados_txt;
    public $num_resultados;
    public $paginas;
    public $articulo;
    
    public function __construct() {
        parent::__construct(__CLASS__, '4 - Crear Orden de Carga', 'distribucion');
    }

    public function private_core() {
        /*
        **  Llamadas a models
        */
        $this->almacen = new almacen();
        $this->facturas_cliente = new factura_cliente();
        $this->linea_factura_cliente = new linea_factura_cliente();
        $this->agencia_transporte = new agencia_transporte();
        $this->distrib_conductores = new distribucion_conductores();
        $this->distrib_unidades = new distribucion_unidades();
        $this->distrib_ordenescarga = new distribucion_ordenescarga();
        $this->distrib_lineasordenescarga = new distribucion_lineasordenescarga();
        $this->articulo = new articulo;
        
        /*
                * Cargamos los plugins necesarios jss y css
                */
        $this->share_extensions();
        
        /*
                * Leemos las variables que nos manda el view
                */
        $type = \filter_input(INPUT_GET, 'type');
        $type_post = \filter_input(INPUT_POST, 'type');
        $buscar_fecha = \filter_input(INPUT_GET, 'buscar_fecha');
        $codalmacen = \filter_input(INPUT_GET, 'codalmacen');
        $codtrans = \filter_input(INPUT_GET, 'codtrans');
        $offset = \filter_input(INPUT_GET, 'offset');
        $mostrar = \filter_input(INPUT_GET, 'mostrar');
        $order = \filter_input(INPUT_GET, 'order');
        $cliente = \filter_input(INPUT_GET, 'codcliente');

        $this->mostrar = (isset($mostrar))?$mostrar:"todo";
        $this->order = (isset($order))?str_replace('_', ' ', $order):"fecha DESC";
        if(isset($cliente) AND !empty($cliente)){
            $cli0 = new cliente();
            $codcliente = $cli0->get($cliente);
        }
        $this->cliente = (isset($codcliente))?$codcliente:FALSE;
        
        if($type === 'buscar_facturas'){
            $this->buscar_facturas($buscar_fecha, $codalmacen, $offset);
        }elseif($type === 'select-unidad'){
            $this->lista_unidades($this->empresa->id,$codtrans,$codalmacen);
        }elseif($type === 'select-conductor'){
            $this->lista_conductores($this->empresa->id,$codtrans,$codalmacen);
        }elseif($type === 'crear-carga'){
            $dataInicialCarga['almacenorig'] = \filter_input(INPUT_GET, 'almacenorig');
            $dataInicialCarga['almacendest'] = \filter_input(INPUT_GET, 'almacendest');
            $dataInicialCarga['codunidad'] = \filter_input(INPUT_GET, 'codunidad');
            $dataInicialCarga['conductor'] = \filter_input(INPUT_GET, 'conductor');
            $dataInicialCarga['observaciones'] = \filter_input(INPUT_GET, 'observaciones');
            $dataInicialCarga['facturas'] = \filter_input(INPUT_GET, 'facturas');
            $this->crear_carga($dataInicialCarga, 'json');
        }elseif(isset($type_post) AND $type_post == 'guardar-carga'){
            $almacenorig = \filter_input(INPUT_POST, 'carga_almacenorig');
            $almacendest = \filter_input(INPUT_POST, 'carga_almacendest');
            $codtrans = \filter_input(INPUT_POST, 'carga_codtrans');
            $codunidad = \filter_input(INPUT_POST, 'carga_unidad');
            $conductor = \filter_input(INPUT_POST, 'carga_conductor');
            $fecha_reparto = \filter_input(INPUT_POST, 'carga_fechareparto');
            $carga_facturas = \filter_input(INPUT_POST, 'carga_facturas');
            $resultados_facturas = $this->crear_carga(['facturas'=>$carga_facturas], 'array');
            $observaciones = \filter_input(INPUT_POST, 'carga_obs');
            $ordenCarga0 = new distribucion_ordenescarga();
            $ordenCarga0->idempresa = $this->empresa->id;
            $ordenCarga0->codalmacen = $almacenorig;
            $ordenCarga0->codalmacen_dest = $almacendest;
            $ordenCarga0->codtrans = $codtrans;
            $ordenCarga0->unidad = $codunidad;
            $ordenCarga0->tipounidad = $this->distrib_unidades->get($this->empresa->id, $codunidad)[0]->tipounidad;
            $ordenCarga0->conductor = $conductor;
            $ordenCarga0->tipolicencia = $this->distrib_conductores->get($this->empresa->id, $conductor)[0]->tipolicencia;
            $ordenCarga0->fecha = $fecha_reparto;
            $ordenCarga0->observaciones = $observaciones;
            $ordenCarga0->totalcantidad = $resultados_facturas['totalCantidad'];
            $ordenCarga0->totalpeso = $resultados_facturas['totalPeso'];
            $ordenCarga0->estado = true;
            $ordenCarga0->despachado = false;
            $ordenCarga0->cargado = false;
            $ordenCarga0->usuario_creacion = $this->user->nick;
            $ordenCarga0->usuario_modificacion = $this->user->nick;
            $ordenCarga0->fecha_creacion = \Date('d-m-Y H:i:s');
            if($ordenCarga0->save()){
                $this->guardar_lineas_ordencarga($ordenCarga0, $resultados_facturas['resultados']);
            }
        }elseif($type === 'ver-carga'){
            $ordencarga = \filter_input(INPUT_GET, 'ordencarga');
            $datos_ordencarga = explode('-', $ordencarga);
            $idordencarga = $datos_ordencarga[0];
            $codalmacen = $datos_ordencarga[1];
            $this->visualizar_ordencarga($idordencarga,$codalmacen);
        }elseif($type === 'imprimir-carga'){
            $ordencarga = \filter_input(INPUT_GET, 'ordencarga');
            $this->imprimir_ordencarga($ordencarga);
        }else{
            $this->resultados = $this->distrib_ordenescarga->all($this->empresa->id);
        }
        $this->total_resultados = 0;
        $this->total_resultados_txt = 0;
        $this->num_resultados = 0;
    }
    
    public function imprimir_ordencarga($ordencarga){
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($ordencarga);
    }
    
    public function visualizar_ordencarga($idordencarga,$codalmacen){
        $datos = array();
        $ordencarga = $this->distrib_ordenescarga->get($this->empresa->id, $idordencarga, $codalmacen);
        $datos['totalCantidad'] = $ordencarga[0]->totalcantidad;
        $datos['totalPeso'] = $ordencarga[0]->totalpeso;
        $lineasOrdencarga = $this->distrib_lineasordenescarga->get($this->empresa->id, $idordencarga, $codalmacen);
        $detalleLineas = array();
        foreach($lineasOrdencarga as $values){
            $producto = $this->articulo->get($values->referencia);
            $values->producto = $producto->descripcion;
            $detalleLineas[] = $values;
        }
        $datos['resultados'] = $detalleLineas;
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode(array('cabecera'=>$ordencarga[0],'userData'=>array('referencia'=>"",'producto'=>'Total','cantidad'=>$datos['totalCantidad']),'rows'=>$datos['resultados']));
    }
    
    public function guardar_lineas_ordencarga($ordencarga, $lineas){
        $this->template = 'distrib_ordencarga';
        $lineasOrdenCarga0 = new distribucion_lineasordenescarga();
        $erroresLinea = "";
        foreach ($lineas as $values){
            $lineasOrdenCarga0->idempresa = $this->empresa->id;
            $lineasOrdenCarga0->codalmacen = $ordencarga->codalmacen;
            $lineasOrdenCarga0->idordencarga = $ordencarga->idordencarga;
            $lineasOrdenCarga0->fecha = $ordencarga->fecha;
            $lineasOrdenCarga0->referencia = $values['referencia'];
            $lineasOrdenCarga0->cantidad = $values['cantidad'];
            //a ser implementado el peso
            $lineasOrdenCarga0->peso = 0;
            $lineasOrdenCarga0->estado = true;
            $lineasOrdenCarga0->usuario_creacion = $this->user->nick;
            $lineasOrdenCarga0->fecha_creacion = $ordencarga->fecha_creacion;
            if(!$lineasOrdenCarga0->save()){
                $coma = (isset($erroresLinea))?", ":"";
                $erroresLinea .= $coma.$values['referencia'];
            }
        }
        if(empty($erroresLinea)){
            $this->new_message('Orden de carga '.$ordencarga->idordencarga.' guardada correctamente');
        }else{
            $this->new_error_msg('Orden de carga '.$ordencarga->idordencarga.' guardada con errores en los siguientes articulos: '.$erroresLinea.' por favor revise la información enviada.');
        }
        $this->resultados = $this->distrib_ordenescarga->all($this->empresa->id);
    }
    
    public function buscar_facturas($buscar_fecha, $codalmacen, $offset){
        $this->template = FALSE;
        $this->resultados = array();
        $data_search = $this->facturas_cliente->all_desde($buscar_fecha, $buscar_fecha);
        foreach ($data_search as $values){
            if($values->codalmacen == $codalmacen){
                $this->resultados[]=$values;
            }
        }
        header('Content-Type: application/json');
        echo json_encode($this->resultados);
    }
    
    public function lista_unidades($idempresa,$codtrans,$codalmacen){
        $this->template = FALSE;
        $this->resultados = array();
        $this->resultados = $this->distrib_unidades->activos_agencia_almacen($idempresa,$codtrans,$codalmacen);
        header('Content-Type: application/json');
        echo json_encode($this->resultados);
    }
    
    public function lista_conductores($idempresa,$codtrans,$codalmacen){
        $this->template = FALSE;
        $this->resultados = array();
        $this->resultados = $this->distrib_conductores->activos_agencia_almacen($idempresa,$codtrans,$codalmacen);
        header('Content-Type: application/json');
        echo json_encode($this->resultados);
    }
    
    public function total_pendientes(){
        return 10;
    }
    
    public function paginas(){
        return array('actual'=>1, 'num'=> 1);
    }
    
    public function crear_carga($datos,$retorno){
        
        $array_facturas = explode(",",$datos['facturas']);
        $this->resultados = array();
        $lineas_factura = array();
        $lista_resumen = array();
        $data_resumen = array();
        $suma_cantidades = 0;
        $suma_peso = 0;
        foreach($array_facturas as $key){
            if($key){
                $lineas_factura[] = $this->linea_factura_cliente->all_from_factura($key);
            }
        }
        foreach($lineas_factura as $linea_factura){
            foreach($linea_factura as $key=>$values){
                if(!isset($lista_resumen[$values->referencia])){
                    $lista_resumen[$values->referencia]=0;
                }
                if(!isset($data_resumen[$values->referencia])){
                    $data_resumen[$values->referencia]=array();
                }
                $lista_resumen[$values->referencia] += $values->cantidad;
                $data_resumen[$values->referencia] = array('referencia'=>$values->referencia ,'producto'=>$values->descripcion, 'cantidad'=>$lista_resumen[$values->referencia]);
                $suma_cantidades += $values->cantidad;
            }
        }
        foreach($data_resumen as $key=>$datos){
            $this->resultados[] = $datos;
        }
        if($retorno == 'json'){
            $this->template = FALSE;
            header('Content-Type: application/json');
            echo json_encode(array('userData'=>array('referencia'=>"",'producto'=>'Total','cantidad'=>$suma_cantidades),'rows'=>$this->resultados));
        }elseif($retorno == 'array'){
            return array('resultados'=>$this->resultados,'totalCantidad'=>$suma_cantidades, 'totalPeso'=>$suma_peso);
        }
    }

    public function nueva_carga() {
        return "Nueva Orden";
    }

    public function imprime_carga($id) {
        return "Imprime la orden " . $id;
    }

    public function delete_carga($id) {
        return "Elimina la orden " . $id;
    }
    
    private function share_extensions() {
        
        $fsext0 = new fs_extension(
            array(
                'name' => 'ordencarga_datepicker_es_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            )
        );
        $fsext0->save();
        
        $fsext1 = new fs_extension(
            array(
                'name' => 'ordencarga_jqueryui_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/jquery-ui.min.js"></script>',
                'params' => ''
            )
        );
        $fsext1->save();
        
        $fsext2 = new fs_extension(
            array(
                'name' => 'ordencarga_jqueryui_css1',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.min.css"/>',
                'params' => ''
            )
        );
        $fsext2->save();
        
        $fsext3 = new fs_extension(
            array(
                'name' => 'ordencarga_jqueryui_css2',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
                'params' => ''
            )
        );
        $fsext3->save();
        
        $fsext4 = new fs_extension(
            array(
                'name' => 'ordencarga_jqueryui_css3',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
                'params' => ''
            )
        );
        $fsext4->save();
        
        $fsext5 = new fs_extension(
            array(
                'name' => 'distribucion_css4',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/distribucion.css"/>',
                'params' => ''
            )
        );
        $fsext5->save();
        
        $fsext6 = new fs_extension(
            array(
                'name' => 'distribucion_css5',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
                'params' => ''
            )
        );
        $fsext6->save();
        
        $fsext7 = new fs_extension(
            array(
                'name' => 'distribucion_css6',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext7->save();
        
        $fsext8 = new fs_extension(
            array(
                'name' => 'distribucion_css7',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext8->save();
        
        $fsext9 = new fs_extension(
            array(
                'name' => 'distribucion_js9',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="plugins/distribucion/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext9->save();
        
        $fsext10 = new fs_extension(
            array(
                'name' => 'distribucion_js10',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="plugins/distribucion/view/js/locale/defaults-es_CL.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext10->save();
        
        $fsext11 = new fs_extension(
            array(
                'name' => 'distribucion_css11',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/distribucion/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            )
        );
        $fsext11->save();
        
        $fsext12 = new fs_extension(
            array(
                'name' => 'distribucion_js12',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script src="plugins/distribucion/view/js/bootbox.min.js" type="text/javascript"></script>',
                'params' => ''
            )
        );
        $fsext12->save();
    }

}
