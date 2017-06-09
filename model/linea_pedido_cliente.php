<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2016    Carlos Garcia Gomez        neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/presupuestos_y_pedidos/model/core/linea_pedido_cliente.php';

class linea_pedido_cliente extends FacturaScripts\model\linea_pedido_cliente
{
    /**
     * Especifica si la linea es de Venta "V" o de Oferta "O", 
     * @var type char(1)
     */
    public $posicion;
    /**
     * La cantidad en la unidad de medida destino
     * @var type integer
     */
    public $cantidad_um;
    /**
     * La unidad de medida destino
     * @var type varchar(10)
     */
    public $codum;   
    public function __construct($t = FALSE) {
       if($t){
           $this->cantidad_um = $t['cantidad_um'];
           $this->codum = $t['codum'];
       }else{
           $this->cantidad_um = 0;
           $this->codum = NULL;
       }
       parent::__construct($t);
   }
   
   public function save()
   {
      if(parent::save()){
         $sql = "UPDATE ".$this->table_name." SET ".
              "cantidad_um = ".$this->var2str($this->cantidad_um).
              ",codum = ".$this->var2str($this->codum).
            " WHERE idlinea = ".$this->intval($this->idlinea).";";
         return $this->db->exec($sql);
      }
   }
}
