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
        $this->fecha_facturacion = ($fecha_facturacion)?$fecha_facturacion:\date('d-m-Y');
        $this->ruta = ($ruta)?$ruta:false;
        $this->codalmacen = ($codalmacen)?$codalmacen:false;
        $this->codcliente = ($codcliente)?$codcliente:false;
        if($this->codcliente){
            $this->cliente = $this->clientes->get($this->codcliente);
        }
        $this->rutas = ($this->codalmacen)?$this->rutas_all->all_rutasporalmacen($this->empresa->id, $this->codalmacen):$this->rutas_all->all($this->empresa->id);
    }
    
    public function shared_extensions(){
        
    }
}
