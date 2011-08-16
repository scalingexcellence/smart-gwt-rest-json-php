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

$ob = new sgrjp();

try {
    if (!preg_match("#.*/(mem|mysql|sqlite)#",$_SERVER['REQUEST_URI'] , $_matches)) {
        throw new Exception("Can't find db, please define one of: mem,mysql,sqlite");
    }
    $db = $_matches[1];

    $req = $ob->decode_post_and_get();

    if ($req->ds=="supplyCategoryDS") {
        $ds = "category";
    }
    else if ($req->ds=="ItemsDs") {
        $ds = "item";
    }
    else {
        throw new Exception("DataSource is not supported");
    }

    if ($db=="mem") {
        $category = array(
            array("categoryName" => "Office Paper Products", "parentID" => "root", "volume" => "1"),
            array("categoryName" => "Calculator Rolls", "parentID" => "Office Paper Products", "volume" => "3"),
            array("categoryName" => "Adding Machine/calculator Roll", "Office Paper Products" => "root", "volume" => "6"),
            array("categoryName" => "General Office Products", "parentID" => "root", "volume" => "3"),
            array("categoryName" => "Segmented products", "parentID" => "General Office Products", "volume" => "13"),
        );

        $item = array(
            array("SKU" => "58074602", "units" => "Ea", "category" => "Office Paper Products", "itemName"=>"Pens Stabiliner 808 Ballpoint Fine Black", "unitCost"=>"0.24", "description"=>"Schwan Stabilo 808 ballpoint pens are a"),
            array("SKU" => "58074604", "units" => "Ea", "category" => "Office Paper Products", "itemName"=>"Pens Stabiliner 808 Ballpoint Fine Blue", "unitCost"=>"0.24"),
            array("SKU" => "58074605", "units" => "Ea", "category" => "Office Paper Products", "itemName"=>"Pens Stabiliner 808 Ballpoint Fine Red", "unitCost"=>"0.24", "description"=>"Schwan Stabilo 808 ballpoint pens are a"),
            array("SKU" => "58074622", "units" => "Ea", "category" => "Calculator Rolls", "itemName"=>"Calculator Rolls Black", "unitCost"=>"0.34"),
            array("SKU" => "58074622", "units" => "Ea", "category" => "Calculator Rolls", "itemName"=>"Calculator Rolls Red", "unitCost"=>"0.14", "description"=>"Realy advanced product"),
            array("SKU" => "58032622", "units" => "Ea", "category" => "Adding Machine/calculator Roll", "itemName"=>"Adding Machine/calculator Roll Black", "unitCost"=>"0.34"),
            array("SKU" => "58042622", "units" => "Ea", "category" => "Adding Machine/calculator Roll", "itemName"=>"Adding Machine/calculator RollRed", "unitCost"=>"0.14", "description"=>"Realy advanced product"),
            array("SKU" => "58132622", "units" => "Ea", "category" => "General Office Products", "itemName"=>"General Office Products Black", "unitCost"=>"0.34"),
            array("SKU" => "58142622", "units" => "Ea", "category" => "General Office Products", "itemName"=>"General Office Products Red", "unitCost"=>"0.14"),
            array("SKU" => "58152622", "units" => "Ea", "category" => "General Office Products", "itemName"=>"General Office Products Blue", "unitCost"=>"0.10"),
            array("SKU" => "59132622", "units" => "Ea", "category" => "Segmented products", "itemName"=>"Segmented products Black", "unitCost"=>"1.43"),
        );

//        if ($ds=="item") {}
//        && isset($ob->constraints["category"])) {
//
//        }


        $ob->returnResult($$ds, (property_exists($req, "startRows") ? $req->startRows : 0), 100);
    }
}
catch (Exception $e) {
    $ob->returnError($e->getMessage());
}
