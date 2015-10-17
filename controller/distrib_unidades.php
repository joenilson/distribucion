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
require_model('distribucion_unidades.php');

/**
 * Description of distribucion_unidades
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_unidades extends fs_controller {
    public $almacen;
    public $pais;
    public $agencia_transporte;
    public $distribucion_tipounidad;
    public $distribucion_unidades;
    public $listado;
    
    public function __construct() {
        parent::__construct(__CLASS__, '2 - Unidades de Transporte', 'distribucion');
    }
    
    public function private_core(){
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->almacen = new almacen();
        $this->agencia_transporte = new agencia_transporte();
        $this->distribucion_tipounidad = new distribucion_tipounidad();
        $this->distribucion_unidades = new distribucion_unidades();
        $delete = \filter_input(INPUT_GET, 'delete');
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
        $codtrans = \filter_input(INPUT_POST, 'codtrans');
        $placa_val = \filter_input(INPUT_POST, 'placa');
        $capacidad = \filter_input(INPUT_POST, 'capacidad');
        $tipounidad = \filter_input(INPUT_POST, 'tipounidad');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val))?true:false;
        $placa = (!empty($delete))?$delete:$placa_val;
        
        $unidad = new distribucion_unidades();
        $unidad->idempresa = $this->empresa->id;
        $unidad->placa = trim(strtoupper($placa));
        $unidad->codalmacen = $codalmacen;
        $unidad->codtrans = (string) $codtrans;
        $unidad->tipounidad = (int) $tipounidad;
        $unidad->capacidad = (int) $capacidad;
        $unidad->estado = $estado;
        $unidad->usuario_creacion = $this->user->nick;
        $unidad->fecha_creacion = \Date('d-m-Y H:i:s');
        $condicion = (!empty($delete))?'delete':'update';
        $valor = (!empty($delete))?$delete:$placa;
        if($valor){
            $this->tratar_unidad($valor,$condicion, $unidad);
        }
        
        $this->listado = $this->distribucion_unidades->all($this->empresa->id);
    }
    
    private function tratar_unidad($valor, $condicion, $unidad){
        $unidad->usuario_modificacion = $this->user->nick;
        $unidad->fecha_modificacion = \Date('d-m-Y H:i:s');
        if($condicion == 'delete'){
            $unidad->placa = $valor;
            if($unidad->delete()){
                $this->new_message("Unidad con placa ".$unidad->placa." eliminada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos de la unidad!");
            }
        }elseif($condicion == 'update'){
            if($unidad->save()){
                $this->new_message("Unidad ".$unidad->placa." tratada correctamente.");
            } else {
                $this->new_error_msg("¡Imposible tratar los datos de la unidad ".$unidad->placa."!");
            }
        }
    }
}
