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
require_model('distribucion_rutas.php');
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
    public $rutas;
    public $type;
    public $cliente_datos;
    public $distribucion_agente;
    public $distribucion_organizacion;
    public $distribucion_rutas;
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
        $this->distribucion_rutas = new distribucion_rutas();

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
        }elseif($type=='ruta'){
            $codagente = \filter_input(INPUT_POST, 'codagente');
            $ruta = \filter_input(INPUT_POST, 'ruta');
            $descripcion = \filter_input(INPUT_POST, 'descripcion');
            $data_agente = $this->distribucion_organizacion->get($this->empresa->id, $codagente);
            $codalmacen = $data_agente[0]->codalmacen;
            $lunes_val = \filter_input(INPUT_POST, 'lunes');
            $martes_val = \filter_input(INPUT_POST, 'martes');
            $miercoles_val = \filter_input(INPUT_POST, 'miercoles');
            $jueves_val = \filter_input(INPUT_POST, 'jueves');
            $viernes_val = \filter_input(INPUT_POST, 'viernes');
            $sabado_val = \filter_input(INPUT_POST, 'sabado');
            $domingo_val = \filter_input(INPUT_POST, 'domingo');
            $estado_val = \filter_input(INPUT_POST, 'estado');
            $lunes = (isset($lunes_val))?1:0;
            $martes = (isset($martes_val))?1:0;
            $miercoles = (isset($miercoles_val))?1:0;
            $jueves = (isset($jueves_val))?1:0;
            $viernes = (isset($viernes_val))?1:0;
            $sabado = (isset($sabado_val))?1:0;
            $domingo = (isset($domingo_val))?1:0;
            $estado = (isset($estado_val))?true:false;
            $ruta0 = new distribucion_rutas();
            $ruta0->idempresa = $this->empresa->id;
            $ruta0->codalmacen = $codalmacen;
            $ruta0->codagente = $codagente;
            $ruta0->ruta = $ruta;
            $ruta0->descripcion = trim($descripcion);
            $ruta0->lunes = trim($lunes);
            $ruta0->martes = trim($martes);
            $ruta0->miercoles = trim($miercoles);
            $ruta0->jueves = trim($jueves);
            $ruta0->viernes = trim($viernes);
            $ruta0->sabado = trim($sabado);
            $ruta0->domingo = trim($domingo);
            $ruta0->estado = trim($estado);
            $ruta0->usuario_creacion = $this->user->nick;
            $ruta0->fecha_creacion = \Date('d-m-Y H:i:s');
            if($ruta0->save()){
                $this->new_message("Ruta $ruta0->ruta tratada correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar la ruta con los datos seleccionados!");
            }
        }elseif($type=='canal'){
            $canal = \filter_input(INPUT_POST, 'canal');
            $descripcion = \filter_input(INPUT_POST, 'descripcion');
            $estado_val = \filter_input(INPUT_POST, 'estado');
            $estado = (isset($estado_val))?true:false;
        }elseif($type=='subcanal'){
            $canal = \filter_input(INPUT_POST, 'canal');
            $subcanal = \filter_input(INPUT_POST, 'subcanal');
            $descripcion = \filter_input(INPUT_POST, 'descripcion');
            $estado_val = \filter_input(INPUT_POST, 'estado');
            $estado = (isset($estado_val))?true:false;
            
        }
        $this->type = $type;
        $this->supervisores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'SUPERVISOR');
        $this->supervisores_libres = $this->agente->get_activos_por('cargo','SUPERVISOR');
        
        $this->vendedores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'VENDEDOR');
        $this->vendedores_libres = $this->agente->get_activos_por('cargo','VENDEDOR');
        
        $this->rutas = $this->distribucion_rutas->all($this->empresa->id);

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
