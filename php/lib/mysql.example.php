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

/**
 * sqlparser.
 */
class sqlparser
{
    /**
     * Parses a chunk of query (where) parameter
     * @static
     * @throws Exception Invalid query object
     * @param $ob The request query object
     * @param $depth int The depth of parsing (default 0)
     * @return string The SQL query
     */
    private static function parseWhere($ob, $depth = 0)
    {
        //Empty query
        if (!$ob || !property_exists($ob, "operator")) {
            return "1";
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
                $q .= $prefix . sqlparser::parseWhere($k, $depth + 1);
                $prefix = $join_with;
            }
            return $q . $ends_with;
        }
        else {
            // Simple queries
            if (!property_exists($ob, "fieldName")) {
                throw new Exception("Unexpected query object type (missing value)");
            }

            list($op, $case_insensitive, $set_not, $set_field) = sgrjp_request::mapOp($ob->operator);

            //Check parameters
            if ($op == "between" || $op == "betweenInclusive") {
                //Require 'start' and 'end'
                if (!property_exists($ob, "start") || !property_exists($ob, "end")) {
                    throw new Exception("Unexpected query object type (missing start,end)");
                }
                if (!is_numeric($ob->start) || !is_numeric($ob->end)) {
                    throw new Exception("Start or end is not numeric");
                }
            }
            else if ($op == "isNull") {
                //No parameters required in this case
            }
            else {
                if (!property_exists($ob, "value")) {
                    //Every other case requires the 'value' parameter
                    throw new Exception("Unexpected query object type (missing value)");
                }
                if (!$set_field && in_array($op, array("greaterThan", "lessThan", "greaterOrEqual", "lessOrEqual"))) {
                    if (!is_numeric($ob->value)) {
                        throw new Exception("Value '" . $ob->value . "' is not numeric");
                    }
                }
            }

            //Create operator's case
            switch ($op) {
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
                    switch ($op) {
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

                    $opmap = array("contains" => "LIKE", "equals" => "=", "greaterThan" => ">", "lessThan" => "<", "greaterOrEqual" => ">=", "lessOrEqual" => "<=", "startsWith" => "LIKE", "endsWith" => "LIKE");
                    $op = $opmap[$op];

                    //MySQL comparison is always case insensitive (!)
                    if ($set_field
                    )
                        //Case field
                        $qr = "`" . $ob->fieldName . "` " . $op . " `" . $ob->value . "`";
                    elseif (!is_numeric($ob->value) || $case_insensitive || $startm != "" || $endm != ""
                    )
                        //Case string
                        $qr = "`" . $ob->fieldName . "` " . $op . " '" . $startm . mysql_real_escape_string($ob->value) . $endm . "'";
                    else
                        //Case number
                        $qr = "`" . $ob->fieldName . "` " . $op . " " . $ob->value;

                    return ($set_not ? "NOT (" : "") . $qr . ($set_not ? ")" : "");

                case "isNull":
                    return ($set_not ? "NOT (" : "") . "ISNULL(`" . $ob->fieldName . "`)" . ($set_not ? ")" : "");

                case "between":
                case "betweenInclusive":
                    $lt = $op == "betweenInclusive" ? "<=" : "<";
                    $gt = $op == "betweenInclusive" ? ">=" : ">";
                    return ($set_not ? "NOT ("
                            : "") . "((`" . $ob->fieldName . "`" . $gt . " " . $ob->start . ") AND (`" . $ob->fieldName . "`" . $lt . " " . $ob->end . "))" . ($set_not
                            ? ")" : "");

                case "regexp":
                    throw new Exception("regexp not supported.");
                case "inSet":
                    throw new Exception("inSet not supported.");
                default:
                    throw new Exception("Unknown operator " . $op . "(" . $ob->operator . ")");
            }
        }
    }

