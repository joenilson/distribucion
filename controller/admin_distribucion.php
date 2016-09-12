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

/**
 * Description of admin_distribucion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class admin_distribucion extends fs_controller {

    public $distribucion_tipounidad;
    public $distribucion_tiporuta;
    public $distribucion_tipovendedor;
    public $listado_tipo_transporte;
    public $listado_tipo_ruta;
    public $listado_tipo_vendedor;
    public $familia;
    public $type;

    public function __construct() {
        parent::__construct(__CLASS__, '1 - Configuración', 'distribucion');
    }

    public function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->distribucion_tipounidad = new distribucion_tipounidad();

        $type_p = \filter_input(INPUT_POST, 'type');
        $type_g = \filter_input(INPUT_GET, 'type');
        $type = (isset($type_p)) ? $type_p : $type_g;
        $this->type = $type;
        if ($type == 'tipo_transporte') {
            $this->tratar_tipounidad();
        } elseif ($type == 'tipo_vendedor') {
            //$this->tratar_vendedor();
        } elseif ($type == 'tipo_ruta') {
            //$this->tratar_ruta();
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

        $this->listado_tipo_transporte = $this->distribucion_tipounidad->all($this->empresa->id);
        $this->listado_tipo_ruta = array();
        $this->listado_tipo_vendedor = array();

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

}
