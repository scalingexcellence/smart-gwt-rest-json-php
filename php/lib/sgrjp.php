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

class srjp
{
    /**
     * @var array Array fields to be json decoded. Publicly available.
     */
    public $json_fields = array("criteria");

    /**
     * @var array Array fields to be turned to arrays. Publicly available.
     */
    public $array_fields = array("criteria", "_sortBy");

    /**
     * @var string The prefix for meta-data. Override to ignore what comes from client.
     */
    private $metaDataPrefix = "_";
    private $metaDataPrefixForce = false;

    /**
     * Creating new srjp instances
     */
    public function __construct()
    {

    }

    /**
     * @param $var The url encoded string of variables to be extracted
     * @param array $vars Original array of variables. If set, the new variables are going to be appended to this string
     * @return array Returns the array of values in $var
     */
    public function url_encoding_to_array($var, $vars = array())
    {
        $pairs = explode("&", $var);
        foreach ($pairs as $pair) {
            $nv = explode("=", $pair);
            if (count($nv) > 1) {
                //Decode params
                $name = urldecode($nv[0]);
                $value = urldecode($nv[1]);

                //Decode json fields
                if (in_array($name, $this->json_fields)) {
                    $value = (array)json_decode($value);
                }

                //Append to array or set value
                if (in_array($name, $this->array_fields)) {
                    if (!isset($vars[$name])) {
                        $vars[$name] = array($value);
                    }
                    else {
                        $vars[$name][] = $value;
                    }
                }
                else {
                    $vars[$name] = $value;
                }
            }
        }
        return $vars;
    }


    /**
     * Processes post and and get variables to an array
     * @return array The values contained in that array
     */
    public function decode_post_and_get()
    {
        $v = array();
        if (preg_match("/\?(.*)/", $_SERVER['REQUEST_URI'], $_matches)) {
            $v = $this->url_encoding_to_array($_matches[1], $v);
        }
        $v = $this->url_encoding_to_array(file_get_contents("php://input"), $v);
        return $v;
    }

    /**
     * @return string Returns the meta-data prefix
     */
    public function getMetaDataPrefix()
    {
        return $this->metaDataPrefix;
    }

    /**
     * @param $mdp The prefix. If set, the parser ignores the value set from the client
     */
    public function setMetaDataPrefix($mdp)
    {
        $this->metaDataPrefix = $mdp;
        $this->metaDataPrefixForce = true;
    }

    public function array_to_request_object($ar)
    {
        //Pre-process very meta-data
        foreach ($ar as $k => $v) {
            if (preg_match("/^isc_/", $k)) {
                if ($k == "isc_dataFormat" && $v != "json") {
                    throw new Exception("Only json format is supported.");
                }
                if ($k == "isc_metaDataPrefix" && !$this->metaDataPrefixForce) {
                    $this->metaDataPrefix = $v;
                }
                unset($ar[$k]);
            }
            else if ($k == "__gwt_ObjectId") {
                unset($ar[$k]);
            }
        }

        //Setting up query object
        $ob = new stdClass();
        $ob->startRow = 0;
        $ob->endRow = -1;

        //Detect startRow
        $xf = $this->metaDataPrefix."startRow";
        if (isset($ar[$xf])) {
            $ob->startRow = $ar[$xf];
            unset($ar[$xf]);
        }

        //Detect endRow
        $xf = $this->metaDataPrefix."endRow";
        if (isset($ar[$xf])) {
            $ob->endRow = $ar[$xf];
            unset($ar[$xf]);
        }

        //Discard component id
        $xf = $this->metaDataPrefix."componentId";
        if (isset($ar[$xf])) {
            unset($ar[$xf]);
        }

        //Detect operation type
        $xf = $this->metaDataPrefix."operationType";
        if (isset($ar[$xf])) {
            $ob->operationType = $ar[$xf];
            unset($ar[$xf]);
        }

        //Detect sorting
        $xf = $this->metaDataPrefix."sortBy";
        if (isset($ar[$xf])) {
            $ob->sort = array();
            foreach ($ar[$xf] as $k) {
                $v = "ASC";
                if (strpos($k, "-")===0) {
                    $k = substr($k,1);
                    $v = "DESC";
                }
                if (isset($ob->sort[$k])) {
                    throw new Exception("Setting sort order twice!");
                }
                $ob->sort[$k] = $v;
            }
            unset($ar[$xf]);
        }

        //Detect data source
        $xf = $this->metaDataPrefix."dataSource";
        if (isset($ar[$xf])) {
            $ob->ds = $ar[$xf];
            unset($ar[$xf]);
        }
        
        return $ar;
    }
}
