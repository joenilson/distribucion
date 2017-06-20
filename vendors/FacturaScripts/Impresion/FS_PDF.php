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
        $this->AddPage();
    }

    public function addEmpresaInfo(\empresa $empresa){
        $x1 = ($this->verlogotipo == '1')?50:10;
        $y1 = 8;
        $this->SetXY( $x1, $y1 );
        $this->SetFont('Arial','B',10);
        $this->SetTextColor(0);
        $length1 = $this->GetStringWidth($empresa->nombre);
        $this->Cell( $length1, 4, utf8_decode($empresa->nombre));
        $this->SetXY( $x1, $y1 + 4 );
        $length2 = $this->GetStringWidth(FS_CIFNIF.': '.$empresa->cifnif);
        $this->SetFont('Arial','',8);
        $this->Cell($length2, 4, utf8_decode(FS_CIFNIF.': '.$empresa->cifnif));
        $this->SetXY($x1, $y1 + 8 );
        $this->SetFont('Arial','',8);
        $length3 = $this->GetStringWidth( $empresa->direccion.' - '.$empresa->ciudad.' - '.$empresa->provincia );
        $this->MultiCell($length3, 4, utf8_decode($empresa->direccion.' - '.$empresa->ciudad.' - '.$empresa->provincia));

        if ($empresa->email != '')
        {
            $this->SetXY( $x1, $y1 + 73 );
            $this->SetFont('Arial','',8);
            $this->Write(5,'Email: ');
            $this->SetTextColor(0,0,255);
            $this->Write(5, utf8_decode($empresa->email), 'mailto:' . $empresa->email);
            $this->SetTextColor(0);
            $this->SetFont('');
        }

        if ($empresa->web != '')
        {
            $this->SetXY( $x1, $y1 + 77 );
            $this->SetFont('Arial','',8);
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
        $y1  = 6;
        $y2  = $y1 + 20;

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
        $y1++;$y1++;$y1++;$y1++;
        $this->SetXY( $r1+1, $y1+3);
        $this->Cell(67,5, utf8_decode($this->documento_numero), 0, 0, "C" );
    }

    public function addCabeceraInfo($cabecera){
        $r1 = 10;
        $r2  = $this->w - 10;
        $y1  = 35;
        $y2  = $y1;

        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.1);
        $this->Rect($r1, $y1,($r2 - $r1), $y2, 'B');
        $y1++;
        $this->SetXY( $r1, $y1);
        foreach($cabecera as $linea){
            $this->SetFont( "Arial", "B", 10 );
            $this->Cell(30,5, utf8_decode($linea['label']), 0, 0, 'R' );
            $this->SetFont( "Arial", "", 10 );
            $this->Cell($linea['size'],5, utf8_decode($linea['valor']), 0, 0, 'L' );
            if($linea['salto_linea']){
                $y1++;$y1++;$y1++;$y1++;$y1++;
                $this->SetXY( $r1, $y1);
            }
        }
    }

    public function AddCabeceraLineas(){
        $r1 = 10;
        $r2  = $this->w - 10;
        $y1  = 72;
        $y2  = 5;

        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.1);
        $this->Rect($r1, $y1,($r2 - $r1), $y2, 'B');
        //$r1++;
        //$y1++;
        $this->SetXY( $r1, $y1);
        $this->SetFont( "Arial", "B", 10 );
        foreach($this->documento_cabecera_lineas as $cab){
            $this->Cell($cab['size'],5, utf8_decode($cab['descripcion']),0,0,$cab['align']);
        }
    }

    public function addDetalleLineas($lineas){
        $r1 = 10;
        $y1 = 80;
        $this->SetFont( "Arial", "", 10 );
        foreach($lineas as $linea){
            $this->SetXY( $r1, $y1);
            foreach($this->documento_cabecera_lineas as $i=>$k){
                $this->Cell($k['size'],5, utf8_decode($linea[$i]),0,0,$k['align']);
            }
            //$r1++;$r1++;$r1++;
            $y1++;$y1++;$y1++;$y1++;$y1++;
        }
    }

    public function addPie(){
        $this->SetY(-20);
        $this->Cell(0, 6, utf8_decode('Página ').$this->GroupPageNo().' de '.$this->PageGroupAlias(), 0, 0, 'C');
    }

    public function addFirmas(){

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
