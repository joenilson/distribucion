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
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('articulo.php');
require_model('unidadmedida.php');
require_model('articulo_unidadmedida.php');
/**
 * Description of articulo_unidadesmedida
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class articulo_unidadesmedida extends fs_controller {
    public $allow_delete;
    public $articulo;
    public $unidadmedida;
    public $articulo_unidadmedida;
    public $articulo_um_lista;
    public function __construct() {
        parent::__construct(__CLASS__, 'UM del Artículo', 'ventas', FALSE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->unidadmedida = new unidadmedida();
        $this->articulo_unidadmedida = new articulo_unidadmedida();
        $art0 = new articulo();
        //Mandamos los botones y tabs
        $this->shared_extensions();
        //Verificamos los accesos del usuario
        $this->allow_delete = ($this->user->admin)?TRUE:$this->user->allow_delete_on(__CLASS__);
        $this->articulo = FALSE;
        if( isset($_REQUEST['ref']) )
        {
            $this->articulo = $art0->get($_REQUEST['ref']);
        }

        $accion = filter_input(INPUT_POST, 'accion');
        if($accion == 'agregar'){
            $unidadmedida = filter_input(INPUT_POST, 'codum');
            $factor = filter_input(INPUT_POST, 'factor');
            $peso = filter_input(INPUT_POST, 'peso');
            $base = filter_input(INPUT_POST, 'base');
            $se_compra = filter_input(INPUT_POST, 'se_compra');
            $se_vende = filter_input(INPUT_POST, 'se_vende');
            $aum0 = new articulo_unidadmedida();
            $aum0->codum = $unidadmedida;
            $aum0->referencia = $this->articulo->referencia;
            $aum0->factor = floatval($factor);
            $aum0->peso = floatval($peso);
            $aum0->se_vende = ($se_vende)?TRUE:FALSE;
            $aum0->se_compra = ($se_compra)?TRUE:FALSE;
            $aum0->base = ($base)?TRUE:FALSE;
            if($aum0->save()){
                $this->new_message('¡Unidad de medida agregada correctamente!');
            }else{
                $this->new_error_msg('Ocurrio un error al tratar de agregar la unidad de medida, por favor revise los datos ingresados');
            }
        }elseif($accion=='eliminar'){
            $unidadmedida = filter_input(INPUT_POST, 'codum');
            $aum0 = $this->articulo_unidadmedida->getOne($unidadmedida, $this->articulo->referencia);
            if($aum0){
                if($aum0->delete()){
                    $this->new_message('¡Unidad de medida eliminada correctamente!');
                }else{
                    $this->new_error_msg('Ocurrio un error al tratar de eliminar la unidad de medida.');
                }
            }
        }

        if($this->articulo){
            $this->articulo_um_lista = $this->articulo_unidadmedida->get($this->articulo->referencia);
        }
    }

    public function url()
    {
        if($this->articulo){
            return 'index.php?page='.__CLASS__.'&ref='.$this->articulo->referencia;
        }else{
            return parent::url();
        }
    }

    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => 'articulo_unidadesmedida',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_articulo',
                'type' => 'tab',
                'text' => '<span class="fa fa-cubes"></span>&nbsp;<span class="hidden-xs">&nbsp; Unidades de Medida</span>',
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
