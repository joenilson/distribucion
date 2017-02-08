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
     * Codum es la abreviatura de la unidad de medida
     * Se puede colocar CAJAx12, PAQUETE20
     * CAJA100, debe ser lo más descriptiva posible
     * @var type varchar(10)
     */
    public $codum;
    /**
     * Nombre de la Unidad de medida
     * @var type varchar(60)
     */
    public $nombre;
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
             $this->codum = $t['codum'];
             $this->nombre = $t['nombre'];
             $this->cantidad = floatval($t['cantidad']);
         }else{
             $this->codum = NULL;
             $this->nombre = NULL;
             $this->cantidad = NULL;
         }
    }

    protected function install() {
        return '';
    }

    public function all(){
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY codum;";
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

    public function get($codum){
        $sql = "SELECT * FROM ".$this->table_name." WHERE codum = ".$this->var2str($codum).";";
        $data = $this->db->select($sql);
        if($data){
            return new unidadmedida($data[0]);
        }else{
            return false;
        }
    }

    public function exists() {
        if(!$this->get($this->codum)){
            return false;
        }else{
            return $this->get($this->codum);
        }
    }
    
    public function en_uso(){
        $sql = "SELECT count(codum) as cantidad from articulo_unidadmedida where codum = ".$this->var2str($this->codum).";";
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
                    " nombre = ".$this->var2str($this->nombre).
                    " WHERE ".
                    " codum = ".$this->var2str($this->codum).";";
        }else{
            $sql = "INSERT INTO ".$this->table_name." (codum, nombre, cantidad) VALUES (".
                    $this->var2str($this->codum).", ".
                    $this->var2str($this->nombre).", ".
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
        $sql = "DELETE FROM ".$this->table_name." WHERE codum = ".$this->var2str($this->codum).";";
        $data = $this->db->exec($sql);
        if($data){
            return true;
        }else{
            return false;
        }
    }
}
