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
require_model('cliente.php');
require_model('almacen.php');
/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_clientes extends fs_controller {
    public $codcliente;
    public $cliente;
    public $almacen;
    public $supervisor;
    public $cliente_datos;
    
    public function __construct() {
        parent::__construct(__CLASS__, '7 - Distribución Clientes', 'distribucion');
    }
    
    public function private_core(){
        
        $this->share_extension();
        
        $this->almacen = new almacen();
        
        $type = \filter_input(INPUT_POST, 'type');
        $codcliente = \filter_input(INPUT_GET, 'codcliente');
        if(!empty($codcliente)){
            $this->codcliente = $codcliente;
            $this->cliente = new cliente();
            $this->cliente_datos = $this->cliente->get($codcliente);
            $this->template = 'extension/distrib_cliente';
        }
        if($type=='supervisor'){
            $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
            $nombre = \filter_input(INPUT_POST, 'nombre');
            $docidentidad = \filter_input(INPUT_POST, 'docidentidad');
            $estado = \filter_input(INPUT_POST, 'estado');
        }
    }
    
    private function share_extension() {
        $extensiones = array(
            array(
                'name' => 'distribucion_cliente',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_cliente',
                'type' => 'button',
                'text' => '<span class="glyphicon glyphicon-transfer" aria-hidden="true"></span> &nbsp; Distribución',
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
