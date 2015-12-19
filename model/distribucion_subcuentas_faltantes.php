<?php

/*
 * Copyright (C) 2015 darkniisan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of distribucion_subcuentas_faltantes
 *
 * @author darkniisan
 */
class distribucion_subcuentas_faltantes extends fs_model{
    public $idempresa;
    public $id;
    public $idsubcuenta;
    public $codsubcuenta;
    public $ejercicio;
    public $conductor;
    public function __construct($t = false) {
        parent::__construct('distribucion_subcuentas_faltantes','plugins/distribucion/');
        if($t){
            $this->idempresa = $t['idempresa'];
            $this->id = $t['id'];
            $this->idsubcuenta = $t['idsubcuenta'];
            $this->codsubcuenta = $t['codsubcuenta'];
            $this->ejercicio = $t['ejercicio'];
            $this->conductor = $t['conductor'];
        }else{
            $this->idempresa = null;
            $this->id = null;
            $this->idsubcuenta = null;
            $this->codsubcuenta = null;
            $this->ejercicio = null;
            $this->conductor = null;
        }
    }
    
    protected function install() {
        ;
    }
    
    public function exists() {
        ;
    }
    
    public function delete() {
        ;
    }
    
    public function save() {
        ;
    }
    
    public function all_subcuentas_conductor($conductor){
        
    }
    
    public function get_subcuenta(){
        
    }
}
