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

namespace FacturaScripts;

use FacturaScripts\Impresion\FS_TXT;
use FacturaScripts\Impresion\FS_PDF;
/**
 * Description of PrinterManager
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class PrinterManager {
    public $file;
    public $type;
    public $tmp_dir;
    public $fs_txt;
    public $fs_pdf;
    public $page_size;
    public $page_lines;
    public $page_units = 'mm';
    public $page_orientation;
    public $fileHandler;
    public function __construct(array $info) {
        $this->file = $info['file'];
        $this->type = $info['type'];
        $this->page_size = $info['page_size'];
        $this->page_lines = $info['page_lines'];
        $this->page_orientation = $info['page_orientation'];
        $this->tmp_dir = sys_get_temp_dir();
    }

    public function crearArchivo()
    {
        $opciones['tmp'] = $this->tmp_dir;
        $opciones['file'] = $this->file;
        $opciones['page_size'] = $this->page_size;
        $opciones['page_lines'] = $this->page_lines;
        $opciones['page_orientation'] = $this->page_orientation;
        if($this->type == 'pdf')
        {
            $this->fileHandler = new FS_PDF($this->page_orientation, $this->page_units, $this->page_size, $this->tmp_dir.DIRECTORY_SEPARATOR.$this->file);
        }
        elseif($this->type == 'txt')
        {
            $this->fileHandler = new FS_TXT($this->page_orientation, $this->page_units, $this->page_size, $this->tmp_dir.DIRECTORY_SEPARATOR.$this->file);
        }
        if(!$this->fileHandler){
            return false;
        }
    }

    public function agregarCabecera(\empresa $empresa, array $documento, array $cabecera){
        $this->fileHandler->addEmpresaInfo($empresa);
        $this->fileHandler->addDocumentoInfo($documento);
        $this->fileHandler->addCabeceraInfo($cabecera);
    }

    public function agregarLineas(array $lineas){

    }

    public function agregarPie(array $pie){

    }

    public function agregarFirmas(array $firmas){

    }

    public function mostrarDocumento(){
        $this->fileHandler->Output();
    }
}
