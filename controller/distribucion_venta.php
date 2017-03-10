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
require_model('articulo.php');
require_model('cliente.php');
require_model('dircliente.php');
require_model('distribucion_clientes.php');
require_model('distribucion_rutas.php');
require_model('distribucion_segmentos.php');
require_model('cliente_ruta.php');
require_model('unidadmedida.php');
require_model('articulo_unidadmedida.php');
/**
 * Description of distribucion_venta
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distribucion_venta extends fs_controller{
    public $almacen;
    public $codalmacen;
    public $rutas_almacen;
    public $total_rutas_almacen;
    public $distribucion_clientes;
    public $distribucion_rutas;
    public $distribucion_segmentos;
    public $fecha_pedido;
    public $fecha_facturacion;
    public $ruta;
    public $rutas;
    public $rutas_all;
    public $cliente;
    public $cliente_nombre;
    public $codcliente;
    public $clientes_ruta;
    public $clientes;
    public $canales;
    public $subcanales;
    public $selector_habilitado;
    public function __construct() {
        parent::__construct(__CLASS__, '9 - Nueva Venta', 'distribucion', FALSE, TRUE, FALSE);
    }

    protected function private_core() {
        $this->almacen = new almacen();
        $this->clientes = new cliente();
        $this->rutas_all = new distribucion_rutas();
        $this->distribucion_clientes = new distribucion_clientes();
        //Mandamos los botones y tabs
        $this->shared_extensions();
        //Verificamos los accesos del usuario
        $this->allow_delete = ($this->user->admin)?TRUE:$this->user->allow_delete_on(__CLASS__);

        $fecha_pedido = filter_input(INPUT_POST, 'fecha_pedido');
        $fecha_facturacion = filter_input(INPUT_POST, 'fecha_facturacion');
        $ruta = filter_input(INPUT_POST, 'ruta');
        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $codcliente = filter_input(INPUT_POST, 'codcliente');
        $this->fecha_pedido = ($fecha_pedido)?$fecha_pedido:\date('d-m-Y');
        $this->fecha_facturacion = ($fecha_facturacion)?$fecha_facturacion:\date('d-m-Y', strtotime('+1 day'));
        $this->ruta = ($ruta)?$ruta:false;
        $this->codalmacen = ($codalmacen)?$codalmacen:false;
        $this->codcliente = ($codcliente)?$codcliente:false;
        if($this->codcliente){
            $this->cliente = $this->clientes->get($this->codcliente);
            $this->cliente_nombre = $this->cliente->nombre;
        }
        $cliente = filter_input(INPUT_GET, 'buscar_cliente');
        if($cliente){
            $this->buscar_cliente($cliente);
        }
        $this->rutas = ($this->codalmacen)?$this->rutas_all->all_rutasporalmacen($this->empresa->id, $this->codalmacen):array();
        if($this->ruta and $this->codalmacen){
            $this->clientes_ruta = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $this->codalmacen, $this->ruta);
        }
    }

    public function buscar_cliente($query){
        /// desactivamos la plantilla HTML
        $this->template = FALSE;
        $json = array();
        foreach($this->clientes->search($query) as $cli)
        {
            $json[] = array('value' => $cli->nombre, 'codcliente' => $cli->codagente);
        }
        header('Content-Type: application/json');
        echo json_encode( array('query' => $query, 'suggestions' => $json) );
    }

    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => 'distribucion_venta_select_js',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'distribucion_venta_select_css',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="'.FS_PATH.'plugins/distribucion/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            )
        );
        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
}
