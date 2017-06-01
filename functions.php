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
if( !function_exists('remote_printer') )
{
   /**
    * Esta función se utilizará para la impresión de facturas en una impresora matricial
    */
   function remote_printer()
   {
      if( isset($_REQUEST['impresora']) )
      {
         require_model('impresoras.php');

         $t0 = new impresoras();
         $impresora_elegida = \filter_input(INPUT_POST, 'impresora');
         $impresora = $t0->get($impresora_elegida);
         if($impresora)
         {
            //echo $terminal->tickets;
         }
         else
            echo 'ERROR: terminal no encontrado.';
      }
   }
}
