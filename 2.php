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
