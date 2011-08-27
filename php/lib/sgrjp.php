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


class sgrjp_request {
    /**
     * @var int The beginning of the results to return
     */
    public $startRow = 0;

    /**
     * @var int The end of the results to return
     */
    public $endRow = -1;

    /**
     * @var string The type of the operation (fetch, update etc.)
     */
    public $operationType = "";

    /**
     * @var string The datasource
     */
    public $ds = "";

    /**
     * @var array The sort criteria (key=>value), key is the fieldName, value = ASC/DESC
     */
    public $sort = array();

    /**
     * @var array The oldvalues object, used for update requests
     */
    public $oldValues = array();

    /**
     * @var stdClass The query object with the constraints
     */
    public $query = null;

    /**
     * @var array Key-Value pairs directly on the request objects. Those are either constraints or values to add/update.
     */
    public $fields = array();

    /**
     * @var string The type of matching for the free fields
     */
    public $textMatchStyle = null;

    /**
     * Conversts key-value pairs to search criteria/constraings
     * @static
     * @param $kv The array of key-value pairs
     * @param $op string The operator for those fields
     * @return stdClass A query constraints object
     */
    public static function convertArrayToCriteria($kv, $op = "iEquals") {
        $ob = new stdClass();
        $ob->criteria = array();
        $ob->operator = "and";
        $ob->_constructor = "AdvancedCriteria";
        foreach ($kv as $k=>$v) {
            $tmp = new stdClass();
            $tmp->fieldName = $k;
            $tmp->operator = $op;
            $tmp->value = $v;
            $ob->criteria[] = $tmp;
        }
        return $ob;
    }

    /**
     * Merges existing criteria with an array of new criteria (on an and- manner)
     * @param array $qr The criteria to merge with
     */
    public function mergeCriteria($qr) {
        //Do we have to merge?
        if (!$qr || !property_exists($qr, "criteria") || count($qr->criteria)<=0 || !property_exists($qr, "operator") || $qr->operator != "and") {
            return;
        }

        //If there was an advanced 'and' type query, then we can merge.
        if ($this->query && property_exists($this->query, "operator") && $this->query->operator=="and") {
            $this->query->criteria = array_merge($this->query->criteria, $qr->criteria);
        }
        else {
            //if there existed a 'top-level' criteria then we append it
            if ($this->query && property_exists($this->query, "operator")) {
                $qr->criteria[] = $this->query;
            }
            $this->query = $qr;
        }
    }

    /**
     * Transforms operations to the primitives returning some of their properties
     * @static
     * @param $op The operation name
     * @return array Array containing the simplified operation, if it's case insensitive, if negation, or if it compares with field
     */
    public static function mapOp($op) {
        //Map case insensitive cases
        $case_insensitive = false;
        if (strpos($op, "i") === 0 && $op != "isNull" && $op != "inSet") {
            $op = strtolower(substr($op, 1, 1)) . substr($op, 2);
            $case_insensitive = true;
        }

        //Map not cases
        $set_not = false;
        $transforms = array("notEqual" => "equals", "notContains" => "contains", "notStartsWith" => "startsWith", "notEndsWith" => "endsWith", "notInSet" => "inSet", "notEqualField" => "equalsField", "notNull" => "isNull");
        if (isset($transforms[$op])) {
            $set_not = true;
            $op = $transforms[$op];
        }

        //Map field cases
        $set_field = false;
        $transforms = array("equalsField" => "equals", "greaterThanField" => "greaterThan", "lessThanField" => "lessThan", "greaterOrEqualField" => "greaterOrEqual", "lessOrEqualField" => "lessOrEqual", "containsField" => "contains", "startsWithField" => "startsWith", "endsWithField" => "endsWith");
        if (isset($transforms[$op])) {
            $set_field = true;
            $op = $transforms[$op];
        }
        return array($op, $case_insensitive, $set_not, $set_field);
    }

