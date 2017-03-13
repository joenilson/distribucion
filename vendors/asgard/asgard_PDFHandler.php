<?php

/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Lesser General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Lesser General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Lesser General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */
require_once('plugins/distribucion/vendors/tcpdf/tcpdf_autoconfig.php');
require_once('plugins/distribucion/vendors/tcpdf/tcpdf.php');
/**
 * Esta clase será una clase agnostica para generar documentos de PDF
 * se coloca dentro de la carpeta vendors/asgard para diferenciarla de las otras librerias en uso
 * se va solicitar una petición formal para que la base de facturascripts traiga una carpeta de vendors 
 * donde poder colocarla y asi bajar el peso de los plugins haciendo uso de herramientas de la base.
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class asgard_PDFHandler {
    public $pdf_formato;
    public $pdf_orientacion;
    public $pdf_documento;
    public $pdf_cabecera;
    public $pdf_iniciocuerpo;
    public $pdf_cuerpo;
    public $pdf_fincuerpo;
    public $pdf_piedepagina;
    public $pdf;
    public function __construct() {
        $this->pdf = new TCPDF();
    }
    
    public function pdf_create($formato = 'letter', $documento = 'doc0.pdf', $orientacion = 'P'){
        $this->pdf_formato = $formato;
        $this->pdf_orientacion = $orientacion;
        $this->pdf_documento = $documento;
        $this->pdf->setPageOrientation($this->pdf_orientacion);
    }
    
    public function pdf_pagina($cabecera = null, $contenido = null, $pie = null){
        $this->pdf->AddPage($this->pdf_orientacion, $this->pdf_formato);
        $this->pdf->writeHTML($cabecera);
        $this->pdf->writeHTML($contenido);
        $this->pdf->writeHTML($pie);
    }
    
    public function pdf_mostrar(){
        $this->pdf->Output($this->pdf_documento,'I');
    }


    public function pdf_constantes(){

    }
}
