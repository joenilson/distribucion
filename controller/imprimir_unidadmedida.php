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
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'plugins/facturacion_base/extras/fs_pdf.php';
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
require_model('articulo_proveedor.php');
require_model('articulo_unidadmedida.php');
require_model('unidadmedida.php');
require_model('cliente.php');
require_model('impuesto.php');
require_model('pedido_cliente.php');
require_model('pedido_proveedor.php');
require_model('presupuesto_cliente.php');
require_model('proveedor.php');
/**
 * Description of imprimir_unidadmedida
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class imprimir_unidadmedida extends fs_controller {

    public function __construct() {
        parent::__construct(__CLASS__, 'imprimir', 'ventas', FALSE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->shared_extensions();
    }

    public function shared_extensions() {
        $extensiones = array(
            array(
                'name' => 'imprimir_pedido_um_proveedor',
                'page_from' => __CLASS__,
                'page_to' => 'compras_pedido',
                'type' => 'pdf',
                'text' => ucfirst(FS_PEDIDO) . ' con UM',
                'params' => '&pedido_um=TRUE'
            ),
            array(
                'name' => 'email_pedido_um_proveedor',
                'page_from' => __CLASS__,
                'page_to' => 'compras_pedido',
                'type' => 'email',
                'text' => ucfirst(FS_PEDIDO) . '  con UM',
                'params' => '&pedido_um=TRUE'
            ),
        );
        foreach ($extensiones as $ext) {
            $fsext = new fs_extension($ext);
            if (!$fsext->save()) {
                $this->new_error_msg('Error al guardar la extensión ' . $ext['name']);
            }
        }
    }

}
