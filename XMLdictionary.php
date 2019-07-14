<?php

require_once('XMLdictionary_RU.php');

function fnTagTranslate(string $sContent, $bToEN):string
{
    global $aTagTranslateItemDictionary;
    global $aTagTranslateAttrDictionary;
    
    $fnModifyArrKeys = function(
        array $aItemDictionary,
        string $prefix = '',
        string $suffix = ''
    ): array
    {
        $aTmp = array(); 
        foreach ($aItemDictionary as $key => $val) {
            $aTmp[$prefix.$key.$suffix] = $prefix.$val.$suffix;
        }
        return $aTmp;
    };
    
    $fnGetKeysORValues = function($aSource, $bKeysNeeeded) use ($bToEN)
    {
        if ($bKeysNeeeded XOR !$bToEN) {
            return array_keys($aSource);
        } else {
            return array_values($aSource);
        }
    };
    
    $fnPropsTagsReplace = function ($sContent) use ($bToEN)
    {
        return preg_replace_callback(
            '/<propertyes>.*<\/propertyes>/siU',
            function ($matches) use ($bToEN){
                $sSubStr = $matches[0];
                if ($bToEN) {
                    $sSubStr = preg_replace('/<(\\pL+)(\s*.*)>(.*)<\/\1>/uU','<property name="$1" $2>$3</property>',$sSubStr);
                } else {
                    $sSubStr = preg_replace('/<property name="(.*)"(.*)>(.*)<\/property>/U','<$1$2>$3</$1>',$sSubStr); 
                }
                return $sSubStr;
            },
            $sContent
        );
    };

    
    $aItemDictionaryStart = $fnModifyArrKeys($aTagTranslateItemDictionary, '<');
    $aItemDictionaryEnd = $fnModifyArrKeys($aTagTranslateItemDictionary, '</');
    $aTagTranslateAttrDictionary = $fnModifyArrKeys($aTagTranslateAttrDictionary, ' ', '=');

    // Properties( EN to RU )
    if (! $bToEN) {
        $sContent = $fnPropsTagsReplace($sContent);
    }
    // Base Tag Begin 
    $sContent = str_replace($fnGetKeysORValues($aItemDictionaryStart, false), $fnGetKeysORValues($aItemDictionaryStart, true), $sContent);
    // Base Tag End
    $sContent = str_replace($fnGetKeysORValues($aItemDictionaryEnd, false), $fnGetKeysORValues($aItemDictionaryEnd, true), $sContent);
    // Attributes
    $sContent = str_replace($fnGetKeysORValues($aTagTranslateAttrDictionary, false), $fnGetKeysORValues($aTagTranslateAttrDictionary, true), $sContent);
    // Properties( RU to EN )
    if ($bToEN) {
        $sContent = $fnPropsTagsReplace($sContent);
    }
    
    return $sContent;
}
