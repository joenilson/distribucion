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
require_model('distribucion_rutas.php');
require_model('distribucion_clientes.php');
/**
 * Description of distribucion_facturas
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distribucion_facturas extends factura_cliente {
    
    public function buscar_rutas($fecha, $codalmacen, $rutas){
        //Convertimos la lista de rutas en un array y luego en una cadena
        $char_rutas = implode("','", explode(',',$rutas));
        $sql = "SELECT idfactura,codigo,numero2,".$this->table_name.".codcliente,nombrecliente,direccion,codalmacen, ruta ".
                "FROM ".$this->table_name.", distribucion_clientes ".
                "WHERE ".$this->table_name.".codcliente = distribucion_clientes.codcliente".
                " AND ".$this->table_name.".codalmacen = ".$this->var2str($codalmacen).
                " AND fecha = ".$this->var2str($fecha).
                " AND distribucion_clientes.ruta IN ('".$char_rutas."')".
                " AND anulada = false AND idfacturarect = NULL".
                " ORDER BY ruta, nombrecliente ";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $linea){
                $value = new factura_cliente($linea);
                $value->ruta = $linea['ruta'];
                $lista[] = $value;
            }
            return $lista;
        }else{
            return false;
        }
        
    }
}
