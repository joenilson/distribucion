<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2015-2017  Joe Nilson  joenilson@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('agente.php');
/**
* Agregado para buscar por campos de los agentes por cargo sobre todo
 */
class distribucion_agente extends agente
{
   public function get_activos_por($campo, $valores, $tipoagente = 'VENDEDOR')
   {
      $listagentes = array();
      $campoigual = ($campo == 'codcargo')?"tipoagente":$campo;
      $signo = (is_array($valores))?" IN ":" = ";
      $valor = (is_array($valores))?"('".implode("','", $valores)."')":$this->var2str($valores);
      $tipoagente_valor = (is_array($tipoagente))?"(".implode(",", $tipoagente).")":$this->var2str($tipoagente);
      $signotipoagente = (is_array($tipoagente))?" IN ":" = ";
      $sql = "SELECT agentes.* FROM ".$this->table_name." LEFT JOIN distribucion_organizacion ON (agentes.codagente = distribucion_organizacion.codagente)".
              " WHERE ".$this->table_name.".".$campo.$signo.$valor.
              " AND agentes.codagente not in (select codagente from distribucion_organizacion where $campoigual $signotipoagente $tipoagente_valor);";
      //echo $sql;
      $agentes = $this->db->select($sql);
      if($agentes)
      {
         foreach($agentes as $a)
         {
           $listagentes[] = new agente($a);
         }
         return $listagentes;
      }
      else
         return FALSE;
   }
}