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
    public $listado;

    public function __construct() {
        parent::__construct(__CLASS__, '1 - Configuración', 'distribucion');
    }

    public function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $delete = \filter_input(INPUT_GET, 'delete');
        $id_val = \filter_input(INPUT_POST, 'id');
        $descripcion = \filter_input(INPUT_POST, 'descripcion');
        $estado_val = \filter_input(INPUT_POST, 'estado');
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
        $this->distribucion_tipounidad = new distribucion_tipounidad();
        $this->listado = $this->distribucion_tipounidad->all($this->empresa->id);
    }

    private function tratar_tipounidad($valor, $condicion, $descripcion, $estado) {
        $dtu = new distribucion_tipounidad();
        $dtu->id = $valor;
        $dtu->idempresa = $this->empresa->id;
        $dtu->descripcion = $descripcion;
        $dtu->estado = $estado;
        $dtu->usuario_modificacion = $this->user->nick;
        $dtu->fecha_modificacion = \Date('d-m-Y H:i:s');
        if ($condicion == 'delete') {
            if ($dtu->delete()) {
                $this->new_message("Tipo de Unidad " . $dtu->descripcion . " con el id " . $dtu->id . " eliminada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos del tipo de unidad!");
            }
        } elseif ($condicion == 'update') {
            if ($dtu->save()) {
                $this->new_message("Tipo de Unidad " . $dtu->descripcion . " con el id " . $dtu->id . " actualizada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible actualizar los datos del tipo de unidad!");
            }
        }
    }



}
