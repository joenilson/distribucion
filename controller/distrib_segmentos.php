<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('cliente.php');
require_model('almacen.php');
require_model('distribucion_agente.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_rutas.php');
require_model('distribucion_tiporuta.php');
require_model('distribucion_asignacion_cargos.php');
require_model('distribucion_segmentos.php');
require_model('distribucion_clientes.php');
require_model('distribucion_coordenadas_clientes.php');
/**
 * Controlador para gestionar la información de los segmentos de clientes
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distrib_segmentos extends fs_controller{
    public $almacenes;
    public $codalmacen;
    public $supervisores;
    public $vendedores;
    public $supervisor;
    public $vendedor;
    public $vendedores_libres;
    public $vendedores_asignados;
    public $clientes;
    public $rutas;
    public $distribucion_agente;
    public $distribucion_organizacion;
    public $distribucion_segmentos;
    public $distribucion_rutas;
    public $tiporuta;
    public $canal;
    public $canales;
    public $canales_activos;
    public $subcanales;    
    public $type;
    public $accion;
    public $segmento;
    public function __construct() {
        parent::__construct(__CLASS__, 'Configuración de Segmentos', 'distribucion', FALSE, FALSE, FALSE);
    }
    
    protected function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->shared_extensions();
        $this->almacen = new almacen();
        $this->agente = new distribucion_agente();
        $this->distribucion_organizacion = new distribucion_organizacion();
        $this->distribucion_asignacion_cargos = new distribucion_asignacion_cargos();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_segmentos = new distribucion_segmentos();

        $canal = \filter_input(INPUT_POST, 'b_canales');
        $this->canal = ($canal)?$canal:false;

        $segmento = \filter_input(INPUT_GET, 'segmento');
        $this->segmento = $segmento;
        $accion = \filter_input(INPUT_POST, 'accion');
        switch ($segmento){
            case "subcanales":
                if($accion){
                    $this->tratar_subcanal($accion);
                }
                $this->template = 'distrib_subcanales';
                break;
            default:
                if($accion){
                    $this->tratar_canal($accion);
                }
                $this->template = 'distrib_canales';
                break;
        }
        
        $this->canales = $this->distribucion_segmentos->all_tiposegmento($this->empresa->id, 'CANAL');
        $this->canales_activos = $this->distribucion_segmentos->activos_tiposegmento($this->empresa->id, 'CANAL');
        $this->subcanales = $this->distribucion_segmentos->all_tiposegmento($this->empresa->id, 'SUBCANAL');
        
        if($this->canal){
            $this->subcanales = $this->distribucion_segmentos->all_codigopadre_tiposegmento($this->empresa->id, $this->canal, 'SUBCANAL');
        }

    }
    
    public function tratar_canal($accion){
        $canal = \filter_input(INPUT_POST, 'canal');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $tipo = \filter_input(INPUT_POST, 'type');
        $tiposegmento = strtoupper($tipo);
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val))?true:false;
        $canal0 = new distribucion_segmentos();
        $canal0->idempresa = $this->empresa->id;
        $canal0->codigo = $canal;
        $canal0->descripcion = $descripcion;
        $canal0->tiposegmento = $tiposegmento;
        $canal0->estado = $estado;
        $canal0->usuario_creacion = $this->user->nick;
        $canal0->fecha_creacion = \Date('d-m-Y H:i:s');
        if($accion=='eliminar'){
            $canal0->delete();
            $this->new_message("Canal $canal0->codigo $canal0->descripcion eliminado correctamente y liberados los clientes.");
        }else{
            if($canal0->save()){
                $this->new_message("Canal $canal0->codigo $canal0->descripcion tratado correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar los datos ingresados!");
            }
        }
    }

    public function tratar_subcanal($accion){
        $canal = \filter_input(INPUT_POST, 'canal');
        $subcanal = \filter_input(INPUT_POST, 'subcanal');
        $tipo = \filter_input(INPUT_POST, 'type');
        $tiposegmento = strtoupper($tipo);
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val))?true:false;
        $subcanal0 = new distribucion_segmentos();
        $subcanal0->idempresa = $this->empresa->id;
        $subcanal0->codigo = $subcanal;
        $subcanal0->codigo_padre = $canal;
        $subcanal0->descripcion = $descripcion;
        $subcanal0->tiposegmento = $tiposegmento;
        $subcanal0->estado = $estado;
        $subcanal0->usuario_creacion = $this->user->nick;
        $subcanal0->fecha_creacion = \Date('d-m-Y H:i:s');
        if($accion=='eliminar'){
            $subcanal0->delete();
            $this->new_message("Subcanal $subcanal0->codigo $subcanal0->descripcion eliminado correctamente y liberados los clientes.");
        }else{
            if($subcanal0->save()){
                $this->new_message("Subcanal $subcanal0->codigo $subcanal0->descripcion tratado correctamente.");
            }else{
                $this->new_error_msg("¡Imposible tratar los datos ingresados!");
            }
        }
    }
    
    public function url(){
        if($this->segmento){
            return parent::url()."&segmento=".$this->segmento;
        }else{
            return partent::url();
        }
    }
    
    public function shared_extensions(){
        
    }
}
