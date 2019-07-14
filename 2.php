<?php

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

