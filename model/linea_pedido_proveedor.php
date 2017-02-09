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

require_once 'plugins/presupuestos_y_pedidos/model/core/linea_pedido_proveedor.php';

class linea_pedido_proveedor extends FacturaScripts\model\linea_pedido_proveedor
{
   public $cantidad_um;
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
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET cantidad = ".$this->var2str($this->cantidad)
                    .", cantidad_um = ".$this->var2str($this->cantidad_um)
                    .", codum = ".$this->var2str($this->codum)
                    .", codimpuesto = ".$this->var2str($this->codimpuesto)
                    .", descripcion = ".$this->var2str($this->descripcion)
                    .", dtopor = ".$this->var2str($this->dtopor)
                    .", idpedido = ".$this->var2str($this->idpedido)
                    .", idlineapresupuesto = ".$this->var2str($this->idlineapresupuesto)
                    .", idpresupuesto = ".$this->var2str($this->idpresupuesto)
                    .", irpf = ".$this->var2str($this->irpf)
                    .", iva = ".$this->var2str($this->iva)
                    .", pvpsindto = ".$this->var2str($this->pvpsindto)
                    .", pvptotal = ".$this->var2str($this->pvptotal)
                    .", pvpunitario = ".$this->var2str($this->pvpunitario)
                    .", recargo = ".$this->var2str($this->recargo)
                    .", referencia = ".$this->var2str($this->referencia)
                    .", orden = ".$this->var2str($this->orden)
                    .", mostrar_cantidad = ".$this->var2str($this->mostrar_cantidad)
                    .", mostrar_precio = ".$this->var2str($this->mostrar_precio)
                    ."  WHERE idlinea = ".$this->var2str($this->idlinea).";";

            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (cantidad,cantidad_um,codum,codimpuesto,descripcion,dtopor,
               idpedido,irpf,iva,pvpsindto,pvptotal,pvpunitario,recargo,referencia) VALUES (".$this->var2str($this->cantidad)
                    .",".$this->var2str($this->cantidad_um)
                    .",".$this->var2str($this->codum)
                    .",".$this->var2str($this->codimpuesto)
                    .",".$this->var2str($this->descripcion)
                    .",".$this->var2str($this->dtopor)
                    .",".$this->var2str($this->idpedido)
                    .",".$this->var2str($this->irpf)
                    .",".$this->var2str($this->iva)
                    .",".$this->var2str($this->pvpsindto)
                    .",".$this->var2str($this->pvptotal)
                    .",".$this->var2str($this->pvpunitario)
                    .",".$this->var2str($this->recargo)
                    .",".$this->var2str($this->referencia).");";

            if( $this->db->exec($sql) )
            {
               $this->idlinea = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
}
