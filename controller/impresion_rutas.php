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
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('almacen.php');
require_model('cliente.php');
require_model('distribucion_clientes.php');
require_model('distribucion_rutas.php');
require_model('distribucion_organizacion.php');
require_once ('plugins/distribucion/vendors/tcpdf/tcpdf.php');
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
require_once 'plugins/distribucion/vendors/FacturaScripts/PrinterManager.php';
use FacturaScripts\PrinterManager;
/**
 * Description of impresion_rutas
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class impresion_rutas extends fs_controller{
    public $almacen;
    public $codalmacen;
    public $clientes;
    public $fecha;
    public $fecha_imprimir;
    public $rutas;
    public $rutas_elegidas;
    public $rutas_listadas;
    public $dia;
    public $dias_elegidos;
    public $dias;
    public $vendedor;
    public $vendedores;
    public $vendedores_elegidos;
    public $codvendedor;
    public $distribucion_rutas;
    public $distribucion_clientes;
    public $distribucion_organizacion;
    public $ArchivoRutasXLSX;
    public $ArchivoRutasXLSXPath;
    public $pdf;
    public $documentosDir;
    public $distribucionDir;
    public $publicPath;
    public function __construct() {
        parent::__construct(__CLASS__, '8 - Impresión de Rutas', 'distribucion', FALSE, TRUE, TRUE);
    }

    protected function private_core() {
        $this->share_extensions();

        $this->almacen = new almacen();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->distribucion_organizacion = new distribucion_organizacion();

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

        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $codvendedor = filter_input(INPUT_POST, 'vendedores');
        $codruta = filter_input(INPUT_POST, 'rutas');
        $dia = filter_input(INPUT_POST, 'dias');
        $fecha = filter_input(INPUT_POST, 'fecha');
        $tipo_p = filter_input(INPUT_POST, 'tipo');
        $tipo_g = filter_input(INPUT_GET, 'tipo');

        $this->codalmacen = (isset($codalmacen))?$codalmacen:'';
        $this->codvendedor = (!empty($codvendedor))?$codvendedor:'';
        $this->ruta = (!empty($codruta))?$codruta:'';
        $this->fecha = (isset($fecha))?$fecha:'';
        $this->dia = (isset($dia))?$dia:'';
        $this->rutas_elegidas = (!empty($codruta))?explode(",",$this->ruta):NULL;
        $this->vendedores_elegidos = (!empty($codvendedor))?explode(",",$this->codvendedor):NULL;
        $this->dias_elegidos = (!empty($dia))?explode(",",$this->dia):NULL;
        $this->dias = $this->lista_dias();

        $tipo = (!empty($tipo_p))?$tipo_p:'';
        $tipo = (!empty($tipo_g))?$tipo_g:$tipo;

        if(!empty($this->codalmacen)){
            $this->vendedores = $this->distribucion_organizacion->activos_almacen_tipoagente($this->empresa->id, $this->codalmacen, 'VENDEDOR');
        }else{
            $this->vendedores = $this->distribucion_organizacion->activos_tipoagente($this->empresa->id, 'VENDEDOR');
        }

        //Buscamos las rutas en orden primero por almacen, luego por día y al final por vendedor
        if(!empty($this->codalmacen) and empty($this->dias_elegidos) and empty($this->vendedores_elegidos)){
            $this->rutas = $this->distribucion_rutas->all_rutasporalmacen($this->empresa->id, $this->codalmacen);
        }elseif(!empty($this->codalmacen) and !empty($this->dias_elegidos) and empty($this->vendedores_elegidos)){
            $lista = array();
            foreach($this->dias_elegidos as $d){
                $linea = $this->distribucion_rutas->all_rutaspordia($this->empresa->id, $this->codalmacen, $d);
                $lista = array_merge($linea, $lista);
            }
            $this->rutas = $lista;
        }elseif(!empty($this->codalmacen) and empty($this->dias_elegidos) and !empty($this->vendedores_elegidos)){
            $lista = array();
            foreach($this->vendedores_elegidos as $vendedor){
                $linea = $this->distribucion_rutas->all_rutasporagente($this->empresa->id, $this->codalmacen, $vendedor);
                $lista = array_merge($linea, $lista);
            }
            $this->rutas = $lista;
        }elseif(!empty($this->codalmacen) and !empty($this->dias_elegidos) and !empty($this->vendedores_elegidos)){
            $lista = array();
            $string_dias = implode(" = TRUE OR ",$this->dias_elegidos)." = TRUE ";
            foreach($this->vendedores_elegidos as $vendedor){
                $linea = $this->distribucion_rutas->all_rutasporagentedias($this->empresa->id, $this->codalmacen, $vendedor, $string_dias);
                $lista = array_merge($linea, $lista);
            }
            $this->rutas = $lista;
        }

        if(!empty($this->rutas)){
            $this->buscar_seleccionados('rutas');
        }

        $this->buscar_seleccionados('vendedores');


        $this->buscar_seleccionados('dias');

        if(isset($tipo) and !empty($tipo)){
            switch($tipo){
                case "busqueda":
                    $this->buscar_rutas();
                    break;
                case "ver-clientes":
                    $this->buscar_clientes();
                    break;
                case "imprimir-rutas":
                    $this->imprimir_rutas();
                    break;
                default:
                    break;
            }
        }
    }

    public function lista_dias(){
        $dias_array = array("lunes","martes","miercoles","jueves","viernes","sabado","domingo");
        $lista = array();
        foreach ($dias_array as $d){
            $semana = new stdClass();
            $semana->dia = $d;
            array_push($lista, $semana);
        }
        return $lista;
    }

    public function buscar_seleccionados($tipo){
        switch($tipo){
            case "rutas":
                $rutas_origen = $this->rutas;
                $rutas_destino = array();
                foreach($rutas_origen as $linea){
                    if(!empty($this->rutas_elegidas)){
                        $linea->seleccionada = (in_array($linea->ruta, $this->rutas_elegidas))?true:false;
                    }else{
                        $linea->seleccionada = false;
                    }
                    $rutas_destino[] = $linea;
                }
                $this->rutas = $rutas_destino;
                break;
            case "vendedores":
                $vendedores_origen = $this->vendedores;
                $vendedores_destino = array();
                foreach($vendedores_origen as $linea){
                    if(!empty($this->vendedores_elegidos)){
                        $linea->seleccionado=(in_array($linea->codagente, $this->vendedores_elegidos))?true:false;
                    }else{
                        $linea->seleccionado = false;
                    }
                    $vendedores_destino[] = $linea;
                }
                $this->vendedores = $vendedores_destino;
                break;
            case "dias":
                $dias_origen = $this->dias;
                $dias_destino = array();
                foreach($dias_origen as $linea){
                    if(!empty($this->dias_elegidos)){
                        $linea->seleccionado=(in_array($linea->dia, $this->dias_elegidos))?true:false;
                    }else{
                        $linea->seleccionado = false;
                    }
                    $dias_destino[] = $linea;
                }
                $this->dias = $dias_destino;
                break;
            default:
                break;
        }
    }

    public function buscar_rutas(){
        $lista = array();
        $lista_rutas = (!empty($this->rutas_elegidas))?$this->rutas_elegidas:$this->rutas;
        foreach ($lista_rutas as $r){
            $valor = (is_object($r))?$r->ruta:$r;
            $info = $this->distribucion_rutas->get($this->empresa->id, $this->codalmacen, $valor);
            $info->cantidad = $this->distribucion_rutas->cantidad_asignados($this->empresa->id, $this->codalmacen, $valor);
            $lista[] = $info;
        }
        $this->rutas_listadas = $lista;
        $this->generar_excel();
    }

    public function buscar_clientes(){
        $this->template = FALSE;
        $a = filter_input(INPUT_GET, 'almacen');
        $r = filter_input(INPUT_GET, 'ruta');
        $lista_clientes = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $a, $r);
        $cabecera = $this->distribucion_rutas->get($this->empresa->id, $a, $r);
        $cabecera->cantidad = $this->distribucion_rutas->cantidad_asignados($this->empresa->id, $a, $r);
        $cabecera->almacen_nombre = $this->almacen->get($a)->nombre;
        $cabecera->dias_atencion = $this->dias_atencion($cabecera, "HTML");

        header('Content-Type: application/json');
        echo json_encode(array('rows'=>$lista_clientes,'cabecera'=>$cabecera));
    }

    public function generar_excel(){
        //Revisamos que no haya un archivo ya cargado
        $archivo = 'ListaRutas';
        $this->ArchivoRutasXLSX = $this->distribucionDir . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
        $this->ArchivoRutasXLSXPath = $this->publicPath . DIRECTORY_SEPARATOR . $archivo . "_" . $this->user->nick . ".xlsx";
        if (file_exists($this->ArchivoRutasXLSX)) {
            unlink($this->ArchivoRutasXLSX);
        }
        //Variables para cada parte del excel
        $estilo_cabecera = array('border'=>'left,right,top,bottom','font-style'=>'bold');
        $estilo_cuerpo = array( array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'left'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'),array('halign'=>'none'));

        //Inicializamos la clase
        $this->writer = new XLSXWriter();
        //Creamos la hoja con todos los clientes organizados por ruta
        $nombre_hoja = "Clientes por ruta";
        $this->writer->writeSheetHeader($nombre_hoja, array(), true);
        //Agregamos la linea de titulo
        $cabecera = array('Almacén','Ruta','Vendedor','Codigo','Cliente','Razon Social',FS_CIFNIF,'Dirección','Canal','Subcanal');
        $this->writer->writeSheetRow($nombre_hoja, $cabecera,$estilo_cabecera);
        //Agregamos cada linea en forma de array
        foreach($this->rutas_listadas as $ruta){
            $lista_clientes = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $ruta->codalmacen, $ruta->ruta);
            if($lista_clientes){
                foreach($lista_clientes as $cliente){
                    $linea = array($ruta->codalmacen,$ruta->ruta,$ruta->nombre,$cliente->codcliente,$cliente->nombre_cliente,$cliente->razonsocial,$cliente->cifnif,$cliente->direccion,$cliente->canal_descripcion,$cliente->subcanal_descripcion);
                    $this->writer->writeSheetRow($nombre_hoja, $linea, $estilo_cuerpo);
                }
            }
        }
        //Escribimos
        $this->writer->writeToFile($this->ArchivoRutasXLSXPath);
    }

    public function dias_atencion($datos, $formato = "HTML"){
        $partes = '';
        if($formato == 'HTML'){
            $span_activo = "<span class='btn btn-success btn-xs'>";
            $span_inactivo = "<span class='btn btn-default btn-xs'>";
            $span_fin_activo = '</span>';
            $span_fin = '</span>';
        }elseif($formato == "PDF"){
            $span_activo = '<b>[';
            $span_inactivo = ' ';
            $span_fin_activo = ']</b>';
            $span_fin = ' ';
        }
        $span_inicio_l = ($datos->lunes)?$span_activo:$span_inactivo;
        $span_fin_l = ($datos->lunes)?$span_fin_activo:$span_fin;
        $partes.=$span_inicio_l.'Lu'.$span_fin_l;
        $span_inicio_m = ($datos->martes)?$span_activo:$span_inactivo;
        $span_fin_m = ($datos->martes)?$span_fin_activo:$span_fin;
        $partes.=$span_inicio_m.'Ma'.$span_fin_m;
        $span_inicio_i = ($datos->miercoles)?$span_activo:$span_inactivo;
        $span_fin_i = ($datos->miercoles)?$span_fin_activo:$span_fin;
        $partes.=$span_inicio_i.'Mi'.$span_fin_i;
        $span_inicio_j = ($datos->jueves)?$span_activo:$span_inactivo;
        $span_fin_j = ($datos->jueves)?$span_fin_activo:$span_fin;
        $partes.=$span_inicio_j.'Ju'.$span_fin_j;
        $span_inicio_v = ($datos->viernes)?$span_activo:$span_inactivo;
        $span_fin_v = ($datos->viernes)?$span_fin_activo:$span_fin;
        $partes.=$span_inicio_v.'Vi'.$span_fin_v;
        $span_inicio_s = ($datos->sabado)?$span_activo:$span_inactivo;
        $span_fin_s = ($datos->sabado)?$span_fin_activo:$span_fin;
        $partes.=$span_inicio_s.'Sa'.$span_fin_s;
        $span_inicio_d = ($datos->domingo)?$span_activo:$span_inactivo;
        $span_fin_d = ($datos->domingo)?$span_fin_activo:$span_fin;
        $partes.=$span_inicio_d.'Do'.$span_fin_d;
        return $partes;
    }

    public function imprimir_rutas(){
        $this->template = FALSE;
        $rutas_imprimir = explode(",",filter_input(INPUT_GET, 'rutas'));
        $almacen_imprimir = filter_input(INPUT_GET, 'codalmacen');
        $fecha_imprimir = filter_input(INPUT_GET, 'fecha');
        $this->fecha_imprimir = ($fecha_imprimir)?\date('Y-m-d',strtotime($fecha_imprimir)):\date('Y-m-d');
        $conf = array('file'=>'rutas_clientes.pdf', 'type'=>'pdf', 'page_size'=>'letter','font'=>'Courier');
        $pdf_doc = new PrinterManager($conf);
        $pdf_doc->crearArchivo();
        //$this->pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        //$this->pdf->setPageOrientation('P',TRUE,10);
        foreach($rutas_imprimir as $r){
            $informacion_ruta = $this->distribucion_rutas->get($this->empresa->id, $almacen_imprimir, $r);
            $informacion_ruta->cantidad = $this->distribucion_rutas->cantidad_asignados($this->empresa->id, $almacen_imprimir, $r);
            $informacion_ruta->almacen_nombre = $this->almacen->get($informacion_ruta->codalmacen)->nombre;
            $informacion_ruta->dias_atencion = $this->dias_atencion($informacion_ruta, 'PDF');
            $informacion_ruta->numero = 'Listado de Clientes al '.$this->fecha_imprimir;
            $informacion_ruta->cabecera_lineas = $this->cabeceras_lineas_rutas();
            $cabecera = array();
            $cabecera[] = array('size'=>60, 'label'=>'Almacén:','valor'=>$informacion_ruta->almacen_nombre,'salto_linea'=>false,'html'=>false);
            $cabecera[] = array('size'=>60, 'label'=>'Supervisor:','valor'=>$informacion_ruta->nombre_supervisor,'salto_linea'=>true,'html'=>false);
            $cabecera[] = array('size'=>60, 'label'=>'Vendedor:','valor'=>$informacion_ruta->nombre,'salto_linea'=>false,'html'=>false);
            $cabecera[] = array('size'=>60, 'label'=>'Días de visita:','valor'=>$informacion_ruta->dias_atencion,'salto_linea'=>true,'html'=>true);
            $cabecera[] = array('size'=>60, 'label'=>'Ruta:','valor'=>$informacion_ruta->ruta.' - '.$informacion_ruta->descripcion,'salto_linea'=>false,'html'=>false);
            $cabecera[] = array('size'=>60, 'label'=>'Total Clientes:','valor'=>$informacion_ruta->cantidad,'salto_linea'=>true,'html'=>false);
            $pdf_doc->agregarCabecera($this->empresa, $informacion_ruta, $cabecera);
            $lista_clientes = $this->distribucion_clientes->clientes_ruta_imprimir($this->empresa->id, $almacen_imprimir, $r);
            $pdf_doc->agregarLineas($lista_clientes,TRUE);
        }
        $pdf_doc->mostrarDocumento();
    }

    public function cabeceras_lineas_rutas(){
        $cabecera = array();
        $cabecera[] = array('size'=>35, 'descripcion'=>'Código','align'=>'C','total'=>false);
        $cabecera[] = array('size'=>80, 'descripcion'=>'Cliente','align'=>'L','total'=>false);
        $cabecera[] = array('size'=>90, 'descripcion'=>'Dirección','align'=>'L','total'=>false);
        $cabecera[] = array('size'=>40, 'descripcion'=>'Canal','align'=>'L','total'=>false);
        $cabecera[] = array('size'=>40, 'descripcion'=>'Subcanal','align'=>'L','total'=>false);
        return $cabecera;
    }

    /**
     * @deprecated since version 62
     */
    public function imprimir_rutas_old(){
        $this->template = FALSE;
        $rutas_imprimir = explode(",",filter_input(INPUT_GET, 'rutas'));
        $almacen_imprimir = filter_input(INPUT_GET, 'codalmacen');
        $fecha_imprimir = filter_input(INPUT_GET, 'fecha');
        $this->pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->setPageOrientation('P',TRUE,10);
        foreach($rutas_imprimir as $r){
            $lista_clientes = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $almacen_imprimir, $r);
            $cabecera = new stdClass();
            $cabecera = $this->distribucion_rutas->get($this->empresa->id, $almacen_imprimir, $r);
            $cabecera->cantidad = $this->distribucion_rutas->cantidad_asignados($this->empresa->id, $almacen_imprimir, $r);
            $cabecera->almacen_nombre = $this->almacen->get($cabecera->codalmacen)->nombre;
            $cabecera->dias_atencion = $this->dias_atencion($cabecera, 'PDF');
            $basepath = dirname(dirname(__FILE__));
            $logo = '../../../..'.FS_MYDOCS.DIRECTORY_SEPARATOR.'images/logo.png';
            $logo_empresa = (file_exists($logo))?$logo:false;
            $this->pdf->startPageGroup();
            $this->pdf->SetHeaderData(
                $logo_empresa,
                15,
                $this->empresa->nombre,
                'Listado de Clientes al '.$fecha_imprimir,
                array(0,0,0),
                array(0,0,0));
            $this->pdf->setFooterData(array(0,64,0), array(0,64,128));
            // set header and footer fonts
            $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            // set default monospaced font
            $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            //$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $this->pdf->SetAutoPageBreak(TRUE, 0);

            $this->pdf->SetFont('courier', '', 9);

            $header = array('Codigo', 'Cliente', 'Direccion', 'Canal', 'Subcanal');
            $this->ColoredTable($header, $lista_clientes, $cabecera);
        }
        $this->pdf->Output('ruta_impresa.pdf', 'I');
    }

    public function HeaderPage($header_page){
        // Colors, line width and bold font
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->SetTextColor(0);


        $this->pdf->SetDrawColor(153, 153, 153);
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->SetFont('courier', 'B');
        //Cabecera
        $this->pdf->Cell(20, 4, 'Almacén:', 0, 0, 'L', 0);$this->pdf->SetFont('courier', '', 8);
        $this->pdf->Cell(50, 4, $header_page->almacen_nombre, 0, 0, 'L', 0);$this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(30, 4, 'Supervisor:', 0, 0, 'L', 0);$this->pdf->SetFont('courier', '', 8);
        $this->pdf->Cell(60, 4, $header_page->nombre_supervisor, 0, 0, 'L', 1);$this->pdf->SetFont('courier', 'B');
        $this->pdf->Ln();
        $this->pdf->Cell(20, 4, 'Vendedor:', 0, 0, 'L', 1);$this->pdf->SetFont('courier', '', 8);
        $this->pdf->Cell(50, 4, $header_page->nombre, 0, 0, 'L', 1);$this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(30, 4, 'Días de Visita:', 0, 0, 'L', 1);$this->pdf->SetFont('courier', '', 8);
        $this->pdf->writeHTMLCell(60, 4, 112, 24,$header_page->dias_atencion, 0, 'L', 0, 0);$this->pdf->SetFont('courier', 'B');
        $this->pdf->Ln();
        $this->pdf->Cell(20, 4, 'Ruta:', 0, 0, 'L', 1);$this->pdf->SetFont('courier', '', 8);
        $this->pdf->Cell(50, 4, $header_page->ruta.' - '.$header_page->descripcion, 0, 0, 'L', 1);$this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(30, 4, 'Total Clientes:', 0, 0, 'L', 1);$this->pdf->SetFont('courier', '', 8);
        $this->pdf->Cell(60, 4, $header_page->cantidad, 0, 0, 'L', 1);
        $this->pdf->Cell(100, 6, '', 0, 0, 'C', 0);
        $this->pdf->Ln();
        // Color and font restoration
        $this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont('courier','',8);
    }

    function HeaderTable($header, $w){
        // Colors, line width and bold font
        $this->pdf->SetFillColor(153, 153, 153);
        $this->pdf->SetTextColor(255);
        $this->pdf->SetDrawColor(153, 153, 153);
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->SetFont('courier', 'B');
        // Header
        $num_headers = count($header);
        for($i = 0; $i < $num_headers; ++$i) {
            $this->pdf->Cell($w[$i], 5, $header[$i], 1, 0, 'C', 1);
        }
        $this->pdf->Ln();
        // Color and font restoration
        $this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont('courier','',8);
    }

    // Colored table
    public function ColoredTable($header, $lista_clientes, $header_page) {
        //Tamaño de cada linea
        $w = array(15, 40, 60, 25, 40);
                // Data
        $fill = 0;
        $line = 0;
        foreach($lista_clientes as $row) {
            if($line == 0){
                $this->pdf->AddPage();
                $this->pdf->setXY(15,20);
                $this->HeaderPage($header_page);
                $this->HeaderTable($header, $w);
            }
            $this->pdf->Cell($w[0], 5, $row->codcliente, 'LR', 0, 'C', $fill);
            $this->pdf->Cell($w[1], 5, $row->nombre_cliente, 'LR', 0, 'L', $fill);
            $this->pdf->Cell($w[2], 5, $row->direccion, 'LR', 0, 'L', $fill);
            $this->pdf->Cell($w[3], 5, $row->canal_descripcion, 'LR', 0, 'L', $fill);
            $this->pdf->Cell($w[4], 5, substr($row->subcanal_descripcion,0,15), 'LR', 0, 'L', $fill);
            $this->pdf->Ln();
            $fill=!$fill;
            if($line<=45){
                $line++;
            }else{
                $this->pdf->Cell(array_sum($w), 0, '', 'T');
                $line = 0;
            }
        }
        $this->pdf->Cell(array_sum($w), 0, '', 'T');
    }

    private function share_extensions(){
        $extensiones = array(
            array(
                'name' => 'impresion_rutas_datepicker_es_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="' . FS_PATH . 'plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_jqueryui_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="' . FS_PATH . 'plugins/distribucion/view/js/jquery-ui.min.js"></script>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_jqueryui_css1',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="' . FS_PATH . 'plugins/distribucion/view/css/jquery-ui.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_jqueryui_css2',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="' . FS_PATH . 'plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_jqueryui_css3',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="' . FS_PATH . 'plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_css5',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="' . FS_PATH . 'plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_css6',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_css7',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_css11',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="' . FS_PATH . 'plugins/distribucion/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            )
        );

        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->delete()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }

        $extensiones2 = array(
            array(
                'name' => '001_impresion_rutas_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="' . FS_PATH . 'plugins/distribucion/view/js/jquery-ui.min.js"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_impresion_rutas_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="' . FS_PATH . 'plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_impresion_rutas_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="'.FS_PATH.'plugins/distribucion/view/js/locale/defaults-es_CL.min.js"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_impresion_rutas_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_impresion_rutas_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script src="' . FS_PATH . 'plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_jqueryui_css1',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="' . FS_PATH . 'plugins/distribucion/view/css/jquery-ui.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_jqueryui_css2',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="' . FS_PATH . 'plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_jqueryui_css3',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" href="' . FS_PATH . 'plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_css5',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="' . FS_PATH . 'plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
                'params' => ''
            ),
            array(
                'name' => 'impresion_rutas_css11',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="' . FS_PATH . 'plugins/distribucion/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            )
        );

        foreach ($extensiones2 as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
}
