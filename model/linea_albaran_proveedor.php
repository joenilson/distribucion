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
require_once 'plugins/facturacion_base/model/core/linea_albaran_proveedor.php';
/**
 * Description of linea_albaran_cliente
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class linea_albaran_proveedor extends FacturaScripts\model\linea_albaran_proveedor{
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
      if(parent::save()){
         $sql = "UPDATE ".$this->table_name." SET ".
              "cantidad_um = ".$this->var2str($this->cantidad_um).
              ",codum = ".$this->var2str($this->codum).
            " WHERE idlinea = ".$this->intval($this->idlinea).";";
         return $this->db->exec($sql);
      }
   }
}
