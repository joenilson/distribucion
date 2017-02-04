<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
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
 * Description of unidadmedida
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class unidadmedida extends fs_model {
    /**
     * ID autogenerado de la unidad de medida
     * @var type integer
     */
    public $id;
    /**
     * Nombre de la Unidad de medida
     * @var type varchar(60)
     */
    public $name;
    /**
     * Abreviatura para la unidad de medida
     * @var type varchar(6)
     */
    public $abreviatura;
    /**
     * Cantidad base de la unidad de medida
     * se usará como referencia a la hora de agregar
     * esta unidad de medida a un artículo
     * @var type numeric(10,4)
     */
    public $cantidad;
    public function __construct($t = FALSE) {
         parent::__construct('unidadmedida','plugins/distribucion/');
         if($t){
             $this->id = $t['id'];
             $this->name = $t['name'];
             $this->abreviatura = $t['abreviatura'];
             $this->cantidad = floatval($t['cantidad']);
         }else{
             $this->id = NULL;
             $this->name = NULL;
             $this->abreviatura = NULL;
             $this->cantidad = NULL;
         }
    }

    protected function install() {
        return '';
    }

    public function all(){
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY id;";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $lista[] = new unidadmedida($d);
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function get($id){
        $sql = "SELECT * FROM ".$this->table_name." WHERE id = ".$this->intval($id).";";
        $data = $this->db->select($sql);
        if($data){
            return new unidadmedida($data[0]);
        }else{
            return false;
        }
    }

    public function exists() {
        if(is_null($this->id)){
            return false;
        }else{
            return $this->get($this->id);
        }
    }
    
    public function en_uso(){
        $sql = "SELECT count(id) as cantidad from articulo_unidadmedida where id = ".$this->id.";";
        $data = $this->db->select($sql);
        if($data){
            return $data[0]['cantidad']+0;
        }else{
            return false;
        }
        
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                    " cantidad = ".floatval($this->cantidad).", ".
                    " abreviatura = ".$this->var2str($this->abreviatura).", ".
                    " name = ".$this->var2str($this->name).
                    " WHERE ".
                    " id = ".$this->intval($this->id).";";
        }else{
            $sql = "INSERT INTO ".$this->table_name." (name, abreviatura, cantidad) VALUES (".
                    $this->var2str($this->name).", ".
                    $this->var2str($this->abreviatura).", ".
                    $this->var2str($this->cantidad).");";
        }
        $data = $this->db->exec($sql);
        if($data){
            return true;
        }else{
            return false;
        }

    }

    public function delete() {
        $sql = "DELETE FROM ".$this->table_name." WHERE id = ".$this->intval($this->id).";";
        $data = $this->db->exec($sql);
        if($data){
            return true;
        }else{
            return false;
        }
    }
}
