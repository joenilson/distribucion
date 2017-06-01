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

/**
 * Description of FS_TXT
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class FS_TXT {
    public $fp;
    public $empresa;
    public $clipro;
    public $documento;
    public $page_size;
    public $lineas;
    public $orientacion;
    public function __construct(array $opciones)
    {
        $file_name = $opciones['tmp'].$opciones['file'];
        $this->page_size = $opciones['page_size'];
        $this->lineas = $opciones['page_lines'];
        $this->orientacion = ($opciones['page_orientation'])?$opciones['page_orientation']:'P';
        $this->fp = fopen($file_name, 'w') or die('CANNOT_OPEN_FILE: '.$file_name);
    }

    public function file_header($empresa,$clipro=false,$documento=false)
    {
        //Dibujamos el esqueleto del documento
        fputs($this->fp, sprintf("%s%30s%s\n\r",'+',str_repeat('-', 30),'+'));
        fputs($this->fp, sprintf("%s%-30s%s\n\r",'+',$empresa->nombre,'+'));
        fputs($this->fp, sprintf("%s%-30s%s\n\r",'+',$empresa->cifnif,'+'));
        fputs($this->fp, sprintf("%s%-30s%s\n\r",'+',$empresa->direccion,'+'));
        fputs($this->fp, sprintf("%s%-30s%s\n\r",'+','','+'));
        fputs($this->fp, sprintf("%s%30s%s\n\r",'+',str_repeat('-', 30),'+'));
    }

    public function file_contents()
    {

    }

    public function file_footer()
    {

    }

    public function file_close()
    {
        fclose($this->fp);
    }
}
