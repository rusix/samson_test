<?php

require_once ('config.php');
require_once ('XMLdictionary.php');

/**
 * IMPORT XML TO DB
 * 
 * @param string $a XML file path 
 * 
 * @var bool $bReWrite Rewrite name for product
 */
function importXml($a)
{
    $bReWrite = true;
    
    global $hDB;
    $sFilePath = $a;
    $aLocal = array();

    if (!file_exists($sFilePath)) {
        throw new Exception("importXml(): File({$sFilePath}) not found;");
    }
    $sContent = file_get_contents($sFilePath);
    
    // Decode to UTF-8
    if (preg_match('/<\?xml .* encoding=".{2,10}1251"/i', $sContent)) {
        $sContent = iconv("CP1251", "UTF-8", $sContent);
        $sContent = preg_replace('/(<\?xml .*?)encoding=".*?"/i', '$1encoding="utf-8"', $sContent);
        $sContent = fnTagTranslate($sContent, true);
    }

    // Fill local storage
    function fnSetLocalParam(&$hDB, $sTableName, $sColumnAsName):array
    {
        return $hDB->query(
            'SELECT ID, LOWER(' . $sColumnAsName . ') FROM ' . $sTableName
        )->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    $aLocal['prices'] = fnSetLocalParam($hDB, 'A_PRICE_TYPES', 'NAME');
    $aLocal['props'] = fnSetLocalParam($hDB, 'A_PROPERTY_TYPES', 'NAME');
    $aLocal['units'] = fnSetLocalParam($hDB, 'A_PROPERTY_UNITS_TYPES', 'NAME');
    $aLocal['categories'] = fnSetLocalParam($hDB, 'A_CATEGORY', 'NAME');

    // Get/Set TYPE of param
    function fnParamSaveType(&$hDB, &$aLocalParam, $sParamName, $sTableName, $aAssocValues):int
    {
        // If not find in local array
        if (!in_array(mb_strtolower($sParamName) , $aLocalParam)) {

            // Build string like: (NAME) VALUES (:name) for SQL
            $sSQLNames = implode(',', array_keys($aAssocValues));
            $aSQLValues = preg_filter('/^/', ':', array_keys($aAssocValues)); // array
            $sSQLValues = implode(',', $aSQLValues); // string
            $sSQLNamesValues = "({$sSQLNames}) VALUES ({$sSQLValues})";
            // Build full SQL string
            $sth = $hDB->prepare('INSERT INTO ' . $sTableName . ' ' . $sSQLNamesValues . ';');
            // Bind params
            foreach ($aSQLValues as $key => $sSQLVariable) {
                $sth->bindValue($sSQLVariable, array_values($aAssocValues) [$key]);
            }
            $sth->execute();
            $iParamID = $hDB->lastInsertId('ID');
            // Save local param
            $aLocalParam[$iParamID] = mb_strtolower($sParamName);
        }
        else
        {
            // Get local param
            $iParamID = array_search(mb_strtolower($sParamName), $aLocalParam);
        }
        return $iParamID;
    }

    // Get/Set VALUE of param
    function fnParamSaveValue(&$hDB, $sTableName, $sColumnTypeName, $iProductID, $iParamID, $sParamVal)
    {
        // Known: Exist value in DB
        $stmt = $hDB->prepare('SELECT COUNT(PRODUCT_ID) AS COUNT FROM ' . $sTableName . ' WHERE PRODUCT_ID = :product_id AND ' . $sColumnTypeName . ' = :param_type_id LIMIT 1');
        $stmt->bindValue(':product_id', $iProductID);
        $stmt->bindValue(':param_type_id', $iParamID);
        $stmt->execute();
        $bParamExists = (bool)$stmt->fetch(PDO::FETCH_ASSOC) ['COUNT'];
        if (!$bParamExists) {
            // INSERT
            $sth = $hDB->prepare('INSERT INTO ' . $sTableName . ' (PRODUCT_ID, ' . $sColumnTypeName . ', VALUE) VALUES (:product_id, :param_type_id, :value);');
            $sth->bindValue(':product_id', $iProductID);
            $sth->bindValue(':param_type_id', $iParamID);
            $sth->bindValue(':value', $sParamVal);
            $sth->execute();
        } else {
            // UPDATE
            $sth = $hDB->prepare('UPDATE ' . $sTableName . ' SET VALUE = :value WHERE PRODUCT_ID = :product_id AND ' . $sColumnTypeName . ' = :param_type_id;');
            $sth->bindValue(':product_id', $iProductID);
            $sth->bindValue(':param_type_id', $iParamID);
            $sth->bindValue(':value', $sParamVal);
            $sth->execute();
        }
    }
    // Get/Set CATEGORY for Product
    function fnSetProductCategory(&$hDB, $iProductID, $iCategoryID)
    {
        // Known: Exist value in DB
        $stmt = $hDB->prepare('SELECT COUNT(PRODUCT_ID) AS COUNT FROM A_PRODUCT_CATEGORY WHERE PRODUCT_ID = :product_id AND CATEGORY_ID = :category_id LIMIT 1');
        $stmt->bindValue(':product_id', $iProductID);
        $stmt->bindValue(':category_id', $iCategoryID);
        $stmt->execute();
        $bParamExists = (bool)$stmt->fetch(PDO::FETCH_ASSOC) ['COUNT'];
        if (!$bParamExists) {
            // INSERT
            $sth = $hDB->prepare('INSERT INTO A_PRODUCT_CATEGORY (PRODUCT_ID, CATEGORY_ID) VALUES (:product_id, :category_id);');
            $sth->bindValue(':product_id', $iProductID);
            $sth->bindValue(':category_id', $iCategoryID);
            $sth->execute();
        }
    }

    // Load XML DOM
    $oXML = new SimpleXMLElement($sContent);
    
    try {
        $hDB->beginTransaction();
        
        // Each xmlProduct
        foreach ($oXML->product as $xProduct) {
            $sProductSKU = (string)$xProduct->attributes() ["SKU"];
            $sProductName = $xProduct->attributes() ["name"];
            $iProductID = - 1; // Def
            $stmt = $hDB->prepare('SELECT ID FROM A_PRODUCT WHERE SKU = :sku  LIMIT 1');
            $stmt->bindValue(':sku', $sProductSKU);
            $stmt->execute();
            $iProductID = $stmt->fetch(PDO::FETCH_ASSOC) ['ID']?? - 1;

            if ($iProductID == - 1) {
                $sth = $hDB->prepare('INSERT INTO A_PRODUCT (SKU, NAME) VALUES (:sku, :name);');
                $sth->bindValue(':sku', $sProductSKU);
                $sth->bindValue(':name', $sProductName);
                $sth->execute();
                $iProductID = $hDB->lastInsertId();
            } elseif ($bReWrite) {
                $sth = $hDB->prepare('UPDATE A_PRODUCT SET NAME = :name WHERE WHERE SKU = :sku;');
                $sth->bindValue(':name', $sProductName);
                $sth->bindValue(':sku', $sProductSKU);
                $sth->execute();
            }

            // Product -> Prices
            foreach ($xProduct->price as $xPrice) {

                $sParamName = (string)$xPrice->attributes() ["type"];
                $sParamVal = (float)$xPrice[0];
                $iParamID = - 1; // Def
                // Get or set TYPE of param
                $iParamID = fnParamSaveType(
                    $hDB,
                    $aLocal['prices'],
                    $sParamName,
                    'A_PRICE_TYPES',
                    array('NAME' => $sParamName)
                );
                // Get or set VALUE of param
                fnParamSaveValue($hDB, 'A_PRICE', 'PRICE_TYPE_ID', $iProductID, $iParamID, $sParamVal);
            }
            
            // Product -> Properties
            foreach ($xProduct->propertyes->property as $xProp) {

                $sParamUnitsName = (string)$xProp->attributes() ["units"];
                $iParamUnitID = - 1;

                $sParamName = (string)$xProp->attributes() ["name"];
                $sParamVal = (string)$xProp[0];
                $iParamID = - 1; // Def
                // Get or set UNITS of param
                if ($sParamUnitsName != '') {
                    $iParamUnitID = fnParamSaveType($hDB, $aLocal['units'], $sParamUnitsName, 'A_PROPERTY_UNITS_TYPES', array('NAME' => $sParamUnitsName));
                }
                // Prepare units of param
                $aColumns = array(
                    'NAME' => $sParamName
                );
                if ($iParamUnitID != - 1) {
                    $aColumns['PROPERTY_UNIT_ID'] = $iParamUnitID;
                }
                // Get or set TYPE of param
                $iParamID = fnParamSaveType($hDB, $aLocal['props'], $sParamName, 'A_PROPERTY_TYPES', $aColumns);
                // Get or set VALUE of param
                fnParamSaveValue($hDB, 'A_PROPERTY', 'PROPERTY_TYPE_ID', $iProductID, $iParamID, $sParamVal);
            }
            
            // Product -> Categories
            foreach ($xProduct->sections->section as $xSection) {

                $sParamVal = (string)$xSection[0];
                $iParamID = - 1; // Def
                // Get or set TYPE of param
                $iParamID = fnParamSaveType($hDB, $aLocal['categories'], $sParamVal, 'A_CATEGORY', array(
                    'NAME' => $sParamVal
                ));
                // Get or set VALUE of param
                fnSetProductCategory($hDB, $iProductID, $iParamID);
            }
        }
        $hDB->commit();
        echo 'Import successful!';
    } catch (Exception $e) {
        $hDB->rollBack();
        echo $ex->getMessage();
    }
}






/**
 * EXPORT FROM DB TO XML
 * 
 * @param string $a Path to import XML file (print to screen if empty)  
 * @param int $b ID for parent section (select all if empty) 
 * 
 * @var int $bEncodeTo1251 Encode to CP1251 
 */
function exportXml($a = '', $b = null)
{
    $bEncodeTo1251 = true;
    
    global $hDB;
    $sFilePath = $a;
    $iSectionID = $b;
    
    $oXMLProducts = new SimpleXMLElement("<products></products>");
    $aSectionsIDs = array();
    $sSectionsSQLWhere = '';
    
    // Prepare sections
    $fnGetChilds = function($iSecionID) use (&$hDB, &$aSectionsIDs, &$fnGetChilds)
    {
        $aSectionsIDs[] = $iSecionID;
        
        $stmt = $hDB->prepare('SELECT ID FROM A_CATEGORY WHERE PID = :section_id;');
        $stmt->bindValue(':section_id', $iSecionID);
        $stmt->execute();
        $aResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($aResult)) {
            foreach ($aResult as $iChildID) {
                $fnGetChilds($iChildID['ID']);
            }
        }
        return  $aSectionsIDs;
    };    
    if (! is_null($iSectionID)){
        $sSectionsIDs = implode(',', $fnGetChilds($iSectionID)); 
        $sSectionsSQLWhere = 'WHERE C.ID IN ('.$sSectionsIDs.')';
    }
    $stmt = $hDB->prepare('SELECT DISTINCT P.ID, P.SKU, P.NAME FROM ((A_PRODUCT P JOIN A_PRODUCT_CATEGORY PC ON P.ID = PC.PRODUCT_ID) JOIN A_CATEGORY C ON PC.CATEGORY_ID = C.ID ) '.$sSectionsSQLWhere);
    $stmt->execute();
    // EACH PRODUCTS
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $aProduct) {
        
        $iProductID = $aProduct['ID']; 
        
        $oXMLProduct = $oXMLProducts->addChild('product');
        $oXMLProduct->addAttribute('SKU', $aProduct['SKU']);  
        $oXMLProduct->addAttribute('name', $aProduct['NAME']);
        
        // Product -> Prices
        $stmt = $hDB->prepare('SELECT P.VALUE, PT.NAME FROM (A_PRICE P JOIN A_PRICE_TYPES PT ON P.PRICE_TYPE_ID = PT.ID) WHERE P.PRODUCT_ID = :product_id;');
        $stmt->bindValue(':product_id', $iProductID);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $aPrice) {
            $oXMLProductPrice = $oXMLProduct->addChild('price', $aPrice['VALUE']);
            $oXMLProductPrice->addAttribute('type', $aPrice['NAME']); 
        }
        // Product -> Properties
        $stmt = $hDB->prepare('SELECT P.VALUE, PT.NAME, TPUT.NAME AS UNIT FROM ((A_PROPERTY P JOIN A_PROPERTY_TYPES PT ON P.PROPERTY_TYPE_ID = PT.ID) LEFT JOIN A_PROPERTY_UNITS_TYPES TPUT ON PT.PROPERTY_UNIT_ID = TPUT.ID) WHERE P.PRODUCT_ID = :product_id;');
        $stmt->bindValue(':product_id', $iProductID);
        $stmt->execute();
        $oXMLProductProps = null;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $aProp) {
            if (is_null($oXMLProductProps)) {
                $oXMLProductProps = $oXMLProduct->addChild('propertyes');
            }
            $oXMLProductProp = $oXMLProductProps->addChild('property', $aProp['VALUE']);
            $oXMLProductProp->addAttribute('name', $aProp['NAME']); 
            if ($aProp['UNIT'] != '') {
                $oXMLProductProp->addAttribute('units', $aProp['UNIT']); 
            }
            
        }
        // Product -> Sections
        $stmt = $hDB->prepare('SELECT C.NAME FROM (A_CATEGORY C JOIN A_PRODUCT_CATEGORY PC ON C.ID = PC.CATEGORY_ID) WHERE PC.PRODUCT_ID = :product_id;');
        $stmt->bindValue(':product_id', $iProductID);
        $stmt->execute();
        $oXMLSections = null;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $aSection) {
            if (is_null($oXMLSections)) {
                $oXMLSections = $oXMLProduct->addChild('sections');
            }
            $oXMLSection = $oXMLSections->addChild('section', $aSection['NAME']);
        }
        
    }
    // FORMATTING  
    $oDOM = dom_import_simplexml($oXMLProducts)->ownerDocument;
    $oDOM->formatOutput = true;
    $sContent = html_entity_decode($oDOM->saveXML(), ENT_NOQUOTES, 'UTF-8');
    // ENCODE
    if ($bEncodeTo1251) {
        $sContent = preg_replace('/<\?xml .*?>/', '<?xml version="1.0" encoding="windows-1251"?>', $sContent);
        $sContent = fnTagTranslate($sContent, false);
    }
    if ($sFilePath == ''){
        // PRINT XML
        header('Content-type: text/xml');
        echo $sContent;
    } else {
        // SAVE XML
        if ($bEncodeTo1251) {
            $sContent = iconv("UTF-8", "CP1251", $sContent);
        }
        file_put_contents($sFilePath, $sContent);
    }
    
}






