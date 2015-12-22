<?php
/*
 * Copyright (C) 2015 Joe Nilson <joenilson@gmail.com>
 *
 *  * This program is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as
 *  * published by the Free Software Foundation, either version 3 of the
 *  * License, or (at your option) any later version.
 *  *
 *  * This program is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See th * e
 *  * GNU Affero General Public License for more details.
 *  * 
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */
require_model('distribucion_transporte.php');
require_model('distribucion_lineastransporte.php');
require_model('distribucion_ordencarga_facturas.php');
require_model('distribucion_ordencarga.php');
require_model('distribucion_lineasordencarga.php');
/**
 * Description of helper_transportes
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class helper_transportes extends fs_controller {
    
    public function __construct() {
        parent::__construct(__CLASS__, 'Helper Transportes', 'distribucion', FALSE, FALSE);
    }
    
    public function private_core() {
        
    }
    
    public function cabecera_transporte($transporte){
        setlocale(LC_ALL, 'es_ES.UTF-8');
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 14px;" colspan="4">';
        $table.= '<b>Transporte</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>Empresa:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= $this->empresa->nombre;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 10px;">';
        $table.= '<b>Direcci&oacute;n:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 10px;">';
        $table.= $this->empresa->direccion;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>RNC:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= $this->empresa->cifnif;
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;" align="right">';
        $table.= '<b>Teléfono:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" style="font-size: 10px;" align="right">';
                $table.= $this->empresa->telefono;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>Conduce:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= str_pad($transporte->idtransporte,10,"0",STR_PAD_LEFT);
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 10px;">';
        $table.= '<b>Fecha de Reparto:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 10px;">';
        $table.= strftime("%A %d, %B %Y", strtotime($transporte->fecha));
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>Almacén Origen:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= $transporte->codalmacen;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 10px;">';
        $table.= '<b>Almacén Destino:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 10px;">';
        $table.= $transporte->codalmacen_dest;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>Unidad:</b>';
        $table.= '</td>';
        $table.= '<td width="20%">';
        $table.= $transporte->unidad;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 10px;">';
        $table.= '<b>Conductor:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 10px;">';
        $table.= $transporte->conductor_nombre;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        $table.= '<br /><hr />';
        return $table;
    }
    
    public function contenido_transporte($lineastransporte){
        $table= '<table width: 100%;>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="20%">';
        $table.= '<b>Referencia</b>';
        $table.= '</td>';
        $table.= '<td width="40%">';
        $table.= '<b>Producto</b>';
        $table.= '</td>';
        $table.= '<td width="20%" align="right">';
        $table.= '<b>Cantidad</b>';
        $table.= '</td>';
        $table.= '<td width="20%" align="right">';
        $table.= '<b>Monto</b>';
        $table.= '</td>';
        $table.= '</tr>';
        $maxLineas = 34;
        
        foreach($lineastransporte as $key=>$linea){
            $table.= '<tr style="font-size: 10px;">';
            $table.= '<td width="20%">';
            $table.= $linea->referencia;
            $table.= '</td>';
            $table.= '<td width="40%">';
            $table.= $linea->descripcion;
            $table.= '</td>';
            $table.= '<td width="20%" align="right">';
            $table.= number_format($linea->cantidad,2,".",",");
            $table.= '</td>';
            $table.= '<td width="20%" align="right">';
            $table.= number_format($linea->importe,2,".",",");
            $table.= '</td>';
            $table.= '</tr>';
            $maxLineas--;
        }
        $table.= '</table>';
        for($x=0; $x<$maxLineas; $x++){
            $table.="<br />";
        }
        $table.= '<hr />';
        
        return $table;
    }
    
    public function pie_transporte($transporte){
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="right" style="font-size: 10px;" colspan="5">';
        $table.= '<b>Total Cantidad:</b> &nbsp;'.number_format($transporte->totalcantidad,2,".",",");
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="right" style="font-size: 10px;" colspan="5">';
        $table.= '<b>Total Monto:</b> &nbsp;'.number_format($transporte->totalimporte,2,".",",");
        $table.= '<br /><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td style="font-size: 10px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td style="font-size: 10px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td style="font-size: 10px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 10px;">';
        $table.= '<b>Firma Distribuci&oacute;n</b><br />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td align="center" style="font-size: 10px;">';
        $table.= '<b>Firma Seguridad</b><br />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td align="center" style="font-size: 10px;">';
        $table.= '<b>Firma Almac&eacute;n</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        return $table;
    }
    
    public function cabecera_liquidacion($transporte){
        setlocale(LC_ALL, 'es_ES.UTF-8');
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 14px;" colspan="4">';
        $table.= '<b>Liquidación del Transporte del '.strftime("%A %d, %B %Y", strtotime($transporte->fecha)).'</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>Empresa:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= $this->empresa->nombre;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 10px;">';
        $table.= '<b>Direcci&oacute;n:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 10px;">';
        $table.= $this->empresa->direccion;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>RNC:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= $this->empresa->cifnif;
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;" align="right">';
        $table.= '<b>Teléfono:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" style="font-size: 10px;" align="right">';
        $table.= $this->empresa->telefono;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>Conduce:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= str_pad($transporte->idtransporte,10,"0",STR_PAD_LEFT);
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 10px;">';
        $table.= '<b>Fecha de Reparto:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 10px;">';
        $table.= strftime("%A %d, %B %Y", strtotime($transporte->fecha));
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>Almacén Origen:</b>';
        $table.= '</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= $transporte->codalmacen;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 10px;">';
        $table.= '<b>Almacén Destino:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 10px;">';
        $table.= $transporte->codalmacen_dest;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<b>Unidad:</b>';
        $table.= '</td>';
        $table.= '<td width="20%">';
        $table.= $transporte->unidad;
        $table.= '</td>';
        $table.= '<td width="20%" align="right" style="font-size: 10px;">';
        $table.= '<b>Conductor:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right" style="font-size: 10px;">';
        $table.= $transporte->conductor_nombre;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        $table.= '<br /><hr />';
        return $table;
    }
    
    public function contenido_liquidacion($lineastransporte){
        $table= '<table width: 100%;>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="20%">';
        $table.= '<b>Referencia</b>';
        $table.= '</td>';
        $table.= '<td width="40%">';
        $table.= '<b>Producto</b>';
        $table.= '</td>';
        $table.= '<td width="20%" align="right">';
        $table.= '<b>Cantidad</b>';
        $table.= '</td>';
        $table.= '<td width="20%" align="right">';
        $table.= '<b>Monto</b>';
        $table.= '</td>';
        $table.= '</tr>';
        $maxLineas = 30;
        
        foreach($lineastransporte as $key=>$linea){
            $table.= '<tr style="font-size: 10px;">';
            $table.= '<td width="20%">';
            $table.= $linea->referencia;
            $table.= '</td>';
            $table.= '<td width="40%">';
            $table.= $linea->descripcion;
            $table.= '</td>';
            $table.= '<td width="20%" align="right">';
            $table.= number_format($linea->cantidad,2,".",",");
            $table.= '</td>';
            $table.= '<td width="20%" align="right">';
            $table.= number_format($linea->importe,2,".",",");
            $table.= '</td>';
            $table.= '</tr>';
            $maxLineas--;
        }
        $table.= '</table>';
        for($x=0; $x<$maxLineas; $x++){
            $table.="<br />";
        }
        $table.= '<hr />';
        
        return $table;
    }
    
    public function pie_liquidacion($transporte,$faltante){
        $table= '<table style="width: 100%;">';
        $table.= '<tr>';
        $table.= '<td align="right" style="font-size: 10px;" colspan="5">';
        $table.= '<b>Total Cantidad:</b> &nbsp;'.number_format($transporte->totalcantidad,2,".",",");
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="right" style="font-size: 10px;" colspan="5">';
        $table.= '<b>Total Monto:</b> &nbsp;'.number_format($transporte->totalimporte,2,".",",");
        $table.= '<br /><br />';
        $table.= '</td>';
        $table.= '</tr>';
        if($faltante){
            $table.= '<tr style="color: white; background-color: #000; font-size: 9px;">';
            $table.= '<td align="center">';
            $table.= '<b>Faltante generado por:</b>';
            $table.= '</td>';
            $table.= '<td align="right" colspan="5">';
            $table.= $this->show_precio($faltante->importe, $faltante->coddivisa);
            $table.= '</td>';
            $table.= '</tr>';
            $table.= '<tr style="color: white; background-color: #000; font-size: 10px;">';
            $table.= '<td align="center" colspan="6">';
            $table.= '<br />Al firmar este documento acepto la responsabilidad de pagar este importe faltante de la liquidaci&oacute;n';
            $table.= '</td>';
            $table.= '</tr>';
        }
        $table.= '<tr style="margin-top: 30px;">';
        $table.= '<td width="25%" style="font-size: 10px;">';
        $table.= '<br /><br /><br /><hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td width="20%" style="font-size: 10px;">';
        $table.= '<br /><br /><br /><hr />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td width="25%" style="font-size: 10px;">';
        $table.= '<br /><br /><br /><hr />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="center" width="25%" style="font-size: 8px;">';
        $table.= '<b>'.$transporte->conductor_nombre.'<br />'.$transporte->conductor.'</b>';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td align="center" width="20%" style="font-size: 10px;">';
        $table.= '<b>Firma Liquidador</b><br />';
        $table.= '</td>';
        $table.= '<td width="15%">&nbsp;</td>';
        $table.= '<td width="25%" align="center" style="font-size: 10px;">';
        $table.= '<b>Firma Contabilidad</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        return $table;
    }
}
