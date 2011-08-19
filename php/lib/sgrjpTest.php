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

require_once(dirname(__FILE__).'/sgrjp.php');

/**
 * Test class for sgrjp.
 */
class sgrjpTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var sgrjp
     */
    protected $t;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->t = new sgrjp();
    }

    /**
     * Tests a few URLs
     */
    public function testUrlEncodingToArray() {



        /*
        $urls = array(
            "_constructor=AdvancedCriteria&operator=and&criteria=%7B%22fieldName%22%3A%22categoryName%22%2C%22operator%22%3A%22iStartsWith%22%2C%22value%22%3A%22abc%22%7D&criteria=%7B%22fieldName%22%3A%22volume%22%2C%22operator%22%3A%22lessThan%22%2C%22value%22%3A12%7D&__gwt_ObjectId=11991&_operationType=fetch&_startRow=15&_endRow=28&_sortBy=categoryName&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json",
            "_constructor=AdvancedCriteria&operator=and&criteria=%7B%22fieldName%22%3A%22categoryName%22%2C%22operator%22%3A%22iStartsWith%22%2C%22value%22%3A%22abc%22%7D&criteria=%7B%22fieldName%22%3A%22volume%22%2C%22operator%22%3A%22lessThan%22%2C%22value%22%3A12%7D&__gwt_ObjectId=11991&_operationType=fetch&_startRow=15&_endRow=28&_sortBy=-volume&_sortBy=categoryName&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json",
            "category=Adding%20Machine%2Fcalculator%20Roll&_operationType=fetch&_startRow=0&_endRow=75&_textMatchStyle=exact&_componentId=isc_ListGrid_1&_dataSource=ItemsDs&isc_metaDataPrefix=_&isc_dataFormat=json&__gwt_ObjectId=40171",
            "_constructor=AdvancedCriteria&operator=not&criteria=%7B%22fieldName%22%3A%22categoryName%22%2C%22operator%22%3A%22iEquals%22%2C%22value%22%3A%22wey%22%7D&__gwt_ObjectId=676&_operationType=fetch&_startRow=26&_endRow=30&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json",
            "_constructor=AdvancedCriteria&operator=or&criteria=%7B%22fieldName%22%3A%22categoryName%22%2C%22operator%22%3A%22iEquals%22%2C%22value%22%3A%22wey%22%2C%22_constructor%22%3A%22AdvancedCriteria%22%7D&__gwt_ObjectId=952&_operationType=fetch&_startRow=0&_endRow=28&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json",
            "_constructor=AdvancedCriteria&operator=and&criteria=%7B%22fieldName%22%3A%22categoryName%22%2C%22operator%22%3A%22iStartsWith%22%2C%22value%22%3A%22C%22%7D&criteria=%7B%22_constructor%22%3A%22AdvancedCriteria%22%2C%22operator%22%3A%22or%22%2C%22criteria%22%3A%5B%7B%22fieldName%22%3A%22volume%22%2C%22operator%22%3A%22lessThan%22%2C%22value%22%3A%22584%22%7D%2C%7B%22fieldName%22%3A%22volume%22%2C%22operator%22%3A%22lessOrEqual%22%2C%22value%22%3A%2243%22%7D%2C%7B%22fieldName%22%3A%22volume%22%2C%22operator%22%3A%22greaterThanField%22%2C%22value%22%3A%22volume%22%7D%5D%7D&__gwt_ObjectId=395&_operationType=fetch&_startRow=26&_endRow=30&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json",
            "operator=and&_constructor=AdvancedCriteria&criteria=%7B%22fieldName%22%3A%22volume%22%2C%22operator%22%3A%22lessThan%22%2C%22value%22%3A%223%22%2C%22_constructor%22%3A%22AdvancedCriteria%22%7D&_operationType=fetch&_startRow=0&_endRow=28&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json&__gwt_ObjectId=395",
            "operator=and&_constructor=AdvancedCriteria&criteria=%7B%22fieldName%22%3A%22volume%22%2C%22operator%22%3A%22isNull%22%2C%22_constructor%22%3A%22AdvancedCriteria%22%7D&_operationType=fetch&_startRow=26&_endRow=30&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json&__gwt_ObjectId=908",
            "operator=and&_constructor=AdvancedCriteria&criteria=%7B%22fieldName%22%3A%22categoryName%22%2C%22operator%22%3A%22iContains%22%2C%22value%22%3A%22cde%22%7D&criteria=%7B%22fieldName%22%3A%22volume%22%2C%22operator%22%3A%22equals%22%2C%22value%22%3A%22absd%22%7D&_operationType=fetch&_startRow=26&_endRow=30&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json&__gwt_ObjectId=1566",
            "categoryName=abd&volume=32&_operationType=fetch&_startRow=26&_endRow=30&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json&__gwt_ObjectId=758",
            "_constructor=AdvancedCriteria&operator=not&criteria=%7B%22fieldName%22%3A%22categoryName%22%2C%22operator%22%3A%22iEndsWith%22%2C%22value%22%3A%22C%22%7D&criteria=%7B%22fieldName%22%3A%22categoryName%22%2C%22operator%22%3A%22iContains%22%2C%22value%22%3A%22g%22%7D&__gwt_ObjectId=1818&_operationType=fetch&_startRow=26&_endRow=30&_textMatchStyle=substring&_componentId=isc_Gwt_1_0&_dataSource=supplyCategoryDS&isc_metaDataPrefix=_&isc_dataFormat=json",
        );

        foreach ($urls as $u) {
            $x = $this->t->arrayToRequestObject($this->t->urlEncodingToArray($u));
            echo "URL: ".$u."\nQUERY:\n";
            print_r($x);
        }
        */
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    
}
