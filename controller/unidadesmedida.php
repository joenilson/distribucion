<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
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
require_model('unidadmedida.php');
/**
 * Description of unidadesmedida
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class unidadesmedida extends fs_controller {
    public $allow_delete;
    public $unidadmedida;
    public function __construct() {
        parent::__construct(__CLASS__, 'Unidades de Medida', 'ventas', FALSE, FALSE, TRUE);
    }

    protected function private_core() {
        $this->unidadmedida = new unidadmedida();
        
        $this->allow_delete = ($this->user->admin)?TRUE:$this->user->allow_delete_on(__CLASS__);
        
        $this->shared_extensions();
        $accion = filter_input(INPUT_POST, 'accion');
        if($accion=='agregar'){
            $codum = filter_input(INPUT_POST, 'codum');
            $nombre = filter_input(INPUT_POST, 'nombre');
            $cantidad = filter_input(INPUT_POST, 'cantidad');
            $um0 = new unidadmedida();
            $um0->codum = $codum;
            $um0->nombre = $this->clearText($nombre);
            $um0->cantidad = floatval($cantidad);
            if($um0->save()){
                $this->new_message('¡Unidad de medida agregada con exito, ya puede utilizarla en los artículos!');
            }else{
                $this->new_error_msg('Ocurrio un error al agregar la Unidad de Medida, revise la información que agregó');
            }
        }elseif($accion=="eliminar"){
            $codum = filter_input(INPUT_POST, 'codum');
            $item = $this->unidadmedida->get($codum);
            if($item){
                if($item->delete()){
                    $this->new_message('¡Unidad de medida eliminada con exito!');
                }else{
                    $this->new_error_msg('Ocurrio un error al eliminar la Unidad de Medida.');
                }
            }else{
                $this->new_error_msg('No se encuentra la unidad de medida a eliminar, revise la información enviada.');
            }
        }
    }
    
    public function clearText($text){
        return htmlentities(strtoupper(trim($text)));
    }

    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => 'articulos_unidadesmedida',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_articulos',
                'type' => 'button',
                'text' => '<span class="fa fa-cubes"></span>&nbsp; Unidades de Medida',
                'params' => ''
            )
        );
        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
}
