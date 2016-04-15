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
require_model('almacen.php');
require_model('cliente.php');
require_model('distribucion_clientes.php');
require_model('distribucion_rutas.php');
require_model('distribucion_organizacion.php');

require_once ('plugins/distribucion/vendors/tcpdf/tcpdf.php');

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
    public function __construct() {
        parent::__construct(__CLASS__, '8 - Impresión de Rutas', 'distribucion', FALSE, TRUE, TRUE);
    }

    protected function private_core() {
        $this->share_extensions();

        $this->almacen = new almacen();
        $this->distribucion_rutas = new distribucion_rutas();
        $this->distribucion_clientes = new distribucion_clientes();
        $this->distribucion_organizacion = new distribucion_organizacion();
        
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
                echo $d;
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
            $info = $this->distribucion_rutas->get($this->empresa->id, $valor);
            $info->cantidad = $this->distribucion_rutas->cantidad_asignados($this->empresa->id, $valor);
            $lista[] = $info;
            
        }
        $this->rutas_listadas = $lista;
    }

    public function buscar_clientes(){
        $span_activo = "<span class='btn btn-success btn-xs'>";
        $span_inactivo = "<span class='btn btn-default btn-xs'>";
        $span_fin = '</span>';
        $partes = '';
        $this->template = FALSE;
        $r = filter_input(INPUT_GET, 'ruta');        
        $lista_clientes = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $r);
        $cabecera = $this->distribucion_rutas->get($this->empresa->id, $r);
        $cabecera->cantidad = $this->distribucion_rutas->cantidad_asignados($this->empresa->id, $r);
        $cabecera->almacen_nombre = $this->almacen->get($cabecera->codalmacen)->nombre;
        $span_inicio_l = ($cabecera->lunes)?$span_activo:$span_inactivo;
        $partes.=$span_inicio_l.' Lu '.$span_fin;
        $span_inicio_m = ($cabecera->martes)?$span_activo:$span_inactivo;
        $partes.=$span_inicio_m.' Ma '.$span_fin;
        $span_inicio_i = ($cabecera->miercoles)?$span_activo:$span_inactivo;
        $partes.=$span_inicio_i.' Mi '.$span_fin;
        $span_inicio_j = ($cabecera->jueves)?$span_activo:$span_inactivo;
        $partes.=$span_inicio_j.' Ju '.$span_fin;
        $span_inicio_v = ($cabecera->viernes)?$span_activo:$span_inactivo;
        $partes.=$span_inicio_v.' Vi '.$span_fin;
        $span_inicio_s = ($cabecera->sabado)?$span_activo:$span_inactivo;
        $partes.=$span_inicio_s.' Sa '.$span_fin;
        $span_inicio_d = ($cabecera->domingo)?$span_activo:$span_inactivo;
        $partes.=$span_inicio_d.' Do '.$span_fin;
        $cabecera->dias_atencion = $partes;
        
        header('Content-Type: application/json');
        echo json_encode(array('rows'=>$lista_clientes,'cabecera'=>$cabecera));
    }
    
    public function imprimir_rutas(){
        $this->template = FALSE;
        $rutas_imprimir = explode(",",filter_input(INPUT_GET, 'rutas'));
        $almacen_imprimir = filter_input(INPUT_GET, 'codalmacen');
        
        $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        foreach($rutas_imprimir as $r){
            $lista_clientes = $this->distribucion_clientes->clientes_ruta($this->empresa->id, $r);
            $cabecera = $this->distribucion_rutas->get($this->empresa->id, $r);
            $cabecera->cantidad = $this->distribucion_rutas->cantidad_asignados($this->empresa->id, $r);
            $cabecera->almacen_nombre = $this->almacen->get($cabecera->codalmacen)->nombre;
            $logo_empresa = '../../../../'.'tmp'.DIRECTORY_SEPARATOR.FS_TMP_NAME.'logo.png';
            $pdf->startPageGroup();
            $pdf->SetHeaderData(
                $logo_empresa, 
                15, 
                $this->empresa->nombre, 
                'Listado de Clientes: '.$cabecera->ruta.' '.$cabecera->descripcion, 
                array(0,0,0), 
                array(0,0,0));
            $pdf->setFooterData(array(0,64,0), array(0,64,128));
            // set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            
            $pdf->SetFont('courier', '', 9);
            $pdf->AddPage();
            $header = array('Codigo', 'Cliente', 'Direccion', 'Canal', 'Subcanal');
            
            $this->ColoredTable($pdf, $header, $lista_clientes);
            
        }
        $pdf->Output('ruta_impresa.pdf', 'I');
    }
    
    // Colored table
    public function ColoredTable($pdf, $header, $lista_clientes) {
        // Colors, line width and bold font
        $pdf->SetFillColor(255, 0, 0);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(128, 0, 0);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('courier', 'B');
        // Header
        $w = array(15, 40, 60, 25, 40);
        $num_headers = count($header);
        for($i = 0; $i < $num_headers; ++$i) {
            $pdf->Cell($w[$i], 5, $header[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        // Color and font restoration
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('courier','',8);
        // Data
        $fill = 0;
        foreach($lista_clientes as $row) {
            $pdf->Cell($w[0], 5, $row->codcliente, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[1], 5, $row->nombre_cliente, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 5, $row->direccion, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[3], 5, $row->canal_descripcion, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[4], 5, substr($row->subcanal_descripcion,0,15), 'LR', 0, 'L', $fill);
            $pdf->Ln();
            $fill=!$fill;
        }
        $pdf->Cell(array_sum($w), 0, '', 'T');
    }

    private function share_extensions(){
        $fsext0 = new fs_extension(
            array(
                'name' => 'impresion_rutas_datepicker_es_js',
                'page_from' => __CLASS__,
                'page_to' => 'impresion_rutas',
                'type' => 'head',
                'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/locale/datepicker-es.js"></script>',
                'params' => ''
            )
        );
        $fsext0->save();

        $fsext1 = new fs_extension(
            array(
            'name' => 'impresion_rutas_jqueryui_js',
            'page_from' => __CLASS__,
            'page_to' => 'impresion_rutas',
            'type' => 'head',
            'text' => '<script type="text/javascript" src="plugins/distribucion/view/js/jquery-ui.min.js"></script>',
            'params' => ''
            )
        );
        $fsext1->save();

        $fsext2 = new fs_extension(
            array(
            'name' => 'impresion_rutas_jqueryui_css1',
            'page_from' => __CLASS__,
            'page_to' => 'impresion_rutas',
            'type' => 'head',
            'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.min.css"/>',
            'params' => ''
            )
        );
        $fsext2->save();

        $fsext3 = new fs_extension(
                array(
           'name' => 'impresion_rutas_jqueryui_css2',
           'page_from' => __CLASS__,
           'page_to' => 'impresion_rutas',
           'type' => 'head',
           'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.structure.min.css"/>',
           'params' => ''
                )
        );
        $fsext3->save();

        $fsext4 = new fs_extension(
                array(
           'name' => 'impresion_rutas_jqueryui_css3',
           'page_from' => __CLASS__,
           'page_to' => 'impresion_rutas',
           'type' => 'head',
           'text' => '<link rel="stylesheet" href="plugins/distribucion/view/css/jquery-ui.theme.min.css"/>',
           'params' => ''
                )
        );
        $fsext4->save();
        
        $fsext6 = new fs_extension(
          array(
           'name' => 'impresion_rutas_css5',
           'page_from' => __CLASS__,
           'page_to' => 'impresion_rutas',
           'type' => 'head',
           'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/distribucion/view/css/ui.jqgrid-bootstrap.css"/>',
           'params' => ''
          )
        );
        $fsext6->save();

        $fsext7 = new fs_extension(
                array(
           'name' => 'impresion_rutas_css6',
           'page_from' => __CLASS__,
           'page_to' => 'impresion_rutas',
           'type' => 'head',
           'text' => '<script src="plugins/distribucion/view/js/locale/grid.locale-es.js" type="text/javascript"></script>',
           'params' => ''
                )
        );
        $fsext7->save();

        $fsext8 = new fs_extension(
                array(
           'name' => 'impresion_rutas_css7',
           'page_from' => __CLASS__,
           'page_to' => 'impresion_rutas',
           'type' => 'head',
           'text' => '<script src="plugins/distribucion/view/js/plugins/jquery.jqGrid.min.js" type="text/javascript"></script>',
           'params' => ''
                )
        );
        $fsext8->save();
        
        $fsext11 = new fs_extension(
                array(
           'name' => 'impresion_rutas_css11',
           'page_from' => __CLASS__,
           'page_to' => 'impresion_rutas',
           'type' => 'head',
           'text' => '<link rel="stylesheet" type="text/css" media="screen" href="plugins/distribucion/view/css/bootstrap-select.min.css"/>',
           'params' => ''
                )
        );
        $fsext11->save();
    }
}
