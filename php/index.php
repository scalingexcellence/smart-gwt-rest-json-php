<?php
/**
 * SGRJP: Smart-Gwt-Rest-Json-Php library
 *
 * Copyright 2011, Dimitrios Kouzis-Loukas, Scaling Excellence,
 * and individual contributors as indicated by the @authors tag.
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
require_once(dirname(__FILE__).'/lib/mysql.example.php');

$ob = new sgrjp();

try {
    //Retrieve the request object
    $req = $ob->decodePostAndGet();

    //Open a connection
    if (!mysql_connect('localhost', 'root', '')) {
        throw new Exception("Can't open MySQL connection. Wrong username/password?");
    }

    try {
        //Configure and select db
        mysql_query("SET CHARACTER SET utf8");
        mysql_select_db("sgrjp");

        //Convert the request to an SQL query using our example MySQL parser and run
        $pks = array("ItemsDs"=>array("SKU"), "supplyCategoryDS"=>array("categoryName"));
        list($ds, $total) = sqlparser::run($req, $req->ds, $pks[$req->ds]);
        
        mysql_close();

        //Return the results as a properly formatted JSON object
        $ob->returnResult($ds, $req->startRow, $total);
    }
    catch (Exception $e) {
        mysql_close();
        throw $e;
    }
}
catch (Exception $e) {
    $ob->returnError($e->getMessage());
}
