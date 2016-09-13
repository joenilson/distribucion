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
require_model('almacen.php');
require_model('pais.php');
require_model('agencia_transporte.php');
require_model('distribucion_tipounidad.php');
require_model('distribucion_tiporuta.php');
require_model('distribucion_tipovendedor.php');

/**
 * Description of admin_distribucion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class admin_distribucion extends fs_controller {

    public $cargos_disponibles;
    public $distribucion_tipounidad;
    public $distribucion_tiporuta;
    public $distribucion_tipovendedor;
    public $listado_tipo_transporte;
    public $listado_tipo_ruta;
    public $listado_tipo_vendedor;
    public $familia;
    public $type;
    public $nomina;
    public function __construct() {
        parent::__construct(__CLASS__, '1 - Configuración', 'distribucion');
    }

    public function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        
        /*
         * Buscamos si está el plugin de nomina para la busqueda de los cargos de Supervisor y Vendedor
         */
        $this->nomina = in_array('nomina',$GLOBALS['plugins']);
        
        $this->distribucion_tipounidad = new distribucion_tipounidad();
        $this->distribucion_tiporuta = new distribucion_tiporuta();
        $this->distribucion_tipovendedor = new distribucion_tipovendedor();
        $type_p = \filter_input(INPUT_POST, 'type');
        $type_g = \filter_input(INPUT_GET, 'type');
        $type = (isset($type_p)) ? $type_p : $type_g;
        $this->type = $type;
        if ($type == 'tipo_transporte') {
            $this->tratar_tipounidad();
        } elseif ($type == 'tipo_vendedor') {
            $this->tratar_tipovendedor();
        } elseif ($type == 'tipo_ruta') {
            $this->tratar_tiporuta();
        }
        /*
        $estado = (isset($estado_val)) ? true : false;
        $id = (!empty($delete)) ? $delete : $id_val;
        if (isset($id)) {
            $condicion = (!empty($delete)) ? 'delete' : 'update';
            $valor = (!empty($delete)) ? $delete : $id;
            $this->tratar_tipounidad($valor, $condicion, $descripcion, $estado);
        } elseif (isset($descripcion) and isset($estado)) {
            $tipounidad = new distribucion_tipounidad();
            $tipounidad->id = $id;
            $tipounidad->idempresa = $this->empresa->id;
            $tipounidad->descripcion = ucwords(strtolower($descripcion));
            $tipounidad->estado = $estado;
            $tipounidad->usuario_creacion = $this->user->nick;
            $tipounidad->fecha_creacion = \Date('d-m-Y H:i:s');
            if ($tipounidad->save()) {
                $this->new_message("Tipo de Unidad " . $tipounidad->descripcion . " con el id " . $tipounidad->id . " guardada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible guardar los datos del tipo de unidad!");
            }
        }
        */
        
        $this->cargos_disponibles = $this->listado_cargos_disponibles();

        $this->listado_tipo_transporte = $this->distribucion_tipounidad->all($this->empresa->id);
        $this->listado_tipo_ruta = $this->distribucion_tiporuta->all();
        $this->listado_tipo_vendedor = $this->distribucion_tipovendedor->all();

    }

    private function tratar_tipounidad() {
        $delete = \filter_input(INPUT_POST, 'delete');
        $id = \filter_input(INPUT_POST, 'id');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val)) ? true : false;
        $dtu = new distribucion_tipounidad();
        $dtu->id = $id;
        $dtu->idempresa = $this->empresa->id;
        $dtu->descripcion = $descripcion;
        $dtu->estado = $estado;
        $dtu->idempresa = $this->empresa->id;
        $dtu->descripcion = ucwords(strtolower($descripcion));
        $dtu->estado = $estado;
        $dtu->usuario_creacion = $this->user->nick;
        $dtu->fecha_creacion = \Date('d-m-Y H:i:s');
        if (isset($delete)) {
            if ($dtu->delete()) {
                $this->new_message("Tipo de Unidad " . $dtu->descripcion . " con el id " . $dtu->id . " eliminada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos del tipo de unidad!");
            }
        } else {
            if(!empty($id)){
                $dtu->usuario_modificacion = $this->user->nick;
                $dtu->fecha_modificacion = \Date('d-m-Y H:i:s');
            }
            if ($dtu->save()) {
                $this->new_message("Tipo de Unidad " . $dtu->descripcion . " con el id " . $dtu->id . " guardada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible actualizar los datos del tipo de unidad!");
            }
        }
    }
    
    public function tratar_tiporuta(){
        $delete = \filter_input(INPUT_POST, 'delete');
        $id = \filter_input(INPUT_POST, 'id');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val)) ? true : false;
        $dtr = new distribucion_tiporuta();
        $dtr->id = $id;
        $dtr->descripcion = $descripcion;
        $dtr->estado = $estado;
        $dtr->idempresa = $this->empresa->id;
        $dtr->descripcion = strtoupper($descripcion);
        $dtr->estado = $estado;
        $dtr->usuario_creacion = $this->user->nick;
        $dtr->fecha_creacion = \Date('d-m-Y H:i:s');
        if (isset($delete)) {
            if ($dtr->delete()) {
                $this->new_message("Tipo de Ruta " . $dtr->descripcion . " con el id " . $dtr->id . " eliminada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos del tipo de ruta!");
            }
        } else {
            if(!empty($id)){
                $dtr->usuario_modificacion = $this->user->nick;
                $dtr->fecha_modificacion = \Date('d-m-Y H:i:s');
            }
            if ($dtr->save()) {
                $this->new_message("Tipo de Ruta " . $dtr->descripcion . " con el id " . $dtr->id . " guardada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible actualizar los datos del tipo de ruta!");
            }
        }   
    }
    
    public function tratar_tipovendedor(){
        $delete = \filter_input(INPUT_POST, 'delete');
        $id = \filter_input(INPUT_POST, 'id');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val)) ? true : false;
        $dtv = new distribucion_tipovendedor();
        $dtv->id = $id;
        $dtv->descripcion = $descripcion;
        $dtv->estado = $estado;
        $dtv->idempresa = $this->empresa->id;
        $dtv->descripcion = strtoupper($descripcion);
        $dtv->estado = $estado;
        $dtv->usuario_creacion = $this->user->nick;
        $dtv->fecha_creacion = \Date('d-m-Y H:i:s');
        if (isset($delete)) {
            if ($dtv->delete()) {
                $this->new_message("Tipo de Vendedor " . $dtv->descripcion . " con el id " . $dtv->id . " eliminado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos del tipo de vendedor!");
            }
        } else {
            if(!empty($id)){
                $dtv->usuario_modificacion = $this->user->nick;
                $dtv->fecha_modificacion = \Date('d-m-Y H:i:s');
            }
            if ($dtv->save()) {
                $this->new_message("Tipo de Vendedor " . $dtv->descripcion . " con el id " . $dtv->id . " guardado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible actualizar los datos del tipo de vendedor!");
            }
        }
    }
    
    public function listado_cargos_disponibles(){
        $listado = array();
        if($this->nomina){
            require_model('cargos.php');
            $cargos = new cargos();
            $listado = $cargos->all();
        }
        return $listado;
    }

}
