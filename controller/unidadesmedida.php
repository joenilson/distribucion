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
 * Description of unidadesmedida
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class unidadesmedida extends fs_controller {

    public function __construct() {
        parent::__construct(__CLASS__, 'Unidades de Medida', 'ventas', FALSE, FALSE, TRUE);
    }

    protected function private_core() {
        $this->shared_extensions();
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