/**
 * REVERS SECOND MATCH
 * 
 * @param string $a Source 
 * @param string $b Substring 
 * 
 * @var int $iMatchNo Number of match for revers
 *  
 * @return string
 */
function convertString(string $a, string $b):string
{
    $iMatchNo = 2; // ( > 0 )
    
    $sContent = $a;
    $sSubStr = preg_quote($b);
	$sContent = preg_replace_callback(
        "/({$sSubStr})/siU",
        function ($matches){
            static $trigger = 0;
            $sSubStr = $matches[1];
            // select end revers choose match (preg_split for UNICODE)
            if ($trigger == ($iMatchNo - 1)) {
                $sSubStr = implode(
                    array_reverse(
                        preg_split('//u',$sSubStr,-1,PREG_SPLIT_NO_EMPTY)
                    )
                );
            }
            $trigger++;
            return $sSubStr;
        },
        $sContent
    );
    return $sContent;
}







/**
 * SORT 2xArray BY SUBKEY
 * 
 * @param array $a 2xArray (Simple: [['a'=>2,'b'=>1],['a'=>1,'b'=>3]])
 * @param string $b Key for SubArray of param $a 
 *  
 * @return array
 */
function mySortForKey(array $a, string $b):array
{
    $arrA = $a;
    $sSortKey = $b;
    $arrTmpByKey = array();
    foreach ($arrA as $key => $val){
        if (! isset($val[$sSortKey])) {
            throw new Exception("mySortForKey(): Key({$sSortKey}) not found in element with index:[$key] "); 
        }
        $arrTmpByKey[] = $val[$sSortKey];
    }
    array_multisort($arrTmpByKey, $arrA); 
    return $arrA; 
}

