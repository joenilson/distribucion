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
require_model('almacen.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('distribucion_conductores.php');
require_model('distribucion_transporte.php');
require_model('distribucion_lineastransporte.php');
require_model('distribucion_ordenescarga_facturas.php');
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
/**
 * Description of informe_despachos
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informe_almacen extends fs_controller{
    public $desde;
    public $f_desde;
    public $hasta;
    public $f_hasta;
    public $almacen;
    public $articulo;
    public $albaran;
    public $factura;
    public $distribucion_conductores;
    public $distribucion_transporte;
    public $distribucion_lineastransporte;
    public $distribucion_ordenescarga_facturas;
    public $total_resultados;
    public $referencia;
    public $resultados;
    public $offset;
    public $fileName;
    public $fileNamePath;
    public $documentosDir;
    public $distribucionDir;
    public $publicPath;    
    public function __construct() {
        parent::__construct(__CLASS__, 'Movimientos de Almacén', 'informes', FALSE, TRUE, FALSE);
    }
    
    protected function private_core() {
        $this->shared_extensions();
        $this->almacen = new almacen();
        $this->articulo = new articulo();
        $this->albaran = new albaran_cliente();
        $this->factura = new factura_cliente();
        $this->distribucion_transporte = new distribucion_transporte();
        $this->distribucion_lineastransporte = new distribucion_lineastransporte();
        $this->distribucion_ordenescarga_facturas = new distribucion_ordenescarga_facturas();

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
        
        $desde_p = \filter_input(INPUT_POST, 'desde');
        $desde_g = \filter_input(INPUT_GET, 'desde');
        $desde = ($desde_p)?$desde_p:$desde_g;      
        $this->desde = ($desde)?$desde:\date('01-m-Y');
        $this->f_desde = \date('Y-m-d',strtotime($this->desde));
        
        $hasta_p = \filter_input(INPUT_POST, 'hasta');
        $hasta_g = \filter_input(INPUT_GET, 'hasta');
        $hasta = ($hasta_p)?$hasta_p:$hasta_g;
        $this->hasta = ($hasta)?$hasta:\date('d-m-Y');
        $this->f_hasta = \date('Y-m-d',strtotime($this->hasta));
        
        $codalmacen_p = \filter_input(INPUT_POST, 'codalmacen');
        $codalmacen_g = \filter_input(INPUT_GET, 'codalmacen');
        $codalmacen = ($codalmacen_p)?$codalmacen_p:$codalmacen_g;
        $this->codalmacen = ($codalmacen)?$codalmacen:false;
        
        $referencia_p = \filter_input(INPUT_POST, 'referencia');
        $referencia_g = \filter_input(INPUT_GET, 'referencia');
        $referencia = ($referencia_p)?$referencia_p:$referencia_g;
        $this->referencia = ($referencia)?$referencia:false;
        
        $accion_p = \filter_input(INPUT_POST, 'accion');
        $accion_g = \filter_input(INPUT_GET, 'accion');
        $accion = ($accion_p)?$accion_p:$accion_g;
        if($accion == 'buscar')
        {
            $this->buscar();
        }
        elseif($accion == 'buscar-articulos')
        {
            $this->buscar_articulo();
        }
        
    }
    
    public function buscar_articulo()
    {
        $articulos = new articulo();
        $query = \filter_input(INPUT_GET, 'q');
        $data = $articulos->search($query);
        $this->template = false;
        header('Content-Type: application/json');
        echo json_encode($data);
    }
            
    
    public function buscar()
    {
        $this->template = false;
        $this->resultados = array();
        $datos = array();
        if($this->codalmacen){
            $datos['codalmacen'] = $this->codalmacen;
        }
        
        if($this->referencia){
            $datos['referencia'] = $this->referencia;
        }
        
        $lineas_transportes = $this->distribucion_lineastransporte->lista($this->empresa->id, $datos, $this->f_desde, $this->f_hasta);
        $this->resultados = $lineas_transportes['resultados'];
        $this->total_resultados = $lineas_transportes['cantidad'];
        $this->generar_excel();
        $data['rows'] = $lineas_transportes['resultados'];
        $data['filename'] = $this->fileNamePath;
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    public function generar_excel(){
        //Revisamos que no haya un archivo ya cargado
        $archivo = 'MovimientoAlmacen';
        $this->fileName = $this->distribucionDir . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
        $this->fileNamePath = $this->publicPath . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
        if (file_exists($this->fileName)) {
            unlink($this->fileName);
        }
        //Variables para cada parte del excel
        $estilo_cabecera = array('border'=>'left,right,top,bottom','font-style'=>'bold');
        $estilo_cuerpo = array( array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'));
        
        //Inicializamos la clase
        $this->writer = new XLSXWriter();
        //Verificamos si es un solo almacén o si son todos
        $nombre_hoja = 'Movimiento General';
        if($this->codalmacen)
        {
            $almacen = $this->almacen->get($this->codalmacen);
            $nombre_hoja = $almacen->nombre;
        }
        $this->writer->writeSheetHeader($nombre_hoja, array(), true);
        //Agregamos la linea de titulo
        $cabecera = array('Almacén','Fecha','Transporte','Referencia','Descripción','Salida','Devolución','Total');
        $this->writer->writeSheetRow($nombre_hoja, $cabecera,$estilo_cabecera);
        //Agregamos cada linea en forma de array
        foreach($this->resultados as $linea){
            $item = array();
            $item[] = $linea->codalmacen;
            $item[] = $linea->fecha;
            $item[] = $linea->idtransporte;
            $item[] = $linea->referencia;
            $item[] = $linea->descripcion;
            $item[] = $linea->cantidad;
            $item[] = $linea->devolucion;
            $item[] = $linea->total_final;
            $this->writer->writeSheetRow($nombre_hoja, $item, $estilo_cuerpo);
        }
        //Escribimos
        $this->writer->writeToFile($this->fileNamePath);
    }
    
    public function shared_extensions()
    {
        
    }
}

