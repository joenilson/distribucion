<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
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
require_once 'plugins/facturacion_base/model/core/albaran_cliente.php';
/**
 * Description of albaran_cliente
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class albaran_cliente extends FacturaScripts\model\albaran_cliente{
    /**
     * El codigo de la ruta de atención
     * @var type varchar(10)
     */
    public $codruta;
    public function __construct($t = FALSE) {
        if($t){
            $this->codruta = $t['codruta'];
        }else{
            $this->codruta = null;
        }
        parent::__construct($t);
    }
    
    /**
    * Guarda los datos en la base de datos
    * @return boolean
    */
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura)
                    .", codigo = ".$this->var2str($this->codigo)
                    .", codagente = ".$this->var2str($this->codagente)
                    .", codruta = ".$this->var2str($this->codruta)
                    .", codserie = ".$this->var2str($this->codserie)
                    .", codejercicio = ".$this->var2str($this->codejercicio)
                    .", codcliente = ".$this->var2str($this->codcliente)
                    .", codpago = ".$this->var2str($this->codpago)
                    .", coddivisa = ".$this->var2str($this->coddivisa)
                    .", codalmacen = ".$this->var2str($this->codalmacen)
                    .", codpais = ".$this->var2str($this->codpais)
                    .", coddir = ".$this->var2str($this->coddir)
                    .", codpostal = ".$this->var2str($this->codpostal)
                    .", numero = ".$this->var2str($this->numero)
                    .", numero2 = ".$this->var2str($this->numero2)
                    .", nombrecliente = ".$this->var2str($this->nombrecliente)
                    .", cifnif = ".$this->var2str($this->cifnif)
                    .", direccion = ".$this->var2str($this->direccion)
                    .", ciudad = ".$this->var2str($this->ciudad)
                    .", provincia = ".$this->var2str($this->provincia)
                    .", apartado = ".$this->var2str($this->apartado)
                    .", fecha = ".$this->var2str($this->fecha)
                    .", hora = ".$this->var2str($this->hora)
                    .", neto = ".$this->var2str($this->neto)
                    .", total = ".$this->var2str($this->total)
                    .", totaliva = ".$this->var2str($this->totaliva)
                    .", totaleuros = ".$this->var2str($this->totaleuros)
                    .", irpf = ".$this->var2str($this->irpf)
                    .", totalirpf = ".$this->var2str($this->totalirpf)
                    .", porcomision = ".$this->var2str($this->porcomision)
                    .", tasaconv = ".$this->var2str($this->tasaconv)
                    .", totalrecargo = ".$this->var2str($this->totalrecargo)
                    .", observaciones = ".$this->var2str($this->observaciones)
                    .", ptefactura = ".$this->var2str($this->ptefactura)
                    .", femail = ".$this->var2str($this->femail)
                    .", codtrans = ".$this->var2str($this->envio_codtrans)
                    .", codigoenv = ".$this->var2str($this->envio_codigo)
                    .", nombreenv = ".$this->var2str($this->envio_nombre)
                    .", apellidosenv = ".$this->var2str($this->envio_apellidos)
                    .", apartadoenv = ".$this->var2str($this->envio_apartado)
                    .", direccionenv = ".$this->var2str($this->envio_direccion)
                    .", codpostalenv = ".$this->var2str($this->envio_codpostal)
                    .", ciudadenv = ".$this->var2str($this->envio_ciudad)
                    .", provinciaenv = ".$this->var2str($this->envio_provincia)
                    .", codpaisenv = ".$this->var2str($this->envio_codpais)
                    .", numdocs = ".$this->var2str($this->numdocs)
                    ."  WHERE idalbaran = ".$this->var2str($this->idalbaran).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (idfactura,codigo,codagente,codruta,
               codserie,codejercicio,codcliente,codpago,coddivisa,codalmacen,codpais,coddir,
               codpostal,numero,numero2,nombrecliente,cifnif,direccion,ciudad,provincia,apartado,
               fecha,hora,neto,total,totaliva,totaleuros,irpf,totalirpf,porcomision,tasaconv,
               totalrecargo,observaciones,ptefactura,femail,codtrans,codigoenv,nombreenv,apellidosenv,
               apartadoenv,direccionenv,codpostalenv,ciudadenv,provinciaenv,codpaisenv,numdocs) VALUES "
                    ."(".$this->var2str($this->idfactura)
                    .",".$this->var2str($this->codigo)
                    .",".$this->var2str($this->codagente)
                    .",".$this->var2str($this->codruta)
                    .",".$this->var2str($this->codserie)
                    .",".$this->var2str($this->codejercicio)
                    .",".$this->var2str($this->codcliente)
                    .",".$this->var2str($this->codpago)
                    .",".$this->var2str($this->coddivisa)
                    .",".$this->var2str($this->codalmacen)
                    .",".$this->var2str($this->codpais)
                    .",".$this->var2str($this->coddir)
                    .",".$this->var2str($this->codpostal)
                    .",".$this->var2str($this->numero)
                    .",".$this->var2str($this->numero2)
                    .",".$this->var2str($this->nombrecliente)
                    .",".$this->var2str($this->cifnif)
                    .",".$this->var2str($this->direccion)
                    .",".$this->var2str($this->ciudad)
                    .",".$this->var2str($this->provincia)
                    .",".$this->var2str($this->apartado)
                    .",".$this->var2str($this->fecha)
                    .",".$this->var2str($this->hora)
                    .",".$this->var2str($this->neto)
                    .",".$this->var2str($this->total)
                    .",".$this->var2str($this->totaliva)
                    .",".$this->var2str($this->totaleuros)
                    .",".$this->var2str($this->irpf)
                    .",".$this->var2str($this->totalirpf)
                    .",".$this->var2str($this->porcomision)
                    .",".$this->var2str($this->tasaconv)
                    .",".$this->var2str($this->totalrecargo)
                    .",".$this->var2str($this->observaciones)
                    .",".$this->var2str($this->ptefactura)
                    .",".$this->var2str($this->femail)
                    .",".$this->var2str($this->envio_codtrans)
                    .",".$this->var2str($this->envio_codigo)
                    .",".$this->var2str($this->envio_nombre)
                    .",".$this->var2str($this->envio_apellidos)
                    .",".$this->var2str($this->envio_apartado)
                    .",".$this->var2str($this->envio_direccion)
                    .",".$this->var2str($this->envio_codpostal)
                    .",".$this->var2str($this->envio_ciudad)
                    .",".$this->var2str($this->envio_provincia)
                    .",".$this->var2str($this->envio_codpais)
                    .",".$this->var2str($this->numdocs).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idalbaran = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
}
