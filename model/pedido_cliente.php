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
require_once 'plugins/presupuestos_y_pedidos/model/core/pedido_cliente.php';
/**
 * Description of pedido_cliente
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class pedido_cliente extends FacturaScripts\model\pedido_cliente {
    /**
     * El codigo de la ruta que se va afectar
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
    
    public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET apartado = ".$this->var2str($this->apartado)
                    . ", cifnif = ".$this->var2str($this->cifnif)
                    . ", ciudad = ".$this->var2str($this->ciudad)
                    . ", codagente = ".$this->var2str($this->codagente)
                    . ", codruta = ".$this->var2str($this->codruta)
                    . ", codalmacen = ".$this->var2str($this->codalmacen)
                    . ", codcliente = ".$this->var2str($this->codcliente)
                    . ", coddir = ".$this->var2str($this->coddir)
                    . ", coddivisa = ".$this->var2str($this->coddivisa)
                    . ", codejercicio = ".$this->var2str($this->codejercicio)
                    . ", codigo = ".$this->var2str($this->codigo)
                    . ", codpago = ".$this->var2str($this->codpago)
                    . ", codpais = ".$this->var2str($this->codpais)
                    . ", codpostal = ".$this->var2str($this->codpostal)
                    . ", codserie = ".$this->var2str($this->codserie)
                    . ", direccion = ".$this->var2str($this->direccion)
                    . ", editable = ".$this->var2str($this->editable)
                    . ", fecha = ".$this->var2str($this->fecha)
                    . ", hora = ".$this->var2str($this->hora)
                    . ", idalbaran = ".$this->var2str($this->idalbaran)
                    . ", irpf = ".$this->var2str($this->irpf)
                    . ", neto = ".$this->var2str($this->neto)
                    . ", nombrecliente = ".$this->var2str($this->nombrecliente)
                    . ", numero = ".$this->var2str($this->numero)
                    . ", numero2 = ".$this->var2str($this->numero2)
                    . ", observaciones = ".$this->var2str($this->observaciones)
                    . ", status = ".$this->var2str($this->status)
                    . ", porcomision = ".$this->var2str($this->porcomision)
                    . ", provincia = ".$this->var2str($this->provincia)
                    . ", tasaconv = ".$this->var2str($this->tasaconv)
                    . ", total = ".$this->var2str($this->total)
                    . ", totaleuros = ".$this->var2str($this->totaleuros)
                    . ", totalirpf = ".$this->var2str($this->totalirpf)
                    . ", totaliva = ".$this->var2str($this->totaliva)
                    . ", totalrecargo = ".$this->var2str($this->totalrecargo)
                    . ", femail = ".$this->var2str($this->femail)
                    . ", fechasalida = ".$this->var2str($this->fechasalida)
                    . ", codtrans = ".$this->var2str($this->envio_codtrans)
                    . ", codigoenv = ".$this->var2str($this->envio_codigo)
                    . ", nombreenv = ".$this->var2str($this->envio_nombre)
                    . ", apellidosenv = ".$this->var2str($this->envio_apellidos)
                    . ", apartadoenv = ".$this->var2str($this->envio_apartado)
                    . ", direccionenv = ".$this->var2str($this->envio_direccion)
                    . ", codpostalenv = ".$this->var2str($this->envio_codpostal)
                    . ", ciudadenv = ".$this->var2str($this->envio_ciudad)
                    . ", provinciaenv = ".$this->var2str($this->envio_provincia)
                    . ", codpaisenv = ".$this->var2str($this->envio_codpais)
                    . ", numdocs = ".$this->var2str($this->numdocs)
                    . ", idoriginal = ".$this->var2str($this->idoriginal)
                    . "  WHERE idpedido = ".$this->var2str($this->idpedido).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (apartado,cifnif,ciudad,codagente,codruta,codalmacen,
               codcliente,coddir,coddivisa,codejercicio,codigo,codpais,codpago,codpostal,codserie,
               direccion,editable,fecha,hora,idalbaran,irpf,neto,nombrecliente,numero,observaciones,
               status,porcomision,provincia,tasaconv,total,totaleuros,totalirpf,totaliva,totalrecargo,
               numero2,femail,fechasalida,codtrans,codigoenv,nombreenv,apellidosenv,apartadoenv,direccionenv,
               codpostalenv,ciudadenv,provinciaenv,codpaisenv,numdocs,idoriginal) VALUES ("
                    . $this->var2str($this->apartado).","
                    . $this->var2str($this->cifnif).","
                    . $this->var2str($this->ciudad).","
                    . $this->var2str($this->codagente).","
                    . $this->var2str($this->codruta).","
                    . $this->var2str($this->codalmacen).","
                    . $this->var2str($this->codcliente).","
                    . $this->var2str($this->coddir).","
                    . $this->var2str($this->coddivisa).","
                    . $this->var2str($this->codejercicio).","
                    . $this->var2str($this->codigo).","
                    . $this->var2str($this->codpais).","
                    . $this->var2str($this->codpago).","
                    . $this->var2str($this->codpostal).","
                    . $this->var2str($this->codserie).","
                    . $this->var2str($this->direccion).","
                    . $this->var2str($this->editable).","
                    . $this->var2str($this->fecha).","
                    . $this->var2str($this->hora).","
                    . $this->var2str($this->idalbaran).","
                    . $this->var2str($this->irpf).","
                    . $this->var2str($this->neto).","
                    . $this->var2str($this->nombrecliente).","
                    . $this->var2str($this->numero).","
                    . $this->var2str($this->observaciones).","
                    . $this->var2str($this->status).","
                    . $this->var2str($this->porcomision).","
                    . $this->var2str($this->provincia).","
                    . $this->var2str($this->tasaconv).","
                    . $this->var2str($this->total).","
                    . $this->var2str($this->totaleuros).","
                    . $this->var2str($this->totalirpf).","
                    . $this->var2str($this->totaliva).","
                    . $this->var2str($this->totalrecargo).","
                    . $this->var2str($this->numero2).","
                    . $this->var2str($this->femail).","
                    . $this->var2str($this->fechasalida).","
                    . $this->var2str($this->envio_codtrans).","
                    . $this->var2str($this->envio_codigo).","
                    . $this->var2str($this->envio_nombre).","
                    . $this->var2str($this->envio_apellidos).","
                    . $this->var2str($this->envio_apartado).","
                    . $this->var2str($this->envio_direccion).","
                    . $this->var2str($this->envio_codpostal).","
                    . $this->var2str($this->envio_ciudad).","
                    . $this->var2str($this->envio_provincia).","
                    . $this->var2str($this->envio_codpais).","
                    . $this->var2str($this->numdocs).","
                    . $this->var2str($this->idoriginal).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idpedido = $this->db->lastval();
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