    /**
     * Validates part of a query object and updates its fields
     * @throws Exception Invalid query object
     * @param $ob The request query object
     * @return string The SQL query
     */
    private function mGetFields($ob) {
        $t = array();

        //Empty query
        if (!$ob || !property_exists($ob, "operator")) {
            return $t;
        }

        if (property_exists($ob, "criteria")) {
            foreach ($ob->criteria as $k) {
                $t = array_merge($t, $this->mGetFields($k));
            }
            return $t;
        }
        else {
            // Simple queries
            if (!property_exists($ob, "fieldName")) {
                throw new Exception("Unexpected query object type (missing fieldName)");
            }

            list($op, $case_insensitive, $set_not, $set_field) = sgrjp_request::mapOp($ob->operator);

            //Set the required fields
            if (!in_array($ob->fieldName,$t)) {$t[]=$ob->fieldName;}
            if ($set_field) {
                if (!property_exists($ob, "value")) {
                    throw new Exception("Unexpected query object type (missing value)");
                }
                if (!in_array($ob->value,$t)) {$t[]=$ob->value;}
            }
            return $t;
        }
    }

    /**
     * Validates and returns the fields of the request object
     * @throws Exception Invalid request type or other problem
     * @return Array The array of fields used from this query
     */
    public function getFields() {
        return $this->mGetFields($this->query);
    }
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
    public function urlEncodingToArray($var, $vars = array())
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
    public function decodePostAndGet()
    {
        $v = array();
        if (preg_match("/\?(.*)/", $_SERVER['REQUEST_URI'], $_matches)) {
            $v = $this->urlEncodingToArray($_matches[1], $v);
        }
        $v = $this->urlEncodingToArray(file_get_contents("php://input"), $v);
        return $this->arrayToRequestObject($v);
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
     * @return array The decoded object and the array of fields for this object
     */
    public function arrayToRequestObject($ar)
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
        $ob = new sgrjp_request();

        //Detect operation type
        $xf = $this->metaDataPrefix."operationType";
        if (isset($ar[$xf])) {
            $ob->operationType = $ar[$xf];
            unset($ar[$xf]);
        }
        else {
            throw new Exception("Invalid operation. operationType is missing");
        }

        //Detect data source
        $xf = $this->metaDataPrefix."dataSource";
        if (isset($ar[$xf])) {
            $ob->ds = $ar[$xf];
            unset($ar[$xf]);
        }
        else {
            throw new Exception("Invalid operation. DataSource is missing");
        }

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

        //Detect sorting
        $xf = $this->metaDataPrefix."sortBy";
        if (isset($ar[$xf])) {
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

        //Detect the oldValues and store as an array with key-value pairs
        $xf = $this->metaDataPrefix."oldValues";
        if (isset($ar[$xf])) {
            $tmp = (array)json_decode($ar[$xf]);
            unset($tmp["__gwt_ObjectId"]);
            unset($tmp["\$29a"]);
            unset($tmp["expanded"]);
            unset($tmp["hasExpansionComponent"]);
            foreach ($tmp as $k=>$v) {
                if (strpos($k, $this->metaDataPrefix)!==0 && !preg_match("/^isc_/", $k)) {
                    $ob->oldValues[$k] = $v;
                }
            }
            unset($ar[$xf]);
        }

        //Discard component id, match style, selection_2
        foreach (array("componentId"=>true, "selection_2"=>true, "\$29a"=> false, "expanded"=> false, "hasExpansionComponent"=> false) as $k=>$v) {
            $xf = $v ? $this->metaDataPrefix.$k : $k;
            unset($ar[$xf]);
        }

        //Parse query
        if (isset($ar["criteria"])) {
            $ob->query = new stdClass();
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

        // Detect the textMatchStyle
        $xf = $this->metaDataPrefix."textMatchStyle";
        if (isset($ar[$xf])) {
            $ob->textMatchStyle = $ar[$xf];
            unset($ar[$xf]);
        }

        //All the non-meta characters become criteria
        foreach ($ar as $k=>$v) {
            if (strpos($k, $this->metaDataPrefix)!==0) {
                $ob->fields[$k] = $v;
            }
        }
        $ar = array_diff_key($ar, $ob->fields);

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
     * @static
     * @param $kv The key-value pairs of constraints
     * @return sgrjp_request The fetch request object with those constraints
     */
    public static function keyValueToFetchRequest($kv) {
        $ob = new sgrjp_request();
        $ob->operationType = "fetch";
        $ob->textMatchStyle = "exact";
        $ob->fields = $kv;
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
     * @param array $ob The array with the data
     * @param int $start Starting row
     * @param int $total Total number of entries
     */
    public function returnResult($ob, $start, $total) {
        $o = new stdClass();
        $o->response->status = 0;
        $o->response->startRow = (int)($start);
        $o->response->endRow = (int)($start + count($ob));
        $o->response->totalRows = (int)($total);
        $o->response->data = $ob;
        $this->returnObject($o);
    }
}