    /**
     * Transforms the request object to an SQL statement and runs it
     * @static
     * @throws Exception Invalid request type or other problem
     * @param $ob sgrjp_request Array containing the request object ($req[0]) and the required fields ($req[1])
     * @param string $table The name of the table to query
     * @param array $pks the array with the primary keys for the update/delete/add operations
     * @param string $fields String with the fields to return (defaults to '*')
     * @return array The two SQL query strings. The first selects data and the second the count of all data that matches.
     */
    public static function run($ob, $table, $pks = array(), $fields = "*")
    {
        // Optionally test that fields in $ob->getFields() exist in the table and that the user has sufficient
        // permissions to edit these tables/fields.
        
        switch ($ob->operationType) {
            case "fetch":
                if ($ob->fields && count($ob->fields)) {
                    if (!$ob->textMatchStyle) {
                        throw new Exception("Invalid request (missing textMatchStyle)");
                    }
                    $mapTextMatch = array("substring"=>"iContains", "exact"=>"iEquals");
                    //Make the extra key-value pairs be criteria
                    $ob->mergeCriteria(sgrjp_request::convertArrayToCriteria($ob->fields, $mapTextMatch[$ob->textMatchStyle]));
                }

                //Extract the constraints
                $where = sqlparser::parseWhere($ob->query);

                //Extract the sort options
                $order = sqlparser::extractOrder($ob);

                //Extract the limit options
                $limit = sqlparser::extractLimit($ob);

                return array(
                    sqlparser::ask("SELECT " . $fields . " FROM `" . $table . "` WHERE " . $where . " " . $order . " " . $limit),
                    sqlparser::ask("SELECT COUNT(*) AS `cnt` FROM `" . $table . "` WHERE " . $where, "cnt")
                );
            
            case "remove":
                //Extract the constraints
                $where = sqlparser::parseWhere(sgrjp_request::convertArrayToCriteria($ob->fields));

                $sql = "DELETE FROM `" . $table . "` WHERE " . $where;
                if (!mysql_query($sql)) {
                    throw new Exception("Could not successfully run query ($sql) from DB: " . mysql_error());
                }

                //Return only the Primary Keys
                $just_pks = array_intersect_key($ob->fields, array_fill_keys($pks , ""));

                return array($just_pks, 1);

            case "update":
                //Keep just the primary keys
                $just_pks = array_intersect_key($ob->oldValues, array_fill_keys($pks , ""));

                //Extract the constraints
                $where = sqlparser::parseWhere(sgrjp_request::convertArrayToCriteria($just_pks));

                //create the SET's for the UPDATE query
                $sets = array();
                foreach ($ob->fields as $k => $v) {
                    if (isset($just_pks[$k])) {
                        if ($v!=$just_pks[$k]) {
                            throw new Exception("Can't modify primary key '".$k."'");
                        }
                    }
                    else {
                        $sets[] = "`" . $k . "`='" . mysql_real_escape_string($v) . "'";
                    }
                }

                //Run the query
                $sql = "UPDATE `" . $table . "` SET " . implode(",", $sets) . " WHERE " . $where;
                if (!mysql_query($sql)) {
                    throw new Exception("Could not successfully run query ($sql) from DB: " . mysql_error());
                }

                //Get the new object
                return array(sqlparser::ask("SELECT " . $fields . " FROM `" . $table . "` WHERE " . $where), 1);

            case "add":
                //Run the query
                $sql = "INSERT INTO `" . $table . "` (`" . implode("`,`", array_keys($ob->fields)) . "`) VALUES('" .
                       implode("','", array_map(" mysql_real_escape_string", $ob->fields)) . "')";
                    
                if (!mysql_query($sql)) {
                    throw new Exception("Could not successfully run query ($sql) from DB: " . mysql_error());
                }

                //Extract the Primary Key constraints for this object
                //$where = sqlparser::parseWhere(sgrjp_request::convertArrayToCriteria($ob->extractPkConstraints($table, $pks, mysql_insert_id())));

                $where = "`$pks[0]` = LAST_INSERT_ID()";

                //Get the new object
                return array(sqlparser::ask("SELECT " . $fields . " FROM `" . $table . "` WHERE " . $where), 1);

            default:
                throw new Exception("Operation '" . $ob->operationType . "' is not supported.");
        }
    }

    /**
     * Creates the ORDER BY query if the object has sort properties
     * @static
     * @param $ob The query object
     * @return string The ORDER BY query if the object has sort properties
     */
    private static function extractOrder($ob)
    {
        //Creating ORDER BY statements
        $order = "";
        if (property_exists($ob, "sort") && is_array($ob->sort) && count($ob->sort)>0) {
            $order = "ORDER BY ";
            $prefix = "";
            foreach ($ob->sort as $k => $v) {
                $order .= $prefix . "`" . $k . "` " . $v;
                $prefix = ",";
            }
        }
        return $order;
    }

    /**
     * Creates the limit part of an SQL query
     * @static
     * @throws Exception if invalid startRow/endRow
     * @param $ob The query object
     * @return string The limit part of an SQL query
     */
    private static function extractLimit($ob)
    {
        //Creating LIMIT statements
        $limit = "";
        if ($ob->startRow != 0 || $ob->endRow != -1) {
            if (!is_numeric($ob->startRow) || !is_numeric($ob->endRow)) {
                throw new Exception("Not numeric startRow/endRow");
            }
            if ($ob->startRow > $ob->endRow) {
                throw new Exception("endRow is less than startRow");
            }
            $limit = "LIMIT " . $ob->startRow;
            if ($ob->endRow != -1) {
                $limit .= ", " . ($ob->endRow - $ob->startRow);
            }
        }
        return $limit;
    }

    /**
     * Makes some SQL requests
     * @static
     * @throws Exception If the query is note executed successfully
     * @param $sql The SQL to be run
     * @param string $field (optional) The field to be returned
     * @return array The array with the results or a scalar with the result if "field" is set
     */
    public static function ask($sql, $field = null)
    {
        $ds = array();
        $result = mysql_query($sql);
        if (!$result) {
            throw new Exception("Could not successfully run query ($sql) from DB: " . mysql_error());
        }
        while (($data = mysql_fetch_object($result))) {
            $ds[] = $data;
        }
        mysql_free_result($result);
        if ($field && (count($ds) <= 0 || !property_exists($ds[0], $field))) {
            throw new Exception("Result set doesn't contain '" . $field . "'");
        }
        return $field ? $ds[0]->{$field} : $ds;
    }
}
