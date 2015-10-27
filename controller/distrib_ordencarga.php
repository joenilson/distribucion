<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Affero General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

/**
 * Description of distribucion_creacion
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class distrib_ordencarga extends fs_controller {

    public function __construct() {
        parent::__construct(__CLASS__, '4 - Crear Orden de Carga', 'distribucion');
    }

    public function private_core() {
        $this->share_extensions();
    }
    
    public function total_pendientes(){
        return 10;
    }
    
    public function paginas(){
        return 10;
    }

    public function nueva_carga() {
        return "Nueva Orden";
    }

    public function imprime_carga($id) {
        return "Imprime la orden " . $id;
    }

    public function delete_carga($id) {
        return "Elimina la orden " . $id;
    }
    
    private function share_extensions() {
        
        $fsext0 = new fs_extension(
            array(
                'name' => 'ordencarga_datepicker_es_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            )
        );
        $fsext0->save();
        
        $fsext1 = new fs_extension(
            array(
                'name' => 'ordencarga_jqueryui_js',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/jquery-ui.min.js"></script>',
                'params' => ''
            )
        );
        $fsext1->save();
        
        $fsext2 = new fs_extension(
            array(
                'name' => 'ordencarga_jqueryui_css1',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.min.css"/>',
                'params' => ''
            )
        );
        $fsext2->save();
        
        $fsext3 = new fs_extension(
            array(
                'name' => 'ordencarga_jqueryui_css2',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
                'params' => ''
            )
        );
        $fsext3->save();
        
        $fsext4 = new fs_extension(
            array(
                'name' => 'ordencarga_jqueryui_css3',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
                'params' => ''
            )
        );
        $fsext4->save();
        
        $fsext4 = new fs_extension(
            array(
                'name' => 'distribucion_css1',
                'page_from' => __CLASS__,
                'page_to' => 'distrib_ordencarga',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/distribucion.css"/>',
                'params' => ''
            )
        );
        $fsext4->save();
    }

}
