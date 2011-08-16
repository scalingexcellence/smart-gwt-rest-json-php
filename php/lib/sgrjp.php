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

class default_target {
    public $list = array();
    public function escape($q) { return $q; }
    public function pushField($q) { if (!in_array($q,$this->list)) {$this->list[]=$q;} }
}

class sgrjp
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
                    $value = json_decode($value);
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
     * @return stdClass The values contained in that array
     */
    public function decode_post_and_get()
    {
        $v = array();
        if (preg_match("/\?(.*)/", $_SERVER['REQUEST_URI'], $_matches)) {
            $v = $this->url_encoding_to_array($_matches[1], $v);
        }
        $v = $this->url_encoding_to_array(file_get_contents("php://input"), $v);
        return $this->array_to_request_object($v);
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

    /**
     * @throws Exception Throws an exception if the format is not supported etc.
     * @param $ar The array with the parameters
     * @return stdClass The decoded object
     */
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

        //Discard text match style
        $xf = $this->metaDataPrefix."textMatchStyle";
        if (isset($ar[$xf])) {
            unset($ar[$xf]);
        }

        //Parse query
        $ob->query = new stdClass();
        if (isset($ar["criteria"])) {
            if (!isset($ar["operator"])) {
                throw new Exception("Found criteria but not operator!");
            }
            if (!isset($ar[$this->metaDataPrefix."constructor"])) {
                throw new Exception("Found criteria but not constructor!");
            }
            $ob->query->criteria = $ar["criteria"];
            $ob->query->operator = $ar["operator"];
            $ob->query->_constructor = $ar[$this->metaDataPrefix."constructor"];
            unset($ar["criteria"]);
            unset($ar["operator"]);
            unset($ar[$this->metaDataPrefix."constructor"]);
        }

        //All the non-meta characters become criteria
        $to_remove = array();
        $extra_criteria  = array();
        foreach ($ar as $k=>$v) {
            if (strpos($k, $this->metaDataPrefix)!==0) {
                $tmp = new stdClass();
                $tmp->fieldName = $k;
                $tmp->operator = "iEquals";
                $tmp->value = $v;
                $extra_criteria[] = $tmp;
                $to_remove[$k] = 1;
            }
        }
        $ar = array_diff_key($ar, $to_remove);

        //We have to merge...
        if (count($extra_criteria)>0) {
            //If there was an advanced 'and' type query, then we can merge.
            if (property_exists($ob->query, "operator") && $ob->query->operator=="and") {
                $ob->query->criteria = array_merge($ob->query->criteria, $extra_criteria);
            }
            else {
                //else, we have to create a new 'top-level' criteria
                $tmp = new stdClass();
                $tmp->criteria = $extra_criteria;
                $tmp->operator = "and";
                $tmp->_constructor = "AdvancedCriteria";
                //if there existed a 'top-level' criteria then we append it
                if (property_exists($ob->query, "operator")) {
                    $tmp->criteria[] = $ob->query;
                }
                $ob->query = $tmp;
            }
        }

        //Do some sanity test and validation to the request object
        if (!property_exists($ob, "ds")) {
            throw new Exception("Invalid operation. DataSource is missing");
        }

        if (!property_exists($ob, "operationType")) {
            throw new Exception("Invalid operation. operationType is missing");
        }

        //Should have parsed all the elements of $ar by now.
        if (count($ar)!=0) {
            $t = "";
            foreach ($ar as $k=>$v) {
                $t .= "'" . $k . "' ";
            }
            throw new Exception("Unknown request field(s): " . $t);
        }
        
        return $ob;
    }

    /**
     * @param $ob Returns a JSON object and terminates
     */
    private function returnObject($ob) {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        if (isset($_GET['callback'])) {
            echo $_GET['callback'] . '(' . json_encode($ob) . ')';
        }
        else {
            echo json_encode($ob);
        }
        exit;
    }

    /**
     * Returns general error
     * @param $err The error to be returned
     */
    public function returnError($err) {
        $o = new stdClass();
        $o->response = new stdClass();
        $o->response->status = -1;
        $o->response->data = $err;
        $this->returnObject($o);
    }

    /**
     * Returns field validation errors
     * @param $errs The errors to be returned as an associative array with keys the field names and values the array with errors.
     */
    public function returnValidationErrors($errs) {
        $o = new stdClass();
        $o->response = new stdClass();
        $o->response->status = -4;
        $o->response->errors = new stdClass();

        foreach ($errs as $field=>$err){
            $o->response->errors->$field = array();
            foreach ($err as $e){
                $xe = new stdClass();
                $xe->errorMessage = $e;
                $o->response->errors->{$field}[] = $xe;
            }
        }
        $this->returnObject($o);
    }

    /**
     * Returns a ResultSet
     * @param $arr The array with the data
     * @param $start Starting row
     * @param $total Total number of entries
     */
    public function returnResult($arr,$start,$total) {
        $o = new stdClass();
        $o->response->status = 0;
        $o->response->startRows = $start;
        $o->response->endRow = $start + count($arr);
        $o->response->totalRows = $total;
        $o->response->data = $arr;
        $this->returnObject($o);
    }

    /**
     * Parses a chunk of query (where) parameter
     * @throws Exception Invalid query object
     * @param $ob The request query object
     * @param $p An object exposing the parameters interface with two methods: 'escape' and 'pushField'
     * @return string The SQL query
     */
    private function parseWhere($ob, &$p) {
        //Empty query
        if (!property_exists($ob, "operator")) {
            return "";
        }

        if (property_exists($ob, "criteria")) {
            // The join engine for composite queries
            $starts_with = "((";
            $ends_with = "))";
            $join_with = ") OR (";
            switch ($ob->operator) {
                case "and":
                    $join_with = ") AND (";
                    break;
                case "or":
                    break;
                case "not":
                    $starts_with = "(NOT ((";
                    $ends_with = ")))";
                    break;
            }
            $q = $starts_with;
            $prefix = "";
            foreach ($ob->criteria as $k) {
                $q.= $prefix . $this->parseWhere($k, $p);
                $prefix = $join_with;
            }
            return $q . $ends_with;
        }
        else {
            // Simple queries
            if (!property_exists($ob, "fieldName")) {
                throw new Exception("Unexpected query object type (missing value)");
            }

            //Map case insensitive cases
            $case_insensitive = false;
            if (strpos($ob->operator, "i")===0 && $ob->operator!="isNull" && $ob->operator!="inSet") {
                $ob->operator = strtolower(substr($ob->operator, 1, 1)) . substr($ob->operator, 2);
                $case_insensitive = true;
            }

            //Map not cases
            $set_not = false;
            $transforms = array("notEqual"=>"equals","notContains"=>"contains","notStartsWith"=>"startsWith","notEndsWith"=>"endsWith","notInSet"=>"inSet","notEqualField"=>"equalsField","notNull"=>"isNull");
            if (isset($transforms[$ob->operator])) {
                $set_not = true;
                $ob->operator = $transforms[$ob->operator];
            }

            //Map field cases
            $set_field = false;
            $transforms = array("equalsField"=>"equals","greaterThanField"=>"greaterThan","lessThanField"=>"lessThan","greaterOrEqualField"=>"greaterOrEqual","lessOrEqualField"=>"lessOrEqual","containsField"=>"contains","startsWithField"=>"startsWith","endsWithField"=>"endsWith");
            if (isset($transforms[$ob->operator])) {
                $set_field = true;
                $ob->operator = $transforms[$ob->operator];
            }

            //Check parameters
            if ($ob->operator == "between" || $ob->operator == "betweenInclusive") {
                //Require 'start' and 'end'
                if (!property_exists($ob, "start") || !property_exists($ob, "end")) {
                    throw new Exception("Unexpected query object type (missing start,end)");
                }
                if (!is_numeric($ob->start) || !is_numeric($ob->end)) {
                    throw new Exception("Start or end is not numeric");
                }
            }
            else if ($ob->operator == "isNull") {
                //No parameters required in this case
            }
            else {
                if (!property_exists($ob, "value")) {
                    //Every other case requires the 'value' parameter
                    throw new Exception("Unexpected query object type (missing value)");
                }
                if (!$set_field && in_array($ob->operator, array("greaterThan","lessThan","greaterOrEqual","lessOrEqual"))) {
                    if (!is_numeric($ob->value)) {
                        throw new Exception("Value '" . $ob->value . "' is not numeric");
                    }
                }
            }

            //Set the required fields
            $p->pushField($ob->fieldName);
            if ($set_field) {
                $p->pushField($ob->value);
            }

            //Create operator's case
            switch ($ob->operator) {
                case "contains":
                case "equals":
                case "greaterThan":
                case "lessThan":
                case "greaterOrEqual":
                case "lessOrEqual":
                case "startsWith":
                case "endsWith":
                    $startm = "";
                    $endm = "";
                    switch ($ob->operator) {
                        case "contains":
                            $startm = "%";
                            $endm = "%";
                            break;
                        case "startsWith":
                            $endm = "%";
                            break;
                        case "endsWith":
                            $startm = "%";
                            break;
                    }

                    $opmap = array("contains"=>"LIKE","equals"=>"=","greaterThan"=>">","lessThan"=>"<","greaterOrEqual"=>">=","lessOrEqual"=>"<=","startsWith"=>"LIKE","endsWith"=>"LIKE");
                    $op = $opmap[$ob->operator];

                    //MySQL comparison is always case insensitive (!)
                    if ($set_field)
                        //Case field
                        $qr = "`".$ob->fieldName."` ".$op." `".$ob->value."`";
                    elseif (!is_numeric($ob->value) || $case_insensitive || $startm!="" || $endm!="")
                        //Case string
                        $qr = "`".$ob->fieldName."` ".$op." '".$startm.$p->escape($ob->value).$endm."'";
                    else
                        //Case number
                        $qr = "`".$ob->fieldName."` ".$op." ".$ob->value;

                    return ($set_not?"NOT (":"") . $qr . ($set_not?")":"");
                    
                case "isNull":
                    return ($set_not?"NOT (":"") . "ISNULL(`".$ob->fieldName."`)" . ($set_not?")":"");

                case "between":
                case "betweenInclusive":
                    $lt = $ob->operator=="betweenInclusive" ? "<=" : "<";
                    $gt = $ob->operator=="betweenInclusive" ? ">=" : ">";
                    return ($set_not?"NOT (":"") . "((`".$ob->fieldName."`".$gt." ".$ob->start.") AND (`".$ob->fieldName."`".$lt." ".$ob->end."))" . ($set_not?")":"");

                case "regexp":
                    throw new Exception("regexp not supported.");
                case "inSet":
                    throw new Exception("inSet not supported.");
                default:
                    throw new Exception("Unknown operator " . $ob->operator);
            }
        }
    }

    /**
     * Transforms the request object to an SQL statement
     * @throws Exception Invalid request type or other problem
     * @param $ob The erquest object
     * @param $p An object exposing the parameters interface with two methods: 'escape' and 'pushField'
     * @return array The SQL statement
     */
    public function toSql($ob, &$p) {
        if ($ob->operationType != "fetch") {
            throw new Exception("Only fetch is supported right now");
        }

        //Parse the where parameters
        $where = $this->parseWhere($ob->query, $p);
        if (strlen($where) > 0) {
            $where = "WHERE " . $where;
        }

        //Creating ORDER BY statements
        $order = "";
        if (property_exists($ob, "sort")) {
            $order = "ORDER BY ";
            $prefix = "";
            foreach ($ob->sort as $k=>$v) {
                $order .= $prefix . "`" . $k . "` " . $v;
                $prefix = ",";
            }
        }

        //Creating LIMIT statements
        $limit = "";
        if ($ob->startRow != 0 || $ob->endRow!=-1) {
            if (!is_numeric($ob->startRow) || !is_numeric($ob->endRow)) {
                throw new Exception("Not numeric startRow/endRow");
            }
            if ($ob->startRow > $ob->endRow) {
                throw new Exception("endRow is less than startRow");
            }
            $limit = "LIMIT " . $ob->startRow;
            if ($ob->endRow!=-1) {
                $limit .= ", " .  ($ob->endRow - $ob->startRow);
            }
        }

        return array($where, $order, $limit);
    }
}
