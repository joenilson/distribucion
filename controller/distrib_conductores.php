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
require_model('distribucion_conductores.php');
/**
 * Description of distribucion_conductores
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_conductores extends fs_controller {
    
    public $almacen;
    public $pais;
    public $agencia_transporte;
    public $distribucion_conductores;
    public $listado;
    
    public function __construct() {
        parent::__construct(__CLASS__, '3 - Conductores', 'distribucion');
    }
    
    public function private_core(){
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->almacen = new almacen();
        $this->agencia_transporte = new agencia_transporte();
        $this->distribucion_conductores = new distribucion_conductores();
        $delete = \filter_input(INPUT_GET, 'delete');
        $codalmacen = \filter_input(INPUT_POST, 'codalmacen');
        $codtrans = \filter_input(INPUT_POST, 'codtrans');
        $nombre = \filter_input(INPUT_POST, 'nombre');
        $licencia_val = \filter_input(INPUT_POST, 'licencia');
        $tipolicencia = \filter_input(INPUT_POST, 'tipolicencia');
        $estado_val = \filter_input(INPUT_POST, 'estado');
        $estado = (isset($estado_val))?true:false;
        $licencia = (!empty($delete))?$delete:$licencia_val;
        
        $conductor = new distribucion_conductores();
        $conductor->idempresa = $this->empresa->id;
        $conductor->codalmacen = $codalmacen;
        $conductor->codtrans = (string) $codtrans;
        $conductor->nombre = (string) trim(strtoupper($nombre));
        $conductor->licencia = (string) trim(strtoupper($licencia));
        $conductor->tipolicencia = (string) trim(strtoupper($tipolicencia));
        $conductor->estado = $estado;
        $conductor->usuario_creacion = $this->user->nick;
        $conductor->fecha_creacion = \Date('d-m-Y H:i:s');
        $condicion = (!empty($delete))?'delete':'update';
        $valor = (!empty($delete))?$delete:$licencia;
        if($valor){
            $this->tratar_conductor($valor,$condicion, $conductor);
        }
        
        $this->listado = $this->distribucion_conductores->all($this->empresa->id);
    }
    
    private function tratar_conductor($valor, $condicion, $conductor){
        $conductor->usuario_modificacion = $this->user->nick;
        $conductor->fecha_modificacion = \Date('d-m-Y H:i:s');
        if($condicion == 'delete'){
            $conductor->licencia = $valor;
            if($conductor->delete()){
                $this->new_message("Conductor ".$conductor->nombre." con licencia ".$conductor->licencia." eliminado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible eliminar los datos del conductor!");
            }
        }elseif($condicion == 'update'){
            if($conductor->save()){
                $this->new_message("Conductor ".$conductor->nombre." tratado correctamente.");
            } else {
                $this->new_error_msg("¡Imposible tratar los datos del conductor ".$conductor->nombre." - ".$conductor->licencia."!");
            }
        }
    }
}
