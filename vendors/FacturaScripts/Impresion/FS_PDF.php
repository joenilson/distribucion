<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
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

namespace FacturaScripts\Impresion;
require_once 'plugins/distribucion/vendors/fpdf181/fpdf.php';
/**
 * Description of FS_PDF
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class FS_PDF extends \FPDF{
    //Addon for FPDF from: http://fpdf.de/downloads/add-ons/page-groups.html
    protected $NewPageGroup;   // variable indicating whether a new group was requested
    protected $PageGroups;     // variable containing the number of pages of the groups
    protected $CurrPageGroup;  // variable containing the alias of the current page group
    public $verlogotipo = 0;
    public $documento_nombre;
    public $documento_numero;
    public $documento_codigo;
    public $documento_cabecera_lineas;
    public $x_pos;
    public $y_pos;
    public $mostrar_borde;
    public $mostrar_colores;
    public $mostrar_linea;
    /**
     *
     * @param string $orientation
     * @param string $unit
     * @param string $size
     */
    public function __construct($orientation = 'P', $unit = 'mm', $size = 'LETTER', $file = 'documento.pdf') {
        parent::__construct($orientation, $unit, $size);
    }

    public function addCabecera(){
        $this->StartPageGroup();
        $this->AddPage();
        //Colocamos el marcador de página en la linea 8
        $this->y_pos = 8;
    }

    public function addEmpresaInfo(\empresa $empresa){
        $x1 = ($this->verlogotipo == '1')?50:10;
        $y1 = $this->y_pos;
        $this->SetXY( $x1, $y1 );
        $this->SetFont('Arial','B',10);
        $this->SetTextColor(0);
        $length1 = $this->GetStringWidth($empresa->nombre);
        $this->Cell( $length1, 4, utf8_decode($empresa->nombre));
        $y1+=4;
        $this->SetXY( $x1, $y1);
        $length2 = $this->GetStringWidth(FS_CIFNIF.': '.$empresa->cifnif);
        $this->SetFont('Arial','',9);
        $this->Cell($length2, 4, utf8_decode(FS_CIFNIF.': '.$empresa->cifnif));
        $y1+=4;
        $this->SetXY( $x1, $y1);
        $this->SetFont('Arial','',9);
        $length3 = $this->GetStringWidth( $empresa->direccion.' - '.$empresa->ciudad.' - '.$empresa->provincia );
        $this->MultiCell($length3, 4, utf8_decode($empresa->direccion.' - '.$empresa->ciudad.' - '.$empresa->provincia));
        $y1 += ($this->getY() - $y1);
        if ($empresa->telefono != '')
        {
            $this->SetXY($x1, $y1);
            $this->SetFont('Arial','',9);
            $this->Cell($length2, 4, utf8_decode('Teléfono: '.$empresa->telefono));
            $this->SetTextColor(0);
            $this->SetFont('');
            $y1+=4;
        }

        if ($empresa->email != '')
        {
            $this->SetXY($x1, $y1);
            $this->SetFont('Arial','',9);
            $this->Write(5,'Email: ');
            $this->SetTextColor(0,0,255);
            $this->Write(5, utf8_decode($empresa->email), 'mailto:' . $empresa->email);
            $this->SetTextColor(0);
            $this->SetFont('');
        }

        if ($empresa->web != '')
        {
            $this->SetXY($x1+$this->GetStringWidth($empresa->email)+12, $y1);
            $this->SetFont('Arial','',9);
            $this->Write(5,'Web: ');
            $this->SetTextColor(0,0,255);
            $this->Write(5, utf8_decode($empresa->web), $empresa->web);
            $this->SetTextColor(0);
            $this->SetFont('');
        }
    }

    public function addDocumentoInfo(){
        $r1  = $this->w - 80;
        $r2  = $r1 + 70;
        $y1  = $this->y_pos;
        $y2  = $y1 + 16;

        $szfont = 10;
        $loop   = 0;

        while ( $loop == 0 )
        {
           $this->SetFont("Arial", "B",$szfont);
           $sz = $this->GetStringWidth($this->documento_nombre);
           if ( ($r1+$sz) > $r2 ){
              $szfont--;
           }else{
              $loop++;
           }
        }
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.1);
        $this->Rect($r1, $y1,($r2 - $r1), $y2, 'B');
        $y1++;
        $this->SetFont( "Arial", "B", 10 );
        $this->SetXY( $r1+1, $y1+3);
        $this->MultiCell(67,5, utf8_decode(strtoupper($this->documento_nombre)), 0, "C");
        $y1+=4;
        $this->SetXY( $r1+1, $y1+3);
        $this->Cell(67,5, utf8_decode($this->documento_numero), 0, 0, "C" );
        $this->y_pos = ($y1+$y2);
    }

    public function addCabeceraInfo($cabecera){
        $r1 = 10;
        $r2  = $this->w - 10;
        $y1  = $this->y_pos;
        $y2  = 5;
        $y1++;
        $this->SetXY( $r1, $y1);
        foreach($cabecera as $linea){
            $this->SetFont( "Arial", "B", 10 );
            $this->Cell(30,5, utf8_decode($linea['label']), 0, 0, 'R' );
            $this->SetFont( "Arial", "", 10 );
            $this->Cell($linea['size'],5, utf8_decode($linea['valor']), 0, 0, 'L' );
            if($linea['salto_linea']){
                $y1+=5;
                $this->SetXY( $r1, $y1);
                $y2+=5;
            }
        }

        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.1);
        $this->Rect($r1, $y1-($y2-2),($r2 - $r1), $y2, 'B');
        $this->y_pos = $y1+5;
    }

    public function AddCabeceraLineas(){
        $r1 = 10;
        $r2  = $this->w - 10;
        $y1  = $this->y_pos;
        $y2  = 5;

        //Verificamos el total de lineas a imprimir que no se salga del margen
        $total_lineas = 0;
        foreach($this->documento_cabecera_lineas as $c){
            $total_lineas += $c['size'];
        }
        if($total_lineas > ($this->w-20)){
            $cantidad_filas = count($this->documento_cabecera_lineas);
            $exceso_longitud = $total_lineas-($this->w-20);
            $eliminar_por_linea = ceil($exceso_longitud/$cantidad_filas);
            for($i = 0; $i<$cantidad_filas; $i++){
                $this->documento_cabecera_lineas[$i]['size'] -= $eliminar_por_linea;
            }
        }

        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.1);
        $this->Rect($r1, $y1,($r2 - $r1), $y2, 'B');
        $this->SetXY($r1, $y1);
        $this->SetFont( "Arial", "B", 10 );
        foreach($this->documento_cabecera_lineas as $cab){
            $this->Cell($cab['size'],5, utf8_decode($cab['descripcion']),0,0,$cab['align']);
        }
        $this->y_pos = $y1+6;
    }

    public function addDetalleLineas($lineas){
        $r1 = 10;
        $y1 = $this->y_pos;
        $this->SetFont("Arial", "", 9);
        foreach($lineas as $linea){
            $r2 = $r1;
            $this->SetXY($r1, $y1);
            foreach($this->documento_cabecera_lineas as $i=>$k){
                $r2 += $k['size'];
                $this->Cell($k['size'],5, ($linea[$i])?utf8_decode($linea[$i]):str_pad('_',($k['size']/3),'_',STR_PAD_BOTH),0,0,$k['align']);
            }
            $y1+=5;
        }
        $this->y_pos = $y1+3;
    }

    public function addTotalesLineas($totales){
        $r1 = 10;
        $y1 = -60;
        $this->SetXY($r1, $y1);
        $this->Line($r1, $this->getY(), $this->w-10, $this->getY());
        $y1++;
        $this->SetFont("Arial", "B", 10);
        $this->SetXY($r1, $y1);
        foreach($this->documento_cabecera_lineas as $c){
            if($c['total']){
                $this->Cell($c['size'],5, utf8_decode(number_format($totales[$c['total_campo']],FS_NF0)),0,0,$c['align']);
            }else{
                $this->Cell($c['size'],5, '',0,0,$c['align']);
            }
            $y1+=5;
        }
        $this->y_pos = $y1+3;
    }

    public function addObservaciones($observaciones){
        $r1 = 10;
        $y1 = $this->y_pos;
        $this->SetXY($r1, -50);
        //$this->Line($r1, $this->getY(), $this->w-10, $this->getY());
        //$y1++;
        if($observaciones){
            $strlength = $this->GetStringWidth('Observaciones: ');
            $this->SetXY($r1, -50);
            $this->SetFont("Arial", "B", 9);
            $this->Cell($strlength+5,5, utf8_decode('Observaciones: '),0,0,'L');
            $this->SetFont("Arial", "", 9);
            $this->MultiCell(($this->w-90),5, utf8_decode($observaciones), 0, "L");
        }else{
            $y1+=5;
        }
        $this->y_pos = $y1+6;
    }

    public function addFirmas($firmas){
        $largo_firma = 0;
        foreach($firmas as $firma){
            if($largo_firma<$this->GetStringWidth($firma)){
                $largo_firma = $this->GetStringWidth($firma);
            }
        }
        $largo_firma+=20;
        $r1 = 10;
        $y1 = $this->y_pos+30;
        $this->SetXY($r1, -30);
        $l1 = $r1;
        foreach($firmas as $firma){
            $this->SetXY($l1, -30);
            $this->Line($l1, $this->getY(), $l1+$largo_firma, $this->getY());
            $this->Cell($largo_firma,5, utf8_decode($firma),0,0,'C');
            $l1 = $l1+$largo_firma+20;
        }
        $this->y_pos = $y1+6;
    }

    public function Footer(){
        $this->SetY(-15);
        $this->Cell(0, 10, utf8_decode('Página ').$this->GroupPageNo().' de '.$this->PageGroupAlias(), 0, 0, 'C');
    }

    public function cerrarArchivo(){

    }

    // create a new page group; call this before calling AddPage()
    public function StartPageGroup()
    {
        $this->NewPageGroup = true;
    }

    // current page in the group
    public function GroupPageNo()
    {
        return $this->PageGroups[$this->CurrPageGroup];
    }

    // alias of the current page group -- will be replaced by the total number of pages in this group
    public function PageGroupAlias()
    {
        return $this->CurrPageGroup;
    }

    public function _beginpage($orientation, $format, $rotation)
    {
        parent::_beginpage($orientation, $format, $rotation);
        if($this->NewPageGroup)
        {
            // start a new group
            $n = sizeof($this->PageGroups)+1;
            $alias = "{nb$n}";
            $this->PageGroups[$alias] = 1;
            $this->CurrPageGroup = $alias;
            $this->NewPageGroup = false;
        }
        elseif($this->CurrPageGroup)
            $this->PageGroups[$this->CurrPageGroup]++;
    }

    public function _putpages()
    {
        $nb = $this->page;
        if (!empty($this->PageGroups))
        {
            // do page number replacement
            foreach ($this->PageGroups as $k => $v)
            {
                for ($n = 1; $n <= $nb; $n++)
                {
                    $this->pages[$n] = str_replace($k, $v, $this->pages[$n]);
                }
            }
        }
        parent::_putpages();
    }
}
