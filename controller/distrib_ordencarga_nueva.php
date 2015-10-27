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
require_once 'distrib_ordencarga.php';
/**
 * Description of distrib_ordencarga_nueva
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_ordencarga_nueva extends fs_controller {   
    public $nueva_carga;
    public $delete_carga;
    public $imprime_carga;
    public $ordencarga;
    
    public function __construct() {
        parent::__construct(__CLASS__, '6 - Orden de Carga Nueva', 'distribucion');
    }
    
    public function private_core()
    {
        $this->ordencarga = new distrib_ordencarga();
        $this->nueva_carga = $this->ordencarga->nueva_carga();
        $this->delete_carga = $this->ordencarga->delete_carga(1);
        $this->imprime_carga = $this->ordencarga->imprime_carga(4);
    }
}
