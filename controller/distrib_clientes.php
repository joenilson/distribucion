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
require_model('distribucion_agente.php');
require_model('distribucion_organizacion.php');
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
    public $vendedor;
    public $agente;
    public $cliente_datos;
    public $distribucion_agente;
    public $distribucion_organizacion;
    public $supervisores_asignados;
    public $supervisores_libres;
    public $vendedores_asignados;
    public $vendedores_libres;
    
    public function __construct() {
        parent::__construct(__CLASS__, '7 - Distribución Clientes', 'distribucion');
    }
    
    public function private_core(){
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->share_extension();
        
        $this->almacen = new almacen();
        $this->agente = new distribucion_agente();
        $this->distribucion_organizacion = new distribucion_organizacion();

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
            $codagente = \filter_input(INPUT_POST, 'codagente');
            $estado_val = \filter_input(INPUT_POST, 'estado');
            $estado = (isset($estado_val))?true:false;
            $tipoagente = $this->agente->get($codagente);
            $agente0 = new distribucion_organizacion();
            $agente0->idempresa = $this->empresa->id;
            $agente0->codalmacen = $codalmacen;
            $agente0->codagente = $codagente;
            $agente0->tipoagente = $tipoagente->cargo;
            $agente0->estado = $estado;
            $agente0->usuario_creacion = $this->user->nick;
            $agente0->fecha_creacion = \Date('d-m-Y H:i:s');
            if($agente0->save()){
                $this->new_message("$agente0->tipoagente asignado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible tratar los datos del ".$agente0->tipoagente."!");
            }
        }elseif($type=='vendedor'){
            $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
            $codsupervisor = \filter_input(INPUT_POST, 'codsupervisor');
            $codagente = \filter_input(INPUT_POST, 'codagente');
            $estado_val = \filter_input(INPUT_POST, 'estado');
            $estado = (isset($estado_val))?true:false;
            $tipoagente = $this->agente->get($codagente);
            $agente0 = new distribucion_organizacion();
            $agente0->idempresa = $this->empresa->id;
            $agente0->codalmacen = $codalmacen;
            $agente0->codagente = $codagente;
            $agente0->codsupervisor = $codsupervisor;
            $agente0->tipoagente = $tipoagente->cargo;
            $agente0->estado = $estado;
            $agente0->usuario_creacion = $this->user->nick;
            $agente0->fecha_creacion = \Date('d-m-Y H:i:s');
            if($agente0->save()){
                $this->new_message("$agente0->tipoagente asignado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible tratar los datos del ".$agente0->tipoagente."!");
            }
        }
        $this->supervisores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'SUPERVISOR');
        $this->supervisores_libres = $this->agente->get_activos_por('cargo','SUPERVISOR');
        
        $this->vendedores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'VENDEDOR');
        $this->vendedores_libres = $this->agente->get_activos_por('cargo','VENDEDOR');

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
