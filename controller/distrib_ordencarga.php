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
require_model('almacen');
require_model('agencia_transporte.php');
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_ordencarga extends fs_controller {

    public $facturas_cliente;
    public $almacen;
    public $resultados;
    public $agencia_transporte;
    public $distrib_conductores;
    public $distrib_unidades;
    
    public function __construct() {
        parent::__construct(__CLASS__, '4 - Crear Orden de Carga', 'distribucion');
    }

    public function private_core() {
        $this->almacen = new almacen();
        $this->facturas_cliente = new factura_cliente();
        
        $this->agencia_transporte = new agencia_transporte();
        $this->distrib_conductores = new distribucion_conductores();
        $this->distrib_unidades = new distribucion_unidades();
        
        $this->share_extensions();
        $type = \filter_input(INPUT_GET, 'type');
        $buscar_fecha = \filter_input(INPUT_GET, 'buscar_fecha');
        $codalmacen = \filter_input(INPUT_GET, 'codalmacen');
        $codtrans = \filter_input(INPUT_GET, 'codtrans');
        $offset = \filter_input(INPUT_GET, 'offset');
        
        if($type === 'buscar_facturas'){
            $this->buscar_facturas($buscar_fecha, $codalmacen, $offset);
        }elseif($type === 'select-unidad'){
            $this->lista_unidades($this->empresa->id,$codtrans,$codalmacen);
        }

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
    
    public function total_pendientes(){
        return 10;
    }
    
    public function paginas(){
        return 10;
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
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/> ',
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
        
        
    }

}
