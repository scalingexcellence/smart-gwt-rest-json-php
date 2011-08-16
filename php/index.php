<?php
/**
 * SGRJP: Smart-Gwt-Rest-Json-Php library
 *
 * Copyright 2011, Scaling Excellence, and individual contributors
 * as indicated by the @authors tag.
 *
 * This is free software; you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; either version 2.1 of
 * the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the Free
 * Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA
 * 02110-1301 USA, or see the FSF site: http://www.fsf.org.
 */


require_once(dirname(__FILE__).'/lib/sgrjp.php');

//$ob = new srjp();
//$ob->whitelistAll();
//$ob->dispatch();

$o->response->status = 0;
$o->response->startRows = 0;
$o->response->endRow = 3;
$o->response->totalRows = 4;
$o->response->data = array(
    array("hello" => "world 1"),
    array("hello" => "world 2"),
    array("hello" => "world 3"),
    array("hello" => "world 4"),
);
echo json_encode($o);