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
require_model('almacen.php');
require_model('distribucion_agente.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_rutas.php');
require_model('distribucion_tiporuta.php');
require_model('distribucion_clientes.php');
/**
 * Controlador para gestionar la información de las rutas
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distrib_rutas extends fs_controller{
    public $almacenes;
    public $codalmacen;
    public $codagente;
    public $supervisores;
    public $vendedores;
    public $supervisor;
    public $vendedor;
    public $vendedores_libres;
    public $vendedores_asignados;
    public $clientes;
    public $rutas;
    public $distribucion_agente;
    public $distribucion_clientes;
    public $distribucion_organizacion;
    public $distribucion_rutas;
    public $tiporuta;
    public $type;
    public $accion;
    public $busqueda;
    public function __construct() {
        parent::__construct(__CLASS__, 'Configuración de Rutas', 'distribucion', FALSE, FALSE, FALSE);
    }
    
    protected function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->shared_extensions();
        $this->almacen = new almacen();
        $this->agente = new distribucion_agente();
        $this->distribucion_organizacion = new distribucion_organizacion();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->tiporuta = new distribucion_tiporuta();
        $this->cliente = new cliente();
        
        $accion = \filter_input(INPUT_POST, 'accion');
        if($accion){
            $this->tratar_ruta($accion);
        }
        
        $codalmacen = \filter_input(INPUT_POST, 'b_codalmacen');
        $this->codalmacen = ($codalmacen)?$codalmacen:false;
        $codagente = \filter_input(INPUT_POST, 'b_codagente');
        $this->codagente = ($codagente)?$codagente:false;
        $busqueda = \filter_input(INPUT_POST, 'b_ruta');
        $this->busqueda = ($busqueda)?$busqueda:false;
        if($this->codalmacen){
            $this->vendedores_asignados = $this->distribucion_organizacion->all_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'VENDEDOR');
            $this->rutas = $this->distribucion_rutas->all_rutasporalmacen($this->empresa->id, $this->codalmacen);
        }else{
            $this->vendedores_asignados = $this->distribucion_organizacion->all_tipoagente($this->empresa->id, 'VENDEDOR');
            $this->rutas = $this->distribucion_rutas->all($this->empresa->id);
        }
        if($this->codagente){
            $datos_agente = $this->distribucion_organizacion->get($this->empresa->id, $this->codagente);
            if($datos_agente){
                $this->rutas = $this->distribucion_rutas->all_rutasporagente($this->empresa->id, $datos_agente->codalmacen, $datos_agente->codagente);
            }
        }
        if($this->busqueda){
            $this->rutas = $this->distribucion_rutas->search($this->codalmacen, $this->busqueda);
        }
    }
    
    public function tratar_ruta($accion){
        $codagente = \filter_input(INPUT_POST, 'codagente');
        $codruta = \filter_input(INPUT_POST, 'codruta');
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
        $ruta0 = new distribucion_rutas();
        $ruta0->idempresa = $this->empresa->id;
        $ruta0->codalmacen = $codalmacen;
        $ruta0->codagente = $codagente;
        $ruta0->codruta = $codruta;
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
        $ruta0->usuario_modificacion = $this->user->nick;
        $ruta0->fecha_creacion = \Date('d-m-Y H:i:s');
        $ruta0->fecha_modificacion = \Date('d-m-Y H:i:s');
        if($accion=='eliminar'){
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
    
    public function shared_extensions(){
        
    }
}
