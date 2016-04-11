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
require_model('distribucion_clientes.php');
require_model('distribucion_rutas.php');
/**
 * Description of distrib_redistribucion
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distrib_redistribucion extends fs_controller{
    public $almacen;
    public $codalmacen;
    public $rutas_almacen;
    public $distribucion_clientes;
    public $distribucion_rutas;
    public $rutas;
    public $ruta_origen;
    public $ruta_destino;
    public $cliente;
    public $clientes_origen;
    public $clientes_destino;

    public function __construct() {
        parent::__construct(__CLASS__, '7 - Redistribucion Clientes', 'distribucion', TRUE, TRUE);
    }
    
    public function private_core(){
        $this->almacen = new almacen();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->rutas_almacen = '';
        $this->ruta_origen = '';
        $this->ruta_destino = '';
        $this->transferidos = array();
        $this->notransferidos = array();
        $type = filter_input(INPUT_GET, 'type');
        $codigo_almacen = filter_input(INPUT_GET, 'codalmacen');
        $this->codalmacen = (isset($codigo_almacen))?$codigo_almacen:'';
        $codigo_rutaorigen = filter_input(INPUT_GET, 'ruta_origen');
        $this->ruta_origen = (isset($codigo_rutaorigen))?$codigo_rutaorigen:'';
        $codigo_rutadestino = filter_input(INPUT_GET, 'ruta_destino');
        $this->ruta_destino = (isset($codigo_rutadestino))?$codigo_rutadestino:'';
        if(isset($type)){
            switch($type){
               case "almacen":
                   $this->codalmacen = filter_input(INPUT_POST, 'codalmacen');
                   break;
               case "ruta_origen":
                   $this->ruta_origen = filter_input(INPUT_POST, 'ruta_origen');
                   break;
               case "ruta_destino":
                   $this->ruta_destino = filter_input(INPUT_POST, 'ruta_destino');
                   break;
               case "transferir":
                   $this->transferir();
                   break;
               default:
                   
                   break;
            }
        }
        $this->rutas_almacen = $this->distribucion_rutas->all_rutasporalmacen($this->empresa->id, $this->codalmacen);
        $this->clientes_origen = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $this->ruta_origen);
        $this->clientes_destino = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $this->ruta_destino);
    }
    
    public function transferir(){
        $clientes = explode(",",filter_input(INPUT_GET, 'clientes'));
        $distribucion_cliente = new distribucion_clientes();
        $exito = 0;
        $error = 0;
        $total = count($clientes);
        foreach($clientes as $cliente){
            $cr0 = $distribucion_cliente->ruta_cliente($this->empresa->id, $cliente, $this->ruta_origen);
            if($cr0->transferir($this->ruta_destino)){
                $exito++;
            }else{
                $error++;
            }
        }
        $mensaje = "Se transfirieron $exito de $total";
        $valor = ($total == $exito)?TRUE:FALSE;
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode( array('success' => $valor, 'mensaje' => $mensaje) );
    }
    
}
