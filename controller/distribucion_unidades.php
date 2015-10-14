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

/**
 * Description of distribucion_unidades
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distribucion_unidades extends fs_controller {
    public $almacen;
    public $pais;
    public $agencia_transporte;
    
    public function __construct() {
        parent::__construct(__CLASS__, '2 - Unidades de Transporte', 'distribucion');
    }
    
    public function private_core(){
        $this->almacen = new almacen();
        $this->agencia_transporte = new agencia_transporte();
        
    }
}
