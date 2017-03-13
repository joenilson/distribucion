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
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('distribucion_rutas.php');
require_model('distribucion_clientes.php');
require_model('distribucion_ordenescarga_facturas.php');
/**
 * Description of distribucion_facturas
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distribucion_facturas extends factura_cliente {

    public function buscar_rutas($idempresa, $fecha, $codalmacen, $rutas){
        //Convertimos la lista de rutas en un array y luego en una cadena
        $char_rutas = implode("','", explode(',',$rutas));
        $sql = "SELECT ".$this->table_name.".*,nombrecliente,direccion,ruta ".
                "FROM ".$this->table_name.", distribucion_clientes ".
                "WHERE ".$this->table_name.".codcliente = distribucion_clientes.codcliente".
                " AND ".$this->table_name.".codalmacen = ".$this->var2str($codalmacen).
                " AND ".$this->table_name.".codalmacen = distribucion_clientes.codalmacen".
                " AND fecha = ".$this->var2str($fecha).
                " AND distribucion_clientes.ruta IN ('".$char_rutas."')".
                " AND anulada = false AND idfacturarect IS NULL".
                " ORDER BY ruta, nombrecliente ";
        $data = $this->db->select($sql);
        if($data){
            $distrib_ordenescarga_facturas = new distribucion_ordenescarga_facturas();
            $lista = array();
            foreach($data as $linea){
                $value = new factura_cliente($linea);
                $value->ruta = $linea['ruta'];
                //Si la factura ya esta en una orden de carga la quitamos del listado
                if(!$distrib_ordenescarga_facturas->get($idempresa, $value->idfactura, $value->codalmacen)){
                    $lista[] = $value;
                }
            }
            sort($lista);
            return $lista;
        }else{
            return false;
        }
        
    }
}
