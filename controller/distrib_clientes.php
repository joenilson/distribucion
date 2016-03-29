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
require_model('distribucion_segmentos.php');
require_model('distribucion_clientes.php');
require_model('distribucion_coordenadas_clientes.php');
/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_clientes extends fs_controller {
    public $codcliente;
    public $cliente;
    public $info_cliente;
    public $almacen;
    public $supervisor;
    public $vendedor;
    public $agente;
    public $rutas;
    public $type;
    public $distrib_cliente;
    public $distrib_coordenadas_cliente;
    public $distribucion_coordenadas_cliente;
    public $distribucion_agente;
    public $distribucion_organizacion;
    public $distribucion_rutas;
    public $distribucion_segmentos;
    public $distribucion_clientes;
    public $supervisores_asignados;
    public $supervisores_libres;
    public $vendedores_asignados;
    public $vendedores_libres;
    public $canales;
    public $canales_activos;
    public $subcanales;
    public $tab_activa;
    
    public function __construct() {
        parent::__construct(__CLASS__, '6 - Distribución Clientes', 'distribucion');
    }
    
    public function private_core(){
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->share_extension();
        $this->almacen = new almacen();
        $this->agente = new distribucion_agente();
        $this->distribucion_organizacion = new distribucion_organizacion();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_segmentos = new distribucion_segmentos();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->distribucion_coordenadas_cliente = new distribucion_coordenadas_clientes();
        $this->cliente = new cliente();
        $this->tab_activa = false;
        $type = \filter_input(INPUT_POST, 'type');
        
        $this->type = $type;

        if($type=='supervisor'){
            $this->tratar_supervisor();
        }elseif($type=='vendedor'){
            $this->tratar_vendedor();
        }elseif($type=='ruta'){
            $this->tratar_ruta();
        }elseif($type=='canal'){
            $this->tratar_canal();
        }elseif($type=='subcanal'){
            $this->tratar_subcanal();
        }elseif($type == 'distrib_cliente'){
            $this->tab_activa = 'p_rutas';
            $this->tratar_cliente();
        }elseif($type == 'direccion_cliente'){
            $this->tab_activa = 'p_coordenadas';
            $this->tratar_direccion_cliente();
        }

        $this->supervisores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'SUPERVISOR');
        $this->supervisores_libres = $this->agente->get_activos_por('cargo','SUPERVISOR');
        
        $this->vendedores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'VENDEDOR');
        $this->vendedores_libres = $this->agente->get_activos_por('cargo','VENDEDOR');
        
        $this->rutas = $this->distribucion_rutas->all($this->empresa->id);
        
        $this->canales = $this->distribucion_segmentos->all_tiposegmento($this->empresa->id, 'CANAL');
        $this->canales_activos = $this->distribucion_segmentos->activos_tiposegmento($this->empresa->id, 'CANAL');
        $this->subcanales = $this->distribucion_segmentos->all_tiposegmento($this->empresa->id, 'SUBCANAL');
        
        $codcliente = \filter_input(INPUT_GET, 'codcliente');
        if(!empty($codcliente)){
            $this->codcliente = $codcliente;
            $this->info_cliente = $this->cliente->get($codcliente);
            $this->distrib_cliente = $this->distribucion_clientes->get($this->empresa->id,$this->codcliente);
            $this->distrib_coordenadas_cliente = $this->distribucion_coordenadas_cliente->all_cliente($this->empresa->id,$this->codcliente);
            $this->rutas_libres = $this->rutas_libres();
            $this->template = 'extension/distrib_cliente';
        }

    }

    public function tratar_supervisor(){
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
        $codagente = \filter_input(INPUT_POST, 'codagente');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $borrar = \filter_input(INPUT_POST, 'borrar');
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
        if($borrar){
            $agente0->delete();
            $this->new_message("$agente0->tipoagente eliminado correctamente.");
        }else{
            if($agente0->save()){
                $this->new_message("$agente0->tipoagente asignado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible tratar los datos del ".$agente0->tipoagente."!");
            }
        }
    }

    public function rutas_libres(){
        if($this->distrib_cliente){
            $ruta_tomada = array();
            foreach($this->distrib_cliente as $ruta){
                $ruta_tomada[$ruta->ruta] = "TRUE";
            }
            foreach($this->rutas as $id => $valores){
                if(isset($ruta_tomada[$valores->ruta])){
                    unset($this->rutas[$id]);
                }
            }
        }
        return $this->rutas;
    }
    
    public function tratar_vendedor(){
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
        $codsupervisor = \filter_input(INPUT_POST, 'codsupervisor');
        $codagente = \filter_input(INPUT_POST, 'codagente');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $borrar = \filter_input(INPUT_POST, 'borrar');
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
        if($borrar){
            $agente0->delete();
            $this->new_message("$agente0->tipoagente eliminado correctamente.");
        }else{
            if($agente0->save()){
                $this->new_message("$agente0->tipoagente asignado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible tratar los datos del ".$agente0->tipoagente."!");
            }
        }
    }
    
    public function tratar_ruta(){
        $codagente = \filter_input(INPUT_POST, 'codagente');
        $ruta = \filter_input(INPUT_POST, 'ruta');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $data_agente = $this->distribucion_organizacion->get($this->empresa->id, $codagente);
        $codalmacen = $data_agente->codalmacen;
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
        $borrar = \filter_input(INPUT_POST, 'borrar');
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
        if($borrar){
            $ruta0->delete();
            $this->new_message("Ruta $ruta0->ruta eliminada correctamente y liberados los clientes.");
        }else{
            if($ruta0->save()){
                $this->new_message("Ruta $ruta0->ruta tratada correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar la ruta con los datos seleccionados!");
            }
        }
    }
    
    public function tratar_canal(){
        $canal = \filter_input(INPUT_POST, 'canal');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $tiposegmento = strtoupper($this->type);
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val))?true:false;
        $borrar = \filter_input(INPUT_POST, 'borrar');
        $canal0 = new distribucion_segmentos();
        $canal0->idempresa = $this->empresa->id;
        $canal0->codigo = $canal;
        $canal0->descripcion = $descripcion;
        $canal0->tiposegmento = $tiposegmento;
        $canal0->estado = $estado;
        $canal0->usuario_creacion = $this->user->nick;
        $canal0->fecha_creacion = \Date('d-m-Y H:i:s');
        if($borrar){
            $canal0->delete();
            $this->new_message("Canal $canal0->codigo $canal0->descripcion eliminada correctamente y liberados los clientes.");
        }else{
            if($canal0->save()){
                $this->new_message("Canal $canal0->codigo $canal0->descripcion tratado correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar los datos ingresados!");
            }
        }
    }
    
    public function tratar_subcanal(){
        $canal = \filter_input(INPUT_POST, 'canal');
        $subcanal = \filter_input(INPUT_POST, 'subcanal');
        $tiposegmento = strtoupper($this->type);
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val))?true:false;
        $borrar = \filter_input(INPUT_POST, 'borrar');
        $subcanal0 = new distribucion_segmentos();
        $subcanal0->idempresa = $this->empresa->id;
        $subcanal0->codigo = $subcanal;
        $subcanal0->codigo_padre = $canal;
        $subcanal0->descripcion = $descripcion;
        $subcanal0->tiposegmento = $tiposegmento;
        $subcanal0->estado = $estado;
        $subcanal0->usuario_creacion = $this->user->nick;
        $subcanal0->fecha_creacion = \Date('d-m-Y H:i:s');
        if($borrar){
            $subcanal0->delete();
            $this->new_message("Subcanal $subcanal0->codigo $subcanal0->descripcion eliminada correctamente y liberados los clientes.");
        }else{
            if($subcanal0->save()){
                $this->new_message("Subcanal $subcanal0->codigo $subcanal0->descripcion tratado correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar los datos ingresados!");
            }
        }
    }
    
    public function tratar_cliente(){
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $iddireccion = \filter_input(INPUT_POST, 'iddireccion');
        $ruta = \filter_input(INPUT_POST, 'ruta');
        $canal = \filter_input(INPUT_POST, 'canal');
        $subcanal = \filter_input(INPUT_POST, 'subcanal');
        $borrar = \filter_input(INPUT_POST, 'borrar');
        $distcli0 = new distribucion_clientes();
        $distcli0->idempresa = $this->empresa->id;
        $distcli0->codcliente = $codcliente;
        $distcli0->iddireccion = $iddireccion;
        $distcli0->ruta = $ruta;
        $distcli0->canal = $canal;
        $distcli0->subcanal = $subcanal;
        $distcli0->fecha_creacion = \Date('d-m-Y H:i:s');
        $distcli0->usuario_creacion = $this->user->nick;
        if($borrar){
            $distcli0->delete();
            $this->new_message("Datos del cliente $distcli0->codcliente para la ruta $distcli0->ruta eliminados correctamente.");
        }else{
            if($distcli0->save()){
                $this->new_message("Datos del cliente $distcli0->codcliente tratados correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar los datos ingresados!");
            }
        }
        $this->rutas = $this->distribucion_rutas->all($this->empresa->id);
        $this->codcliente = $codcliente;
        $this->info_cliente = $this->cliente->get($codcliente);
        $this->distrib_coordenadas_cliente = $this->distribucion_coordenadas_cliente->all_cliente($this->empresa->id,$this->codcliente);
        $this->distrib_cliente = $this->distribucion_clientes->get($this->empresa->id,$this->codcliente);
        $this->rutas_libres = $this->rutas_libres();
        $this->template = 'extension/distrib_cliente';
    }
    
    public function tratar_direccion_cliente(){
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $iddireccion = \filter_input(INPUT_POST, 'iddireccion');
        $coordenadas = \filter_input(INPUT_POST, 'coordenadas');
        $borrar = \filter_input(INPUT_POST, 'borrar');
        $distccli0 = new distribucion_coordenadas_clientes();
        $distccli0->idempresa = $this->empresa->id;
        $distccli0->codcliente = $codcliente;
        $distccli0->iddireccion = $iddireccion;
        $distccli0->coordenadas = $coordenadas;
        $distccli0->fecha_creacion = \Date('d-m-Y H:i:s');
        $distccli0->usuario_creacion = $this->user->nick;
        if($borrar){
            $distccli0->delete();
            $this->new_message("Coordenadas de la dirección del cliente $distccli0->codcliente eliminados correctamente.");
        }else{
            if($distccli0->save()){
                $this->new_message("Coordenadas del cliente $distccli0->codcliente tratadas correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar los datos ingresados!");
            }
        }
        $this->rutas = $this->distribucion_rutas->all($this->empresa->id);
        $this->codcliente = $codcliente;
        $this->info_cliente = $this->cliente->get($codcliente);
        $this->distrib_coordenadas_cliente = $this->distribucion_coordenadas_cliente->all_cliente($this->empresa->id,$this->codcliente);
        $this->distrib_cliente = $this->distribucion_clientes->get($this->empresa->id,$this->codcliente);
        $this->rutas_libres = $this->rutas_libres();
        $this->template = 'extension/distrib_cliente';
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
