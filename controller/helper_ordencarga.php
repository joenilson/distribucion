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
 * Description of helper_ordencarga
 *
 * @author Joe Nilson <joenilson@gmail.com>
 */
class helper_ordencarga extends fs_controller {
    
    public function __construct() {
        parent::__construct(__CLASS__, 'Helper Ordenes Carga', 'distribucion', FALSE, FALSE);
    }
    
    public function private_core() {
        
    }
    
    public function cabecera($ordencarga){
        setlocale(LC_ALL, 'es_ES');
        $table= '<table width: 100%;>';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 14px;" colspan="2">';
        $table.= '<b>Orden de Carga</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%">';
        $table.= '<b>Orden de Carga:</b>';
        $table.= '</td>';
        $table.= '<td width="20%">';
        $table.= str_pad($ordencarga[0]->idordencarga,10,"0",STR_PAD_LEFT);
        $table.= '</td>';
        $table.= '<td width="20%" align="right">';
        $table.= '<b>Fecha de Reparto:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right">';
        $table.= strftime("%A %d, %B %Y", strtotime($ordencarga[0]->fecha));
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%">';
        $table.= '<b>Almacén Origen:</b>';
        $table.= '</td>';
        $table.= '<td width="20%">';
        $table.= $ordencarga[0]->codalmacen;
        $table.= '</td>';
        $table.= '<td width="20%" align="right">';
        $table.= '<b>Almacén Destino:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right">';
        $table.= $ordencarga[0]->codalmacen_dest;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td width="20%">';
        $table.= '<b>Unidad:</b>';
        $table.= '</td>';
        $table.= '<td width="20%">';
        $table.= $ordencarga[0]->unidad;
        $table.= '</td>';
        $table.= '<td width="20%" align="right">';
        $table.= '<b>Conductor:</b>';
        $table.= '</td>';
        $table.= '<td width="40%" align="right">';
        $table.= $ordencarga[0]->conductor_nombre;
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        $table.= '<br /><br /><hr />';
        return $table;
    }
    
    public function contenido($lineasordencarga){
        $table= '<table width: 100%;>';
        $table.= '<tr style="font-size: 10px;">';
        $table.= '<td width="30%">';
        $table.= '<b>Referencia</b>';
        $table.= '</td>';
        $table.= '<td width="40%">';
        $table.= '<b>Producto</b>';
        $table.= '</td>';
        $table.= '<td width="30%" align="right">';
        $table.= '<b>Cantidad</b>';
        $table.= '</td>';
        $table.= '</tr>';
        $maxLineas = 34;
        
        foreach($lineasordencarga as $key=>$linea){
            $table.= '<tr style="font-size: 10px;">';
            $table.= '<td width="30%">';
            $table.= $linea->referencia;
            $table.= '</td>';
            $table.= '<td width="40%">';
            $table.= $linea->descripcion;
            $table.= '</td>';
            $table.= '<td width="30%" align="right">';
            $table.= number_format($linea->cantidad,2,".",",");
            $table.= '</td>';
            $table.= '</tr>';
            $maxLineas--;
        }
        $table.= '</table>';
        for($x=0; $x<$maxLineas; $x++){
            $table.="<br />";
        }
        $table.= '<hr /><br />';
        
        return $table;
    }
    
    public function pie($ordencarga){
        $table= '<table width: 100%;>';
        $table.= '<tr>';
        $table.= '<td align="left" style="font-size: 10px;" colspan="3">';
        $table.= '<b>Observaciones</b>';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="left" style="font-size: 10px;" colspan="3">';
        $table.= $ordencarga[0]->observaciones.'<br /><br /><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td style="font-size: 10px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '<td width="30%">&nbsp;</td>';
        $table.= '<td style="font-size: 10px;">';
        $table.= '<br /><hr />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '<tr>';
        $table.= '<td align="center" style="font-size: 10px;">';
        $table.= '<b>Firma Distribuci&oacute;n</b><br />';
        $table.= '</td>';
        $table.= '<td width="30%">&nbsp;</td>';
        $table.= '<td align="center" style="font-size: 10px;">';
        $table.= '<b>Firma Almac&eacute;n</b><br />';
        $table.= '</td>';
        $table.= '</tr>';
        $table.= '</table>';
        return $table;
    }
}
