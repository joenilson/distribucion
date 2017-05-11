<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/facturacion_base/model/core/grupo_clientes.php';

/**
 * Description of grupo_clientes
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class grupo_clientes extends FacturaScripts\model\grupo_clientes
{
    public function cantidad_clientes_total()
    {
        $cantidad = 0;
        $sql = "SELECT count(c.codcliente) as cantidad FROM ".$this->table_name." as t1 ".
            " JOIN clientes as c ON (t1.codgrupo = c.codgrupo) ".
            " WHERE t1.codgrupo = ".$this->var2str($this->codgrupo);
        $data = $this->db->select($sql);
        if($data)
        {
            $cantidad = $data[0]['cantidad'];
        }
        return $cantidad;
    }
    
    public function cantidad_clientes_singrupo()
    {
        $cantidad = 0;
        $sql = "SELECT count(c.codcliente) as cantidad FROM clientes as c WHERE c.codgrupo IS NULL;";
        $data = $this->db->select($sql);
        if($data)
        {
            $cantidad = $data[0]['cantidad'];
        }
        return $cantidad;
    }
    
    public function cantidad_clientes_activos()
    {
        $cantidad = 0;
        $sql = "SELECT count(c.codcliente) as cantidad FROM ".$this->table_name." as t1 ".
            " JOIN clientes as c ON (t1.codgrupo = c.codgrupo) ".
            " WHERE t1.codgrupo = ".$this->var2str($this->codgrupo).
            " AND c.fechabaja IS NULL";
        $data = $this->db->select($sql);
        if($data)
        {
            $cantidad = $data[0]['cantidad'];
        }
        return $cantidad;
    }
    
    public function cantidad_clientes_inactivos()
    {
        $cantidad = 0;
        $sql = "SELECT count(c.codcliente) as cantidad FROM ".$this->table_name." as t1 ".
            " JOIN clientes as c ON (t1.codgrupo = c.codgrupo) ".
            " WHERE t1.codgrupo = ".$this->var2str($this->codgrupo).
            " AND c.fechabaja IS NOT NULL";
        $data = $this->db->select($sql);
        if($data)
        {
            $cantidad = $data[0]['cantidad'];
        }
        return $cantidad;
    }
}