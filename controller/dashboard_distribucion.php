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
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('almacenes.php');
require_model('articulo.php');
require_model('distribucion_transportes.php');
require_model('distribucion_conductores.php');
require_model('distribucion_unidades.php');
require_model('distribucion_faltantes.php');
require_model('distribucion_organizacion.php');
require_model('distribucion_rutas.php');
require_model('facturas_cliente.php');
require_model('facturas_proveedor.php');
require_model('forma_pago.php');
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
require_once 'plugins/distribucion/vendors/tcpdf/tcpdf.php';
/**
 * Description of dashboard_distribucion
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class dashboard_distribucion extends fs_controller {
    public $almacenes;
    public $articulos;
    public $cantidad_supervisores;
    public $cantidad_vendedores;
    public $cantidad_unidades;
    public $cantidad_articulos;
    public $facturascli;
    public $facturaspro;
    public $transportes;
    public $conductores;
    public $unidades;
    public $organizacion;
    public $supervisores;
    public $vendedores;
    public $mesa_trabajo;
    public $rutas;
    public $faltantes;
    public $f_desde;
    public $f_hasta;
    public $codalmacen;
    public $total;
    public $total_ingresos;
    public $total_egresos;
    public $total_cobros;
    public $total_pendientes_cobro;
    public $fileNameXLS;
    public $fileNamePDF;
    public $pathNameXLS;
    public $pathNamePDF;
    public $documentosDir;
    public $distribucionDir;
    public $publicPath;
    public $pdf;
    public function __construct() {
        parent::__construct(__CLASS__,'Dashboard Distribución', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core() {
        $this->shared_extensions();
        $this->almacenes = new almacen();
        $this->articulos = new articulo();
        $this->facturascli = new factura_cliente();
        $this->facturaspro = new factura_proveedor();
        $this->organizacion = new distribucion_organizacion();
        $this->faltantes = new distribucion_faltantes();
        $this->unidades = new distribucion_unidades();
        $this->fp = new forma_pago();
        $this->resultados_formas_pago = false;
        //Si el usuario es admin puede ver todos los recibos, pero sino, solo los de su almacén designado
        if(!$this->user->admin){
            $this->agente = new agente();
            $cod = $this->agente->get($this->user->codagente);
            $user_almacen = $this->almacenes->get($cod->codalmacen);
            $this->user->codalmacen = $user_almacen->codalmacen;
            $this->user->nombrealmacen = $user_almacen->nombre;
        }
        
        //Creamos o validamos las carpetas para grabar los informes de caja
        $this->fileName = '';
        $basepath = dirname(dirname(dirname(__DIR__)));
        $this->documentosDir = $basepath . DIRECTORY_SEPARATOR . FS_MYDOCS . 'documentos';
        $this->distribucionDir = $this->documentosDir . DIRECTORY_SEPARATOR . "distribucion";
        $this->publicPath = FS_PATH . FS_MYDOCS . 'documentos' . DIRECTORY_SEPARATOR . 'distribucion';

        if (!is_dir($this->documentosDir)) {
            mkdir($this->documentosDir);
        }

        if (!is_dir($this->distribucionDir)) {
            mkdir($this->distribucionDir);
        }

        $f_desde = filter_input(INPUT_POST, 'f_desde');
        $this->f_desde = ($f_desde)?$f_desde:\date('01-m-Y');
        $f_hasta = filter_input(INPUT_POST, 'f_hasta');
        $this->f_hasta = ($f_hasta)?$f_hasta:\date('d-m-Y');
        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $this->codalmacen = (isset($this->user->codalmacen))?$this->user->codalmacen:$codalmacen;
        $accion = filter_input(INPUT_POST, 'accion');
        if($accion){
            switch ($accion){
                case "buscar":
                    $this->generar_resumen();
                break;
            }
        }
    }
    
    public function generar_resumen(){
        $this->supervisores = $this->organizacion->all_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'SUPERVISOR');
        $this->cantidad_supervisores = count($this->supervisores);
        $this->mesa_trabajo = array();
        foreach($this->supervisores as $sup){
            $vendedores = $this->organizacion->get_asignados($this->empresa->id, $sup->codagente);
            $this->mesa_trabajo[$sup->codagente] = $vendedores;
        }

        $this->vendedores = $this->organizacion->all_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'VENDEDOR');
        $this->cantidad_vendedores = count($this->vendedores);
        $unidades = $this->unidades->activos_almacen($this->empresa->id, $this->codalmacen);
        $this->cantidad_unidades = count($unidades);
        $articulos = $this->articulos->all();
        $this->cantidad_articulos = 0;
        foreach($articulos as $art){
            if($art->sevende AND !$art->nostock){
                $this->cantidad_articulos++;
            }
        }
    }
    
    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => 'dashboard_distribucion_momentjs',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/moment-with-locales.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'dashboard_distribucion_chartjs',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/distribucion/view/js/z/Chart.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
        );
        foreach ($extensiones as $ext) {
            $fsext = new fs_extension($ext);
            if (!$fsext->save()) {
                $this->new_error_msg('Error al guardar la extensión ' . $ext['name']);
            }
        }
    }
}
