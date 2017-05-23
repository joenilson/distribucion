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
require_model('almacenes.php');
require_model('distribucion_faltantes.php');
require_model('facturas_cliente.php');
require_model('facturas_proveedor.php');
require_model('forma_pago.php');
require_once 'plugins/facturacion_base/extras/xlsxwriter.class.php';
require_once ('plugins/distribucion/vendors/tcpdf/tcpdf.php');
/**
 * Description of informes_caja
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informes_caja extends fs_controller {
    public $almacenes;
    public $facturascli;
    public $facturaspro;
    public $faltantes;
    public $f_desde;
    public $f_hasta;
    public $codalmacen;
    public $total;
    public $ingresos;
    public $ingresos_condpago;
    public $cobros_condpago;
    public $egresos;
    public $egresos_condpago;
    public $pagos_condpago;
    public $cobros;
    public $resultados_cobros;
    public $resultados_pendientes;
    public $resultados_ingresos;
    public $resultados_egresos;
    public $resultados_egresos_formas_pago;
    public $resultados_formas_pago;
    public $resultados_faltantes_cobrados;
    public $resultados_faltantes_pendientes;
    public $total_ingresos;
    public $total_cobros;
    public $total_pendientes_cobro;
    public $total_egresos;
    public $total_pagos;
    public $total_pendientes_pago;
    public $total_general;
    public $total_ventas;
    public $total_faltantes_ventas;
    public $total_faltantes_compras;
    public $total_compras;
    public $pagadas;
    public $pendientes;
    public $fp;
    public $detalle;
    public $tesoreria;
    public $fileNameXLS;
    public $fileNamePDF;
    public $pathNameXLS;
    public $pathNamePDF;
    public $documentosDir;
    public $cajaDir;
    public $publicPath;
    public $pdf;
    public function __construct() {
        parent::__construct(__CLASS__, 'Caja', 'informes', FALSE, TRUE, FALSE);
    }

    protected function private_core() {
        $this->almacenes = new almacen();
        $this->facturascli = new factura_cliente();
        $this->facturaspro = new factura_proveedor();
        $this->faltantes = new distribucion_faltantes();
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
        
        //revisamos si esta el plugin de tesoreria
        $this->tesoreria = FALSE;
        $disabled = array();
        if( defined('FS_DISABLED_PLUGINS') )
        {
           foreach( explode(',', FS_DISABLED_PLUGINS) as $aux )
           {
              $disabled[] = $aux;
           }
        }
        if(in_array('tesoreria',$GLOBALS['plugins']) and !in_array('tesoreria',$disabled)){
            $this->tesoreria = TRUE;
        }

        //Creamos o validamos las carpetas para grabar los informes de caja
        $this->fileName = '';
        $basepath = dirname(dirname(dirname(__DIR__)));
        $this->documentosDir = $basepath . DIRECTORY_SEPARATOR . FS_MYDOCS . 'documentos';
        $this->cajaDir = $this->documentosDir . DIRECTORY_SEPARATOR . "caja";
        $this->publicPath = FS_PATH . FS_MYDOCS . 'documentos' . DIRECTORY_SEPARATOR . 'caja';

        if (!is_dir($this->documentosDir)) {
            mkdir($this->documentosDir);
        }

        if (!is_dir($this->cajaDir)) {
            mkdir($this->cajaDir);
        }

        $f_desde = filter_input(INPUT_POST, 'f_desde');
        $this->f_desde = ($f_desde)?$f_desde:\date('d-m-Y');
        $f_hasta = filter_input(INPUT_POST, 'f_hasta');
        $this->f_hasta = ($f_hasta)?$f_hasta:\date('d-m-Y');
        $codalmacen = filter_input(INPUT_POST, 'codalmacen');
        $this->codalmacen = (isset($this->user->codalmacen))?$this->user->codalmacen:$codalmacen;
        $accion = filter_input(INPUT_POST, 'accion');
        if($accion){
            switch ($accion){
                case "buscar":
                    $this->pagadas = array();
                    $this->pendientes = array();
                    $this->detalle = array();
                    $this->generar_formas_pago();
                    $this->total_general = 0;
                    $this->ingresos();
                    $this->egresos();
                    $this->generar_excel();
                    $this->generar_pdf();
                break;
            }
        }
    }

    private function generar_excel(){
        $this->pathNameXLS = $this->cajaDir . DIRECTORY_SEPARATOR . 'informe' . "_" . $this->user->nick . ".xlsx";
        $this->fileNameXLS = $this->publicPath . DIRECTORY_SEPARATOR . 'informe' . "_" . $this->user->nick . ".xlsx";
        if (file_exists($this->fileNameXLS)) {
            unlink($this->fileNameXLS);
        }
        $cabeceraResumenIngresos['Tipo'] = 'string';
        $cabeceraResumenIngresos['Facturado'] = '#,###,###.##';
        $cabeceraResumenIngresos['Cobrado'] = '#,###,###.##';
        $cabeceraResumenIngresos['Por Cobrar'] = '#,###,###.##';
        $cabeceraResumenIngresos[''] = '#,###,###.##';

        $this->writer = new XLSXWriter();
        $this->writer->writeSheetHeader('Resumen', $cabeceraResumenIngresos);
        $this->writer->writeSheetRow('Resumen', array('Detalle de Ingresos', '', ''));
        $this->writer->writeSheetRow('Resumen', array('Ventas', $this->total_ventas, $this->pagadas['ventas'], $this->pendientes['ventas']));
        $this->writer->writeSheetRow('Resumen', array('Faltantes', $this->total_faltantes_ventas, $this->pagadas['faltantes_ventas'], $this->pendientes['faltantes_ventas']));
        $this->writer->writeSheetRow('Resumen', array('Total', $this->total_ingresos, $this->total_cobros, $this->total_pendientes_cobro));
        $this->writer->writeSheetHeader('Resumen', $cabeceraResumenIngresos);
        $this->writer->writeSheetRow('Resumen', array('Detalle de Egresos', '', ''));
        $this->writer->writeSheetRow('Resumen', array('Compras', $this->total_compras, $this->pagadas['compras'], $this->pendientes['compras']));
        $this->writer->writeSheetRow('Resumen', array('Faltantes', $this->total_faltantes_compras, $this->pagadas['faltantes_compras'], $this->pendientes['faltantes_compras']));
        $this->writer->writeSheetRow('Resumen', array('Total', $this->total_egresos, $this->total_pagos, $this->total_pendientes_pago));
        $this->writer->writeSheetRow('Resumen', array('', '', ''));
        $this->writer->writeSheetRow('Resumen', array('', '', ''));
        $this->writer->writeSheetRow('Resumen', array('', '', ''));
        $this->writer->writeSheetRow('Resumen', array('Movimientos por Formas de Pago', '', '','',''));
        $totales_fp['ingresos_brutos'] = 0;
        $totales_fp['ingresos_netos'] = 0;
        $totales_fp['egresos_brutos'] = 0;
        $totales_fp['egresos_netos'] = 0;
        foreach($this->fp->all() as $fp){
            $this->writer->writeSheetRow('Resumen', array($fp->descripcion, $this->ingresos_condpago[$fp->codpago], $this->cobros_condpago[$fp->codpago], $this->egresos_condpago[$fp->codpago], $this->pagos_condpago[$fp->codpago]));
            $totales_fp['ingresos_brutos'] += $this->ingresos_condpago[$fp->codpago];
            $totales_fp['ingresos_netos'] += $this->cobros_condpago[$fp->codpago];
            $totales_fp['egresos_brutos'] += $this->egresos_condpago[$fp->codpago];
            $totales_fp['egresos_netos'] += $this->pagos_condpago[$fp->codpago];
        }
        $this->writer->writeSheetRow('Resumen', array('Total', $totales_fp['ingresos_brutos'], $totales_fp['ingresos_netos'], $totales_fp['egresos_brutos'], $totales_fp['egresos_netos']));

        $cabeceraDetalleVenta['Factura'] = 'string';
        $cabeceraDetalleVenta[FS_NUMERO2] = 'string';
        $cabeceraDetalleVenta['Cliente'] = 'string';
        $cabeceraDetalleVenta['Pagada'] = 'string';
        $cabeceraDetalleVenta['Importe'] = '#,###,###.##';
        $cabeceraDetalleVenta['Rect'] = '#,###,###.##';
        $cabeceraDetalleVenta['Abonos'] = '#,###,###.##';
        $cabeceraDetalleVenta['Saldo'] = '#,###,###.##';
        $cabeceraDetalleVenta['Fecha Factura'] = 'date';
        $cabeceraDetalleVenta['Fecha Pago'] = 'date';
        $this->writer->writeSheetHeader('Ventas', $cabeceraDetalleVenta);
        $totalImporte=0;
        $totalRectificativas=0;
        $totalAbonos=0;
        $totalSaldo=0;
        foreach($this->detalle['ventas'] as $factura){
            $factura->saldo = ($factura->total+$factura->rectificativa)-$factura->abonos;
            $this->writer->writeSheetRow('Ventas', array($factura->idfactura, $factura->numero2, $factura->nombrecliente, ($factura->pagada)?'Pagada':'Pendiente', $factura->total, $factura->rectificativa, $factura->abonos, $factura->saldo, \date('Y-m-d',strtotime($factura->fecha)), ($factura->fecha_pago)?\date('Y-m-d',strtotime($factura->fecha_pago)):''));
            $totalImporte+=$factura->total;
            $totalRectificativas+=$factura->rectificativa;
            $totalAbonos+=$factura->abonos;
            $totalSaldo+=$factura->saldo;
        }
        $this->writer->writeSheetRow('Ventas', array('Total Montos Facturas', '', '', '', $totalImporte, $totalRectificativas, $totalAbonos, $totalSaldo, '',''));
        $this->writer->writeSheetRow('Ventas', array('Total Facturas', '', '', '', 0, 0, 0, ($totalAbonos+$totalSaldo), '',''));
        $totalImporteFaltantes=0;
        $totalAbonosFaltantes=0;
        $totalSaldoFaltantes=0;
        foreach($this->detalle['faltantes'] as $factura){
            $factura->saldo = ($factura->total+$factura->rectificativa)-$factura->abonos;
            $this->writer->writeSheetRow('Ventas', array($factura->idrecibo, '', $factura->conductor_nombre, ucfirst($factura->estado), $factura->importe, 0, $factura->importe_abonos, $factura->importe_saldo, \date('Y-m-d',strtotime($factura->fecha)), ($factura->fechap)?\date('Y-m-d',strtotime($factura->fechap)):''));
            $totalImporteFaltantes+=$factura->importe;
            $totalAbonosFaltantes+=$factura->importe_abonos;
            $totalSaldoFaltantes+=$factura->importe_saldo;
        }
        
        $this->writer->writeSheetRow('Ventas', array('Total Montos Faltantes', '', '', '', $totalImporteFaltantes, 0, $totalAbonosFaltantes, $totalSaldoFaltantes, '',''));
        $this->writer->writeSheetRow('Ventas', array('Total Faltantes', '', '', '', 0, 0, 0, ($totalSaldoFaltantes), '',''));
        $this->writer->writeSheetRow('Ventas', array('Total Ingreso Neto', '', '', '', 0, 0, 0, (($totalAbonos+$totalSaldo)-$totalSaldoFaltantes), '',''));
        
        //Hoja de Cuadre contable de Documentos
        $this->writer->writeSheetHeader('Cuadre Ventas', $cabeceraDetalleVenta);
        $totalImporte2=0;
        $totalRectificativas2=0;
        $totalAbonos2=0;
        $totalSaldo2=0;
        foreach($this->detalle['ventas'] as $factura){
            $factura->saldo = ($factura->total+$factura->rectificativa)-$factura->abonos;
            $this->writer->writeSheetRow('Cuadre Ventas', array($factura->idfactura, $factura->numero2, $factura->nombrecliente, ($factura->pagada)?'Pagada':'Pendiente', $factura->total, $factura->rectificativa, $factura->abonos, $factura->saldo, \date('Y-m-d',strtotime($factura->fecha)), ($factura->fecha_pago)?\date('Y-m-d',strtotime($factura->fecha_pago)):''));
            if($factura->get_rectificativas()){
                foreach($factura->get_rectificativas() as $rectificativa){
                    $this->writer->writeSheetRow('Cuadre Ventas', array($rectificativa->idfactura, $rectificativa->numero2, ucfirst(FS_FACTURA_RECTIFICATIVA), ($rectificativa->anulada)?'Anulada':'Activa', 0, $rectificativa->total, 0, 0, \date('Y-m-d',strtotime($rectificativa->fecha)), ''));
                }
            }
            $totalImporte2+=$factura->total;
            $totalRectificativas2+=$factura->rectificativa;
            $totalAbonos2+=$factura->abonos;
            $totalSaldo2+=$factura->saldo;
        }
        $this->writer->writeSheetRow('Cuadre Ventas', array('Total Montos Facturas', '', '', '', $totalImporte2, $totalRectificativas2, $totalAbonos2, $totalSaldo2, '',''));
        $this->writer->writeSheetRow('Cuadre Ventas', array('Total Facturas', '', '', '', 0, 0, 0, ($totalAbonos2+$totalSaldo2), '',''));
        $totalImporteFaltantes2=0;
        $totalAbonosFaltantes2=0;
        $totalSaldoFaltantes2=0;
        foreach($this->detalle['faltantes'] as $factura){
            $factura->saldo = ($factura->total+$factura->rectificativa)-$factura->abonos;
            $this->writer->writeSheetRow('Cuadre Ventas', array($factura->idrecibo, '', $factura->conductor_nombre, ucfirst($factura->estado), $factura->importe, 0, $factura->importe_abonos, $factura->importe_saldo, \date('Y-m-d',strtotime($factura->fecha)), ($factura->fechap)?\date('Y-m-d',strtotime($factura->fechap)):''));
            if($factura->get_pagos()){
                foreach($factura->get_pagos() as $recibo){
                    $this->writer->writeSheetRow('Cuadre Ventas', array($recibo->idrecibo, '', '', ucfirst($factura->estado), $factura->importe, 0, 0, 0, \date('Y-m-d',strtotime($factura->fecha)), ''));
                }
            }
            $totalImporteFaltantes+=$factura->importe;
            $totalAbonosFaltantes+=$factura->importe_abonos;
            $totalSaldoFaltantes+=$factura->importe_saldo;
        }
        
        $this->writer->writeSheetRow('Cuadre Ventas', array('Total Montos Faltantes', '', '', '', $totalImporteFaltantes2, 0, $totalAbonosFaltantes2, $totalSaldoFaltantes2, '',''));
        $this->writer->writeSheetRow('Cuadre Ventas', array('Total Faltantes', '', '', '', 0, 0, 0, ($totalSaldoFaltantes2), '',''));
        $this->writer->writeSheetRow('Cuadre Ventas', array('Total Ingreso Neto', '', '', '', 0, 0, 0, (($totalAbonos2+$totalSaldo2)-$totalSaldoFaltantes2), '',''));
        
        //Hoja de Detalle de Compras
        $this->writer->writeSheetHeader('Compras', $cabeceraDetalleVenta);
        $totalImporteCompras=0;
        $totalRectificativasCompras=0;
        $totalAbonosCompras=0;
        $totalSaldoCompras=0;
        foreach($this->detalle['compras'] as $factura){
            $factura->saldo = ($factura->total+$factura->rectificativa)-$factura->abonos;
            $this->writer->writeSheetRow('Compras', array($factura->idfactura, $factura->numproveedor, $factura->nombre, ($factura->pagada)?'Pagada':'Pendiente', $factura->total, $factura->rectificativa, $factura->abonos, $factura->saldo, \date('Y-m-d',strtotime($factura->fecha)), ($factura->fecha_pago)?\date('Y-m-d',strtotime($factura->fecha_pago)):''));
            $totalImporteCompras+=$factura->total;
            $totalRectificativasCompras+=$factura->rectificativa;
            $totalAbonosCompras+=$factura->abonos;
            $totalSaldoCompras+=$factura->saldo;
        }
        $this->writer->writeSheetRow('Compras', array('Total', '', '', '', $totalImporteCompras, $totalRectificativasCompras, $totalAbonosCompras, $totalSaldoCompras, '',''));
        $this->writer->writeSheetRow('Compras', array('Total', '', '', '', 0, 0, 0, ($totalAbonos+$totalSaldo), '',''));
        $this->writer->writeToFile($this->pathNameXLS);
        gc_collect_cycles();
    }

    private function generar_pdf(){
        $this->pathNamePDF = $this->cajaDir . DIRECTORY_SEPARATOR . 'informe' . "_" . $this->user->nick . ".pdf";
        $this->fileNamePDF = $this->publicPath . DIRECTORY_SEPARATOR . 'informe' . "_" . $this->user->nick . ".pdf";
        if (file_exists($this->fileNamePDF)) {
            unlink($this->fileNamePDF);
        }
        $this->pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->setPageOrientation('L',TRUE,10);
        $basepath = dirname(dirname(__FILE__));
        $logo = '../../../..'.FS_MYDOCS.DIRECTORY_SEPARATOR.'images/logo.png';
        $logo_empresa = (file_exists($logo))?$logo:false;
        $this->pdf->startPageGroup();
        $this->pdf->SetHeaderData(
            $logo_empresa,
            10,
            $this->empresa->nombre,
            'Informe de Caja del Almacén: '.$this->almacenes->get($this->codalmacen)->nombre.' del '.$this->f_desde.' al '.$this->f_hasta. 'generado el: '.\date('d-m-Y H:i:s'),
            array(0,0,0),
            array(0,0,0)
        );
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
        $headerResumen = array('Tipo'=>20,'Facturado'=>30,'Cobrado'=>30,'Por cobrar'=>30);
        $this->pdf->SetFont('courier', '', 9);
        $this->pdf->AddPage();
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(110, 4, 'Detalle de Ingresos', 1, 0, 'C', 0);
        $this->pdf->Ln();
        $this->pdfHeader($headerResumen);
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(20, 4, 'Ventas', 1, 0, 'L', 0);
        $this->pdf->SetFont('courier', '');
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_ventas,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->pagadas['ventas'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->pendientes['ventas'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Ln();
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(20, 4, 'Faltantes', 1, 0, 'L', 0);
        $this->pdf->SetFont('courier', '');
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_faltantes_ventas,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->pagadas['faltantes_ventas'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->pendientes['faltantes_ventas'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Ln();
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(20, 4, 'Total', 1, 0, 'L', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_ingresos,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_cobros,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_pendientes_cobro,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(110, 4, 'Detalle de Egresos', 1, 0, 'C', 0);
        $this->pdf->Ln();
        $this->pdfHeader($headerResumen);
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(20, 4, 'Ventas', 1, 0, 'L', 0);
        $this->pdf->SetFont('courier', '');
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_compras,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->pagadas['compras'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->pendientes['compras'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Ln();
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(20, 4, 'Faltantes', 1, 0, 'L', 0);
        $this->pdf->SetFont('courier', '');
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_faltantes_compras,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->pagadas['faltantes_compras'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->pendientes['faltantes_compras'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Ln();
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(20, 4, 'Total', 1, 0, 'L', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_egresos,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_pagos,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($this->total_pendientes_pago,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(180, 4, 'Movimientos por Formas de Pago', 1, 0, 'C', 0);
        $this->pdf->Ln();
        $headerFormasPago = array('forma Pago'=>60,'Ingreso Bruto'=>30,'Ingreso Neto'=>30,'Egreso Bruto'=>30, 'Egreso Neto'=>30);
        $this->pdfHeader($headerFormasPago);
        $totales_fp['ingresos_brutos'] = 0;
        $totales_fp['ingresos_netos'] = 0;
        $totales_fp['egresos_brutos'] = 0;
        $totales_fp['egresos_netos'] = 0;
        foreach($this->fp->all() as $fp){
            $this->pdf->Cell(60, 4, $fp->descripcion, 1, 0, 'L', 0);
            $this->pdf->Cell(30, 4, $this->show_precio($this->ingresos_condpago[$fp->codpago],$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(30, 4, $this->show_precio($this->cobros_condpago[$fp->codpago],$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(30, 4, $this->show_precio($this->egresos_condpago[$fp->codpago],$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(30, 4, $this->show_precio($this->pagos_condpago[$fp->codpago],$this->empresa->coddivisa), 1, 0, 'R', 0);
            $totales_fp['ingresos_brutos'] += $this->ingresos_condpago[$fp->codpago];
            $totales_fp['ingresos_netos'] += $this->cobros_condpago[$fp->codpago];
            $totales_fp['egresos_brutos'] += $this->egresos_condpago[$fp->codpago];
            $totales_fp['egresos_netos'] += $this->pagos_condpago[$fp->codpago];
            $this->pdf->Ln();
        }

        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(60, 4, 'Total', 1, 0, 'L', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($totales_fp['ingresos_brutos'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($totales_fp['ingresos_netos'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($totales_fp['egresos_brutos'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(30, 4, $this->show_precio($totales_fp['egresos_netos'],$this->empresa->coddivisa), 1, 0, 'R', 0);
        //Inicio de páginas para Ventas
        $this->pdf->AddPage('L');
        $lineas = 1;
        $totalImporte=0;
        $totalRectificativas=0;
        $totalAbonos=0;
        $totalSaldo=0;
        $this->pdf->Cell(270, 4, 'Detalles de Ventas', 1, 0, 'C', 0);
        $this->pdf->Ln();
        $headerDetalleVentas = array('Factura'=>15,FS_NUMERO2=>40,'Cliente'=>60,'Pagada'=>15, 'Importe'=>25,'Rect.'=>25, 'Abonos'=>25, 'Saldo'=>25, 'Fecha Doc.'=>20, 'Fecha Pago'=>20);
        $this->pdfHeader($headerDetalleVentas);
        foreach($this->detalle['ventas'] as $factura){
            if($lineas == 40){
                $this->pdf->AddPage('L');
                $this->pdf->Cell(270, 4, 'Detalles de Ventas', 1, 0, 'C', 0);
                $this->pdf->Ln();
                $this->pdfHeader($headerDetalleVentas);
                $lineas=1;
            }
            $factura->saldo = ($factura->total+$factura->rectificativa)-$factura->abonos;
            $totalImporte+=$factura->total;
            $totalRectificativas+=$factura->rectificativa;
            $totalAbonos+=$factura->abonos;
            $totalSaldo+=$factura->saldo;
            $this->pdf->Cell(15, 4, $factura->idfactura, 1, 0, 'L', 0);
            $this->pdf->Cell(40, 4, $factura->numero2, 1, 0, 'L', 0);
            $this->pdf->Cell(60, 4, $factura->nombrecliente, 1, 0, 'L', 0);
            $this->pdf->Cell(15, 4, ($factura->pagada)?'Pagada':'Pendiente', 1, 0, 'L', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->total,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->rectificativa,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->abonos,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->saldo,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(20, 4, $factura->fecha, 1, 0, 'L', 0);
            $this->pdf->Cell(20, 4, $factura->fecha_pago, 1, 0, 'L', 0);
            $this->pdf->Ln();
            $lineas++;
        }
        if($lineas>=38){
            $this->pdf->AddPage('L');
        }
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(130, 4, 'Total Ingresos', 1, 0, 'L', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalImporte,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalRectificativas,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalAbonos,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalSaldo,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Ln();
        $this->pdf->Cell(180, 4, '', 1, 0, 'L', 0);
        $this->pdf->Cell(50, 4, $this->show_precio(($totalAbonos+$totalSaldo),$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->AddPage('L');
        $lineas = 1;
        $totalImporteFaltantes=0;
        $totalAbonosFaltantes=0;
        $totalSaldoFaltantes=0;
        $this->pdf->Cell(270, 4, 'Detalle de Faltantes', 1, 0, 'C', 0);
        $this->pdf->Ln();
        $headerDetalleFaltantes = array('Factura'=>15,FS_NUMERO2=>40,'Conductor'=>60,'Pagada'=>15, 'Importe'=>25,''=>25, 'Abonos'=>25, 'Saldo'=>25, 'Fecha Doc.'=>20, 'Fecha Pago'=>20);
        $this->pdfHeader($headerDetalleVentas);
        foreach($this->detalle['faltantes'] as $factura){
            if($lineas == 40){
                $this->pdf->AddPage('L');
                $this->pdf->Cell(270, 4, 'Detalle de Faltantes', 1, 0, 'C', 0);
                $this->pdf->Ln();
                $this->pdfHeader($headerDetalleFaltantes);
                $lineas=1;
            }
            $totalImporteFaltantes+=$factura->importe;
            $totalAbonosFaltantes+=$factura->importe_abonos;
            $totalSaldoFaltantes+=$factura->importe_saldo;
            $this->pdf->Cell(15, 4, $factura->idrecibo, 1, 0, 'L', 0);
            $this->pdf->Cell(40, 4, '', 1, 0, 'L', 0);
            $this->pdf->Cell(60, 4, $factura->conductor_nombre, 1, 0, 'L', 0);
            $this->pdf->Cell(15, 4, ucfirst($factura->pagada), 1, 0, 'L', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->importe,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, '', 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->importe_abonos,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->importe_saldo,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(20, 4, $factura->fecha, 1, 0, 'L', 0);
            $this->pdf->Cell(20, 4, $factura->fechap, 1, 0, 'L', 0);
            $this->pdf->Ln();
            $lineas++;
        }
        if($lineas>=38){
            $this->pdf->AddPage('L');
        }
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(130, 4, 'Total Faltantes', 1, 0, 'L', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalImporteFaltantes,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, '', 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalAbonosFaltantes,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalSaldoFaltantes,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Ln();
        $this->pdf->Cell(180, 4, '', 1, 0, 'L', 0);
        $this->pdf->Cell(50, 4, $this->show_precio($totalSaldoFaltantes,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->AddPage('L');
        if($lineas>=38){
            $this->pdf->AddPage('L');
        }
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(180, 4, 'Total Ingreso Neto', 1, 0, 'L', 0);
        $this->pdf->Cell(50, 4, $this->show_precio(($totalAbonos+$totalSaldo)-$totalSaldoFaltantes,$this->empresa->coddivisa), 1, 0, 'R', 0);
        //Inicio de paginas para Compras
        $this->pdf->AddPage('L');
        $items = 1;
        $totalImporteCompras=0;
        $totalRectificativasCompras=0;
        $totalAbonosCompras=0;
        $totalSaldoCompras=0;
        $headerDetalleCompras = array('Factura'=>15,FS_NUMERO2=>40,'Proveedor'=>60,'Pagada'=>15, 'Importe'=>25,'Rect.'=>25, 'Abonos'=>25, 'Saldo'=>25, 'Fecha Doc.'=>20, 'Fecha Pago'=>20);
        $this->pdf->Cell(270, 4, 'Detalles de Compras', 1, 0, 'C', 0);
        $this->pdf->Ln();
        $this->pdfHeader($headerDetalleCompras);
        foreach($this->detalle['compras'] as $factura){
            if($items == 40){
                $this->pdf->AddPage('L');
                $this->pdf->Cell(270, 4, 'Detalles de Compras', 1, 0, 'C', 0);
                $this->pdf->Ln();
                $this->pdfHeader($headerDetalleCompras);
                $items=1;
            }
            $factura->saldo = ($factura->total+$factura->rectificativa)-$factura->abonos;
            $totalImporteCompras+=$factura->total;
            $totalRectificativasCompras+=$factura->rectificativa;
            $totalAbonosCompras+=$factura->abonos;
            $totalSaldoCompras+=$factura->saldo;
            $this->pdf->Cell(15, 4, $factura->idfactura, 1, 0, 'L', 0);
            $this->pdf->Cell(40, 4, $factura->numproveedor, 1, 0, 'L', 0);
            $this->pdf->Cell(60, 4, $factura->nombre, 1, 0, 'L', 0);
            $this->pdf->Cell(15, 4, ($factura->pagada)?'Pagada':'Pendiente', 1, 0, 'L', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->total,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->rectificativa,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->abonos,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(25, 4, $this->show_precio($factura->saldo,$this->empresa->coddivisa), 1, 0, 'R', 0);
            $this->pdf->Cell(20, 4, $factura->fecha, 1, 0, 'L', 0);
            $this->pdf->Cell(20, 4, $factura->fecha_pago, 1, 0, 'L', 0);
            $this->pdf->Ln();
            $items++;
        }
        if($items>=38){
            $this->pdf->AddPage('L');
        }
        $this->pdf->SetFont('courier', 'B');
        $this->pdf->Cell(130, 4, 'Total', 1, 0, 'L', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalImporteCompras,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalRectificativasCompras,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalAbonosCompras,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(25, 4, $this->show_precio($totalSaldoCompras,$this->empresa->coddivisa), 1, 0, 'R', 0);
        $this->pdf->Cell(180, 4, '', 1, 0, 'L', 0);
        $this->pdf->Cell(50, 4, $this->show_precio(($totalAbonosCompras+$totalSaldoCompras),$this->empresa->coddivisa), 1, 0, 'R', 0);

        //Guardamos el PDF
        $this->pdf->Output($this->pathNamePDF,'F');
    }

    private function pdfHeader($header){
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(153, 153, 153);
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->SetFont('courier', 'B');
        //Cabecera
        foreach($header as $text=>$width){
            $this->pdf->Cell($width, 1, $text, 1, 0, 'C', 1);
        }
        $this->pdf->SetFont('courier', '', 9);
        //$this->pdf->Cell(100, 6, '', 0, 0, 'C', 0);
        $this->pdf->Ln();
        // Color and font restoration
        $this->pdf->SetFillColor(224, 235, 255);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont('courier','',8);

    }

    private function generar_formas_pago(){
        foreach($this->fp->all() as $fp){
            $this->ingresos_condpago[$fp->codpago] = 0;
            $this->cobros_condpago[$fp->codpago] = 0;
            $this->egresos_condpago[$fp->codpago] = 0;
            $this->pagos_condpago[$fp->codpago] = 0;
        }
    }

    /**
     * //Buscamos todos los ingresos, ya seán por ventas o por cobros de faltantes
     */
    private function ingresos(){
        $this->total_ingresos = 0;
        $this->total_cobros = 0;
        $this->total_pendientes_cobro = 0;
        $this->total_ventas = 0;
        $this->total_faltantes_ventas = 0;
        $this->pagadas['ventas'] = 0;
        $this->pagadas['faltantes_ventas'] = 0;
        $this->pendientes['ventas'] = 0;
        $this->pendientes['faltantes_ventas'] = 0;
        $this->detalle['ventas'] = array();
        //Obtenemos las ventas que no estén anuladas y sacamos las que estén o no pagadas
        $query_ventas = "fecha >= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_desde)))
                ." AND fecha <= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_hasta)))
                ." AND codalmacen = ".$this->facturascli->var2str($this->codalmacen)
                ." AND anulada = FALSE and idfacturarect IS NULL ORDER BY fecha";
        $sql_ventas = "SELECT * FROM facturascli WHERE $query_ventas";
        $lista_ventas = $this->db->select($sql_ventas);
        if($lista_ventas){
            foreach($lista_ventas as $d){
                $factura = new factura_cliente($d);
                $factura->total = ($this->empresa->coddivisa == $factura->coddivisa)?$factura->total:$this->euro_convert($this->divisa_convert($factura->total, $factura->coddivisa, 'EUR'));
                $factura->fecha_pago = '';
                $factura->abonos = 0;
                if($factura->pagada){
                    if($this->tesoreria){
                        require_model('recibo_cliente.php');
                        require_model('recibo_factura.php');
                        $recibos = new recibo_cliente();
                        /*
                        $recibos_factura = new recibo_factura();
                        $rec0 = $recibos->all_from_factura($factura->idfactura);
                        foreach($rec0 as $r){
                            if(\date('Y-m-d',strtotime($r->fecha))>=\date('Y-m-d',strtotime($this->f_desde)) AND \date('Y-m-d',strtotime($r->fecha))<=\date('Y-m-d',strtotime($this->f_hasta))){

                            }
                        }
                         *
                         */
                        $recibo_pago = $recibos->all_from_factura($factura->idfactura);
                        $pago_venta = ($recibo_pago)?$recibo_pago[0]:FALSE;
                    }else{
                        $pago_venta = $factura->get_asiento_pago();
                    }
                    if($pago_venta){
                        $fecha_pago = ($this->tesoreria)?$pago_venta->fechap:$pago_venta->fecha;
                        if(\date('Y-m-d',strtotime($fecha_pago))>=\date('Y-m-d',strtotime($this->f_desde)) AND \date('Y-m-d',strtotime($fecha_pagop))<=\date('Y-m-d',strtotime($this->f_hasta))){
                            //Esta pagada a la fecha buscada
                            $this->total_cobros += $factura->total;
                            $this->pagadas['ventas'] += $factura->total;
                            $this->cobros_condpago[$factura->codpago] += $factura->total;
                            $factura->fecha_pago = $fecha_pago;
                            $factura->abonos = $factura->total;
                        }else{
                            //Esta pendiente a la fecha buscada
                            $this->total_pendientes_cobro += $factura->total;
                            $this->pendientes['ventas'] += $factura->total;
                        }
                    }else{
                        $this->total_pendientes_cobro += $factura->total;
                        $this->pendientes['ventas'] += $factura->total;
                    }
                }else{
                    $this->total_pendientes_cobro += $factura->total;
                    $this->pendientes['ventas'] += $factura->total;
                }
                $factura->rectificativa = 0;
                $rectificativas = $factura->get_rectificativas();
                if($rectificativas){
                    $total_rectificativas = 0;
                    foreach($rectificativas as $rectificativa){
                        if(\date('Y-m-d',strtotime($rectificativa->fecha))>=\date('Y-m-d',strtotime($this->f_desde)) AND \date('Y-m-d',strtotime($rectificativa->fecha))<=\date('Y-m-d',strtotime($this->f_hasta))){
                            $this->total_cobros += $rectificativa->total;
                            $this->pagadas['ventas'] += $rectificativa->total;
                            $this->cobros_condpago[$rectificativa->codpago] += $rectificativa->total;
                            $total_rectificativas += $rectificativa->total;
                            $factura->fecha_pago = $rectificativa->fecha;
                            if(round($rectificativa->total+$factura->total,0) == 0){
                                $factura->abonos = 0;
                            }else{
                                $factura->abonos += $rectificativa->total;
                            }
                        }else{
                            //Si no estan en fecha las restamos del total de cobros y las sumamos a los pendientes,
                            //Como esta en valor negativo en total cobros se suma y en pendientes se resta
                            $factura->abonos += $rectificativa->total;
                            $factura->fecha_pago = '';
                            $this->pagadas['ventas'] += $rectificativa->total;
                            $this->total_cobros += $rectificativa->total;
                            $this->cobros_condpago[$rectificativa->codpago] += $rectificativa->total;
                            $this->total_pendientes_cobro -= $rectificativa->total;
                            $this->pendientes['ventas'] -= $rectificativa->total;
                        }
                    }
                    $factura->rectificativa = $total_rectificativas;

                }
                $this->detalle['ventas'][] = $factura;
                $this->total_ventas += $factura->total;
                $this->total_ingresos += $factura->total;
                $this->ingresos_condpago[$factura->codpago] += $factura->total;
            }
        }
        $this->detalle['faltantes'] = array();
        //Obtenemos los cobros de faltantes
        $recibos_faltantes = $this->faltantes->buscar($this->empresa->id, $this->codalmacen, $this->f_desde, $this->f_hasta, FALSE, FALSE);
        if($recibos_faltantes){
            foreach($recibos_faltantes as $faltante){
                if($faltante->estado == 'pagado' and ($faltante->fechap>=\date('Y-m-d',strtotime($this->f_desde)) AND $faltante->fechap>=\date('Y-m-d',strtotime($this->f_hasta)))){
                    $faltante->importe = ($this->empresa->coddivisa == $faltante->coddivisa)?$faltante->importe:$this->euro_convert($this->divisa_convert($faltante->importe, $faltante->coddivisa, 'EUR'));
                    $this->total_cobros += $faltante->importe;
                    $this->pagadas['faltantes_ventas'] += $faltante->importe;
                    $this->cobros_condpago['CONT'] += $faltante->importe;
                }else{
                    $this->total_pendientes_cobro += $faltante->importe;
                    $this->pendientes['faltantes_ventas'] += $faltante->importe;
                    $this->total_cobros -= $faltante->importe;
                    $this->pagadas['faltantes_ventas'] -= $faltante->importe;
                }
                $this->total_faltantes_ventas += $faltante->importe;
                $this->total_ingresos -= $faltante->importe;
                $this->cobros_condpago['CONT'] -= $faltante->importe;
                $this->detalle['faltantes'][] = $faltante;
            }
            $this->total_general += $this->total_ingresos;
            $this->ingresos_condpago['CONT'] += $faltante->importe;
        }
    }

    private function egresos(){
        $this->total_egresos = 0;
        $this->total_pagos = 0;
        $this->total_pendientes_pago = 0;
        $this->total_compras = 0;
        $this->total_faltantes_compras = 0;
        $this->pagadas['compras'] = 0;
        $this->pagadas['faltantes_compras'] = 0;
        $this->pendientes['compras'] = 0;
        $this->pendientes['faltantes_compras'] = 0;
        $this->detalle['compras'] = array();
        //Obtenemos las compras que no estén anuladas y sacamos las que estén o no pagadas
        $query_compras = "fecha >= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_desde)))
                ." AND fecha <= ".$this->facturascli->var2str(\date('Y-m-d',strtotime($this->f_hasta)))
                ." AND codalmacen = ".$this->facturascli->var2str($this->codalmacen)
                ." AND anulada = FALSE ORDER BY fecha";
        $sql_compras = "SELECT * FROM facturasprov WHERE $query_compras";
        $lista_compras = $this->db->select($sql_compras);
        //Obtenemos las facturas de compra por pagar
        if($lista_compras){
            foreach($lista_compras as $f){
                $factura = new factura_proveedor($f);
                $factura->total = ($this->empresa->coddivisa == $factura->coddivisa)?$factura->total:$this->euro_convert($this->divisa_convert($factura->total, $factura->coddivisa, 'EUR'));
                $factura->rectificativa = 0;
                $factura->abonos = 0;
                $factura->fecha_pago = 0;
                if($factura->pagada and $factura->idfacturarect == ''){
                    $pago_compra = $factura->get_asiento_pago();
                    if($pago_compra){
                        if(\date('Y-m-d',strtotime($pago_compra->fecha))>=\date('Y-m-d',strtotime($this->f_desde)) AND \date('Y-m-d',strtotime($pago_compra->fecha))<=\date('Y-m-d',strtotime($this->f_hasta))){
                            //Esta pagada a la fecha buscada
                            $this->total_pagos += $factura->total;
                            $this->pagadas['compras'] += $factura->total;
                            $this->pagos_condpago[$factura->codpago] += $factura->total;
                            $factura->fecha_pago = ($this->tesoreria)?$pago_compra->fechap:$pago_compra->fecha;
                        }else{
                            //Esta pendiente a la fecha buscada
                            $this->total_pendientes_pago += $factura->total;
                            $this->pendientes['compras'] += $factura->total;
                        }
                    }else{
                        //Asumimos que va aparecer en esta fecha
                        $this->total_pagos += $factura->total;
                        $this->pagadas['compras'] += $factura->total;
                        $this->pagos_condpago[$factura->codpago] += $factura->total;
                    }
                }else{
                    $this->total_pendientes_pago += $factura->total;
                    $this->pendientes['compras'] += $factura->total;
                }
                $this->detalle['compras'][] = $factura;
                $this->total_egresos += $factura->total;
                $this->total_compras += $factura->total;
                $this->egresos_condpago[$factura->codpago] += $factura->total;
            }
        }
        $this->total_general += $this->total_egresos;
    }

    public function shared_extensions(){

    }
}
