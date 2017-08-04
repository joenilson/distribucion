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
require_model('distribucion_conductores.php');
require_once 'plugins/distribucion/vendors/FacturaScripts/Seguridad/SeguridadUsuario.php';
use FacturaScripts\Seguridad\SeguridadUsuario;

/**
 * Description of distribucion_controller
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class distribucion_controller extends fs_controller 
{

    /**
     * TRUE si el usuario tiene permisos para eliminar en la página.
     * @var type
     */
    public $allow_delete;

    /**
     * TRUE si hay más de un almacén.
     * @var type
     */
    public $multi_almacen;
    public $meses = array(1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Setiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre');
    public $tesoreria;
    public $distribucion_setup;
    public $ordencarga_nombre;
    public $transporte_nombre;
    public $devolucion_nombre;
    public $liquidacion_nombre;
    public $hojadevolucion_nombre;
    public $documentosDir;
    public $cajaDir;
    public $distribucionDir;
    public $publicPath;    
    protected function private_core() 
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on($this->class_name);

        /// ¿Hay más de un almacén?
        $fsvar = new fs_var();
        $this->multi_almacen = $fsvar->simple_get('multi_almacen');
        $this->variables_globales();
        
        $this->verificar_carpetas();
        
        $this->existe_tesoreria();
        
        //Si el usuario es admin puede ver todos los recibos, pero sino, solo los de su almacén designado
        $seguridadUsuario = new SeguridadUsuario();
        $this->user = $seguridadUsuario->accesoAlmacenes($this->user);
    }
    
    public function verificar_carpetas()
    {
        $basepath = dirname(dirname(dirname(__DIR__)));
        $this->documentosDir = $basepath . DIRECTORY_SEPARATOR . FS_MYDOCS . 'documentos';
        $this->cajaDir = $this->documentosDir . DIRECTORY_SEPARATOR . "caja";
        $this->distribucionDir = $this->documentosDir . DIRECTORY_SEPARATOR . "distribucion";
        $this->publicPath = FS_PATH . FS_MYDOCS . 'documentos' . DIRECTORY_SEPARATOR . 'distribucion';

        if (!is_dir($this->documentosDir)) {
            mkdir($this->documentosDir);
        }

        if (!is_dir($this->distribucionDir)) {
            mkdir($this->distribucionDir);
        }
        
        if (!is_dir($this->cajaDir)) {
            mkdir($this->cajaDir);
        }

    }
    
    public function existe_tesoreria()
    {
        $this->tesoreria = FALSE;
        //revisamos si esta el plugin de tesoreria
        $disabled = array();
        if (defined('FS_DISABLED_PLUGINS')) {
            foreach (explode(',', FS_DISABLED_PLUGINS) as $aux) {
                $disabled[] = $aux;
            }
        }
        if (in_array('tesoreria', $GLOBALS['plugins']) and ! in_array('tesoreria', $disabled)) {
            $this->tesoreria = TRUE;
        }
    }
    
    /**
     * Comparamos las fechas de los documentos para saber si estan dentro de un
     * determinado rango de fechas o fuera del mismo
     * @param type $fecha
     * @param type $tipo
     * @return boolean
     */
    public function fecha_rango($desde, $hasta, $fecha, $tipo='dentro')
    {
        $respuesta = false;
        if($tipo == 'dentro')
        {
            if(\date('Y-m-d',strtotime($fecha))>=\date('Y-m-d',strtotime($desde)) AND \date('Y-m-d',strtotime($fecha))<=\date('Y-m-d',strtotime($hasta)))
            {
                $respuesta = true;
            }
        }
        elseif($tipo == 'fuera')
        {
            if(\date('Y-m-d',strtotime($fecha))<\date('Y-m-d',strtotime($desde)))
            {
                $respuesta = true;
            }
        }
        return $respuesta;
    }
    
    /**
     * Función para devolver el valor de una variable pasada ya sea por POST o GET
     * @param type string
     * @return type string
     */
    public function filter_request($nombre) {
        $nombre_post = \filter_input(INPUT_POST, $nombre);
        $nombre_get = \filter_input(INPUT_GET, $nombre);
        return ($nombre_post) ? $nombre_post : $nombre_get;
    }
    
    public function variables_globales()
    {
        $fsvar = new fs_var();
        $this->distribucion_setup = $fsvar->array_get( array( 'distrib_ordencarga' => "Orden de Carga",
            'distrib_ordenescarga' => "Ordenes de Carga",
            'distrib_transporte' => "Transporte",             'distrib_transportes' => "Transportes",
            'distrib_devolucion' => "Devolución",             'distrib_devoluciones' => "Devoluciones",
            'distrib_agencia' => "Agencia",             'distrib_agencias' => "Agencias",
            'distrib_unidad' => "Unidad",             'distrib_unidades' => "Unidades",
            'distrib_conductor' => "Conductor",             'distrib_conductores' => "Conductores",
            'distrib_liquidacion' => "Liquidación",             'distrib_liquidaciones' => "Liquidaciones",
            'distrib_faltante' => "Faltante",             'distrib_faltantes' => "Faltantes",
            'distrib_hojadevolucion' => "Hoja de Devolución",             'distrib_hojasdevolucion' => "Hojas de Devolución"), FALSE);
        $this->ordencarga_nombre = ucfirst(strtolower($this->distribucion_setup['distrib_ordencarga']));
        $this->transporte_nombre = ucfirst(strtolower($this->distribucion_setup['distrib_transporte']));
        $this->devolucion_nombre = ucfirst(strtolower($this->distribucion_setup['distrib_devolucion']));
        $this->liquidacion_nombre = ucfirst(strtolower($this->distribucion_setup['distrib_liquidacion']));
        $this->hojadevolucion_nombre = ucfirst(strtolower($this->distribucion_setup['distrib_hojadevolucion']));
    }
    
    public function buscar_conductor()
    {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $con0 = new distribucion_conductores();
        $json = array();
        foreach($con0->search($this->empresa->id, $_REQUEST['buscar_conductor']) as $con)
        {
           $json[] = array('label' => $con->nombre, 'value' => $con->licencia);
        }

        header('Content-Type: application/json');
        echo json_encode( $json );
    }

}
