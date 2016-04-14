<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('almacen.php');
require_model('cliente.php');
require_model('distribucion_clientes.php');
require_model('distribucion_rutas.php');
require_model('distribucion_organizacion.php');
/**
 * Description of impresion_rutas
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class impresion_rutas extends fs_controller{
    public $almacen;
    public $codalmacen;
    public $clientes;
    public $fecha;
    public $rutas;
    public $vendedor;
    public $vendedores;
    public $codvendedor;
    public $distribucion_rutas;
    public $distribucion_clientes;
    public $distribucion_organizacion;
    public function __construct() {
        parent::__construct(__CLASS__, '8 - Impresión de Rutas', 'distribucion', FALSE, TRUE, TRUE);
    }

    protected function private_core() {
        $this->share_extensions();

        $this->almacen = new almacen();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->distribucion_organizacion = new distribucion_organizacion();

        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $codvendedor = filter_input(INPUT_POST, 'codvendedor');
        $codruta = filter_input(INPUT_POST, 'rutas');
        $fecha = filter_input(INPUT_POST, 'fecha');
        $tipo = filter_input(INPUT_POST, 'tipo');

        $this->codalmacen = (isset($codalmacen))?$codalmacen:'';
        $this->codvendedor = (isset($codvendedor))?$codvendedor:'';
        $this->ruta = (isset($codruta))?$codruta:'';
        $this->fecha = (isset($fecha))?$fecha:'';

        if(!empty($this->codalmacen)){
            $this->vendedores = $this->distribucion_organizacion->activos_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'VENDEDOR');
        }else{
            $this->vendedores = $this->distribucion_organizacion->activos_tipoagente($this->empresa->id, 'VENDEDOR');
        }

        if(!empty($this->codvendedor)){
            $this->rutas = $this->distribucion_rutas->all_rutasporagente($this->empresa->id, $this->codalmacen, $this->codvendedor);
        }elseif(!empty($this->codalmacen)){
            $this->rutas = $this->distribucion_rutas->all_rutasporalmacen($this->empresa->id, $this->codalmacen);
        }

        if(isset($tipo) and !empty($tipo)){
            switch($tipo){
                case "busqueda":
                    $this->buscar();
                    break;
                default:
                    break;
            }
        }
    }

    public function buscar(){
        $lista_clientes = array();
        $lista_rutas = explode(",",$this->ruta);
        foreach ($lista_rutas as $r){
            $lista_clientes[] = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $r);
        }
        $this->clientes = $lista_clientes;
    }

    private function share_extensions(){
        $fsext0 = new fs_extension(
            array(
                'name' => 'impresion_rutas_datepicker_es_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            )
        );
        $fsext0->save();

        $fsext1 = new fs_extension(
            array(
            'name' => 'impresion_rutas_jqueryui_js',
            'page_from' => __CLASS__,
            'page_to' => 'impresion_rutas',
            'type' => 'head',
            'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/jquery-ui.min.js"></script>',
            'params' => ''
            )
        );
        $fsext1->save();

        $fsext2 = new fs_extension(
            array(
            'name' => 'impresion_rutas_jqueryui_css1',
            'page_from' => __CLASS__,
            'page_to' => 'impresion_rutas',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.min.css"/>',
            'params' => ''
            )
        );
        $fsext2->save();

        $fsext3 = new fs_extension(
                array(
           'name' => 'impresion_rutas_jqueryui_css2',
           'page_from' => __CLASS__,
           'page_to' => 'impresion_rutas',
           'type' => 'head',
           'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
           'params' => ''
                )
        );
        $fsext3->save();

        $fsext4 = new fs_extension(
                array(
           'name' => 'impresion_rutas_jqueryui_css3',
           'page_from' => __CLASS__,
           'page_to' => 'impresion_rutas',
           'type' => 'head',
           'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
           'params' => ''
                )
        );
        $fsext4->save();
    }
}
