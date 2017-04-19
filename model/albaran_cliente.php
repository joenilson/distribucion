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
require_once 'plugins/facturacion_base/model/core/albaran_cliente.php';
/**
 * Description of albaran_cliente
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class albaran_cliente extends FacturaScripts\model\albaran_cliente{
    /**
     * El codigo de la ruta de atención
     * @var type varchar(10)
     */
    public $codruta;
    public function __construct($t = FALSE) {
        if($t){
            $this->codruta = $t['codruta'];
        }else{
            $this->codruta = null;
        }
        parent::__construct($t);
    }
    
    /**
    * Guarda los datos en la base de datos
    * @return boolean
    */
   public function save()
   {
      if(parent::save()){
         $sql = "UPDATE ".$this->table_name." SET codruta = ".$this->var2str($this->codruta).
            " WHERE idalbaran = ".$this->intval($this->idalbaran).";";
         return $this->db->exec($sql);
      }
   }
}
