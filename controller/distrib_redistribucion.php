<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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
require_model('distribucion_clientes.php');
require_model('distribucion_rutas.php');
/**
 * Description of distrib_redistribucion
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distrib_redistribucion extends fs_controller{
    public $rutas;
    public $ruta_origen;
    public $ruta_destino;
    public $cliente;
    public $clientes_origen;
    public $clientes_destino;

    public function __construct() {
        parent::__construct(__CLASS__, '7 - Redistribucion Clientes', 'distribucion', TRUE, TRUE);
    }
    
    public function private_core(){
        
    }
    
}
