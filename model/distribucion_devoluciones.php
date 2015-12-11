<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
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
 * Agregado para buscar por la devolucion de una factura
 */
class distribucion_devoluciones extends factura_cliente {

    public function get_devolucion($factura) {
        $devolucion = $this->db->select("SELECT idfactura FROM facturascli WHERE idfacturarect = " . $factura . ";");
        if ($devolucion) {
            $datos = $this->get($devolucion[0]['idfactura']);
            return $datos;
        } else
            return FALSE;
    }

}
