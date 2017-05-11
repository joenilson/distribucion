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
    
    /**
     * Se genera el listado de clientes visitados por ruta
     * es opcional las fechas desde y hasta y el campo ruta
     * @param type $codalmacen
     * @param type $codruta
     * @param type $desde
     * @param type $hasta
     * @return type integer
     */
    public function clientes_visitados($codalmacen, $codruta, $desde, $hasta)
    {
        $visitados = 0;
        $sql = "SELECT count(DISTINCT codcliente) as visitados FROM ".$this->table_name.
            " WHERE anulada = FALSE AND idfacturarect IS NULL ";
        
        if($codalmacen)
        {
            $sql .= " AND codalmacen = ".$this->var2str($codalmacen);
        }
        
        if($codruta)
        {
            $sql .= " AND codruta = ".$this->var2str($codruta);
        }
        
        if($desde)
        {
            $sql.= " AND fecha >= ".$this->var2str($desde);
        }
        
        if($hasta)
        {
            $sql.= " AND fecha <= ".$this->var2str($hasta);
        }
        $data = $this->db->select($sql);
        if($data)
        {
            $visitados = $data[0]['visitados'];
        }
        return $visitados; 
    }
    
    /**
     * Se generan las ventas y ofertas por ruta y en un rango de fechas 
     * @param type $codalmacen
     * @param type $codruta
     * @param type $desde
     * @param type $hasta
     * @return \stdClass array
     */
    public function ventas_ruta($codalmacen,$codruta, $desde, $hasta)
    {
        $lista = array();
        $sql = "SELECT codruta,fecha,sum(T2.cantidad) as qdad_vendida,sum(T2.pvptotal) as importe_vendido, sum(T3.cantidad) as qdad_oferta ".
            "FROM ".$this->table_name." AS T1 ".
            "LEFT JOIN lineasfacturascli as T2 ".
            "ON T1.idfactura = T2.idfactura AND T2.dtopor != 100".
            "LEFT JOIN lineasfacturascli as T3 ".
            "ON T1.idfactura = T3.idfactura AND T3.dtopor = 100".
            "WHERE fecha between ".$this->var2str($desde)." AND ".$this->var2str($hasta)." ".
            "AND codalmacen = ".$this->var2str($codalmacen)." and codruta = ".$this->var2str($codruta)." and anulada = FALSE ".
            "GROUP by codruta,fecha;";
        $data = $this->db->select($sql);
        if($data)
        {
            foreach($data as $d)
            {
                $item = new stdClass();
                $item->codruta = $d['codruta'];
                $item->fecha = $d['fecha'];
                $item->qdad_vendida = $d['qdad_vendida'];
                $item->importe_vendido = $d['importe_vendido'];
                $item->qdad_oferta = $d['qdad_oferta'];
                $lista[] = $item;
            }
        }
        return $lista;
    }
    
}
