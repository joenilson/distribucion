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
    public $rutas_elegidas;
    public $rutas_listadas;
    public $vendedor;
    public $vendedores;
    public $vendedores_elegidos;
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
        $codvendedor = filter_input(INPUT_POST, 'vendedores');
        $codruta = filter_input(INPUT_POST, 'rutas');
        $fecha = filter_input(INPUT_POST, 'fecha');
        $tipo = filter_input(INPUT_POST, 'tipo');

        $this->codalmacen = (isset($codalmacen))?$codalmacen:'';
        $this->codvendedor = (!empty($codvendedor))?$codvendedor:'';
        $this->ruta = (!empty($codruta))?$codruta:'';
        $this->fecha = (isset($fecha))?$fecha:'';
        $this->rutas_elegidas = (!empty($codruta))?explode(",",$this->ruta):NULL;
        $this->vendedores_elegidos = (!empty($codvendedor))?explode(",",$this->codvendedor):NULL;
        
        if(!empty($this->codalmacen)){
            $this->vendedores = $this->distribucion_organizacion->activos_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'VENDEDOR');
        }else{
            $this->vendedores = $this->distribucion_organizacion->activos_tipoagente($this->empresa->id, 'VENDEDOR');
        }
        
        if(!empty($this->vendedores_elegidos)){
            $lista = array();
            foreach($this->vendedores_elegidos as $vendedor){
                $linea = $this->distribucion_rutas->all_rutasporagente($this->empresa->id, $this->codalmacen, $vendedor);
                $lista = array_merge($linea, $lista);
            }
            $this->rutas = $lista;
        }elseif(!empty($this->codalmacen)){
            $this->rutas = $this->distribucion_rutas->all_rutasporalmacen($this->empresa->id, $this->codalmacen);
        }
        if(!empty($this->rutas)){
            $this->buscar_seleccionados('rutas');
        }
        
        $this->buscar_seleccionados('vendedores');
        
        if(isset($tipo) and !empty($tipo)){
            switch($tipo){
                case "busqueda":
                    $this->buscar_rutas();
                    break;
                default:
                    break;
            }
        }
    }
    
    public function buscar_seleccionados($tipo){
        switch($tipo){
            case "rutas":
                $rutas_origen = $this->rutas;
                $rutas_destino = array();
                foreach($rutas_origen as $linea){
                    if(!empty($this->rutas_elegidas)){
                        $linea->seleccionada = (in_array($linea->ruta, $this->rutas_elegidas))?true:false;
                    }else{
                        $linea->seleccionada = false;
                    }
                    $rutas_destino[] = $linea;
                }
                $this->rutas = $rutas_destino;
                break;
            case "vendedores":
                $vendedores_origen = $this->vendedores;
                $vendedores_destino = array();
                foreach($vendedores_origen as $linea){
                    if(!empty($this->vendedores_elegidos)){
                        $linea->seleccionado=(in_array($linea->codagente, $this->vendedores_elegidos))?true:false;
                    }else{
                        $linea->seleccionada = false;
                    }
                    $vendedores_destino[] = $linea;
                }
                $this->vendedores = $vendedores_destino;
                break;
            default:
                break;
        }
    }
    
    public function buscar_rutas(){
        $lista = array();
        $lista_rutas = (!empty($this->rutas_elegidas))?$this->rutas_elegidas:$this->rutas;
        foreach ($lista_rutas as $r){
            $valor = (is_object($r))?$r->ruta:$r;
            $info = $this->distribucion_rutas->get($this->empresa->id, $valor);
            $info->cantidad = $this->distribucion_rutas->cantidad_asignados($this->empresa->id, $valor);
            $lista[] = $info;
            
        }
        $this->rutas_listadas = $lista;
    }

    public function buscar_clientes(){
        $lista_clientes = array();
        foreach ($this->rutas_elegidas as $r){
            $lista_clientes = array_merge($lista_clientes, $this->distribucion_clientes->clientes_ruta($this->empresa->id, $r));
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
