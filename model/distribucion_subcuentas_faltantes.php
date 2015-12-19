<?php

/*
 * Copyright (C) 2015 darkniisan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
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

/**
 * Description of distribucion_subcuentas_faltantes
 *
 * @author darkniisan
 */
class distribucion_subcuentas_faltantes extends fs_model {
    public $idempresa;
    public $id;
    public $idsubcuenta;
    public $codsubcuenta;
    public $codejercicio;
    public $conductor;
    public function __construct($t = false) {
        parent::__construct('distribucion_subcuentas_faltantes','plugins/distribucion/');
        if($t){
            $this->idempresa = $t['idempresa'];
            $this->id = $t['id'];
            $this->idsubcuenta = $t['idsubcuenta'];
            $this->codsubcuenta = $t['codsubcuenta'];
            $this->codejercicio = $t['codejercicio'];
            $this->conductor = $t['conductor'];
        }else{
            $this->idempresa = null;
            $this->id = null;
            $this->idsubcuenta = null;
            $this->codsubcuenta = null;
            $this->codejercicio = null;
            $this->conductor = null;
        }
    }
    
    protected function install() {
        return '';
    }
    
    public function exists()
    {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else{
          return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
      }
         
    }
    
    public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET conductor = ".$this->var2str($this->conductor).",
            codsubcuenta = ".$this->var2str($this->codsubcuenta).",
            codejercicio = ".$this->var2str($this->codejercicio).",
            idsubcuenta = ".$this->var2str($this->idsubcuenta)."
            idempresa = ".$this->var2str($this->idempresa)."
            WHERE id = ".$this->var2str($this->id).";";
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (idempresa,conductor,codsubcuenta,codejercicio,idsubcuenta)
            VALUES (".$this->var2str($this->idempresa).",".$this->var2str($this->conductor).",".$this->var2str($this->codsubcuenta).",
            ".$this->var2str($this->codejercicio).",".$this->var2str($this->idsubcuenta).");";
         $resultado = $this->db->exec($sql);
         if($resultado)
         {
            $newid = $this->db->lastval();
            if($newid){
               $this->id = intval($newid);
            }
         }
         return $resultado;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
    
   public function all_from_conductor($cod)
   {
      $sublist = array();
      $subcs = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE conductor = ".$this->var2str($cod)." ORDER BY codejercicio DESC;");
      if($subcs)
      {
         foreach($subcs as $s){
            $sublist[] = new distribucion_subcuentas_faltantes($s);
         }
      }
      return $sublist;
   }
   
   public function get_subcuenta()
   {
      $subc = new subcuenta();
      return $subc->get($this->idsubcuenta);
   }
   
   public function get($cli, $idsc)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE conductor = ".$this->var2str($cli)."
         AND idsubcuenta = ".$this->var2str($idsc).";");
      if($data)
         return new distribucion_subcuentas_faltantes($data[0]);
      else
         return FALSE;
   }
    
}
