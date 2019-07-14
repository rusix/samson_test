<?php
namespace Test;

/**
 * SOME RESULT WITH SOME ARGUMENTS (ABSTRACT)
 * 
 * @method float exp1 (float $a,  float $b, float $c) Calc: a*(b^c)
 * @method float exp2 (float $a, float $b, float  $c) Calc: (a/b)^c
 * @method getValue USE IN CHILD CLASS
 */
abstract class BaseMath
{
    public function exp1(float $a,  float $b, float $c):float
    {
        return $a * ($b ** $c);
    }
    public function exp2(float $a, float $b, float  $c):float
    {
        return (($a / $b) ** $c);
    }
    abstract public function getValue();
}





/**
 * SOME RESULT WITH SOME ARGUMENTS (EXTEND)
 * 
 * @method __construct (float $a,  float $b, float $c) 
 *         Some arguments, BUT $c <> 0 !!!
 * @method float getValue SOME RESULT
 */
class F1 extends BaseMath
{
    private $numA;
    private $numB;
    private $numC;
    public function __construct(float $a,  float $b, float $c)
    {
        $this->numA = $a;
        $this->numB = $b;
        $this->numC = $c;
    }
    public function getValue():float
    {
        $a = $this->numA;
        $b = $this->numB;
        $c = $this->numC;
        // calc: (a*(b^c)+(((a/c)^b)%3)^min(a,b,c))
        return 
            ($a* ($b ** $c) + ((($a / $c) ** $b) % 3) ** min($a,$b,$c));
    }
}





/**
 * RETURN MIN VALUE FROM ARRAY
 * 
 * @param array $a 
 * 
 * @return Float
 */
function getMin(array $a):float
{
    $arrA = $a;
    if (! (count($arrA) > 0)) {
            throw new Exception("getMin(): Param is emty");
    }
    usort($arrA,
        function($a, $b) 
        {
            if (! (is_numeric($a) && is_numeric($b))) {
                    throw new Exception("getMin(): Incorrect type in parameter item");
            }
            return $a <=> $b; 
        }
    );
    return $arrA[0];
    
}





/**
 * RETURN ARRAY OF SIMPLE DIGITS BETWEEN A B
 * 
 * @param int $a ( > 0 )
 * @param int $b ( > 0 )
 * 
 * @return Array       
 */
function findSimple(int $a, int $b):array
{
    $aRange = array();
    $aSimpleKeys = array(2, 3, 5, 7);
    
    if (! ($a > 0 && $b > 0 ) ){
        throw new Exception('findSimple(): Bad value params');
    }
    $aRange = range($a, $b, 1);
    foreach($aRange as $iKey => $iVal) {
        foreach($aSimpleKeys as $iSimpleVal) {
            if ($iVal == 1 || ( $iVal != $iSimpleVal && fmod($iVal, $iSimpleVal) == 0)) {
                unset ($aRange[$iKey]);
            }
        }
    }
    
    return $aRange;    
}





/**
 * MODIFY INPUT ARRAY TO 2xARRAY ON MASK
 * 
 * @param array $a ( Array of posive digits, count divisible 3 )
 *        Simple [1, 2, 3, 4, 5, 6]
 *
 * @var int iMod :Count of items for return in sub arrays
 * @var array arrFiller :Key list for return sub arrays
 *  
 * @return Array (2xArray with key: a,b,c)
 *         Simple [[‘a’=>1,’b’=>2,’с’=>3],[‘a’=>4,’b’=>5 ,’c’=>6]]
 */
function createTrapeze(array $a):array
{
    $iMod = 3;
    $arrFiller = array('a','b','c');
    
    $arrA = $a;
    $iCount = count($arrA);
    if (! ($iCount > 0 && fmod($iCount, $iMod) == 0 )) {
        throw new Exception("createTrapeze(): Bad value param (items count)");
    }
    foreach ($arrA as $val) {
        if (! (int)$val > 0) {
            throw new Exception("createTrapeze(): Bad value on param item ({$val})");
        }
    }
    $iPointer = 0;
    $arrReturn = array();
    $arrTmp = array();
    while ($iPointer < $iCount) {
        foreach ($arrFiller as $val) {
            $arrTmp[$val] = $arrA[$iPointer];
            $iPointer++;
        }
        $arrReturn[] = $arrTmp;
    }
    return $arrReturn;
    
}





/**
 * MODIFY 2xARRAY: ADD CALCULATED ITEM
 * 
 * @param array $a ( 2xArray of positive int digits, separate on part (def 3) )
 *        Simple [[‘a’=>1,’b’=>2,’с’=>3],[‘a’=>4,’b’=>5 ,’c’=>6]]
 * 
 * @var array arrFiller :Key list for sub arrays
 * @var string sCalcKeyName :Key for calc field
 * 
 * @return Array (2xArray with new calc key (s))
 *         Simple [[‘a’=>1,’b’=>2,’с’=>3,’s’=>4.5],[‘a’=>4,’b’=>5 ,’c’=>6,’s’=>27]]
 */
function squareTrapeze(array $a):array
{  
    $arrFiller = array('a','b','c');
    $sCalcKeyName = 's';
    
    $iKeyA = $arrFiller[0];
    $iKeyB = $arrFiller[1];
    $iKeyC = $arrFiller[2];
    $arrA = $a;
    
    foreach ($arrA as &$arrAVal) {
        if (count($arrAVal) > count($arrFiller)) {
            throw new Exception("squareTrapeze(): One or more ParamsItem mismatch filter mask");
        }
        foreach ($arrFiller as $arrFillerVal) {
            if (! (isset($arrAVal[$arrFillerVal]) && $arrAVal[$arrFillerVal] > 0 )) {
                throw new Exception("squareTrapeze(): Bad value on param item ({$arrAVal[$arrFillerVal]})");
            }
        }
        $iValA = $arrAVal[$iKeyA];
        $iValB = $arrAVal[$iKeyB];
        $iValC = $arrAVal[$iKeyC];
        $arrAVal[$sCalcKeyName] = 0.5 * $iValC * ($iValA + $iValB);
    }
    return $arrA;
    
}





/**
 * RETURN MIN VALUE FROM ARRAY
 * 
 * @param array $a (2xArray with new calc key (s))
 *        Simple [[‘a’=>1,’b’=>2,’с’=>3,’s’=>4.5],[‘a’=>4,’b’=>5 ,’c’=>6,’s’=>27]]
 * 
 * @var array arrFiller :Key list for return sub arrays
 * @var string sCalcKeyName :Key for calc field
 * @var string sHt :Horizontal filler
 * @var string sVt :Vertiacal filler
 * @var string sMarker :Marker for need field (auto set)
 * @var string sSpace :Char for space fill
 * @var string iSpace :Count of space fill char in one field
 * @var bool bConsoleMode :Do not wrap code to <pre> tag
 */
function printTrapeze(array $a)
{
    $arrFiller = array('a','b','c');
    $sCalcKeyName = 's';
    $sHt = '-';
    $sVt = '|';
    $sMarker = '*';
    $sSpace = chr(32);
    $iSpace = 2;
    $bConsoleMode = false;
    
    $arrA = $a;
    $arrHeader = array();
    $sContent = '';
    
    // CALC MIN SPACE
    $iMaxLen = 0;
    array_walk_recursive($arrA, function ($item, $key) use (&$iMaxLen)
        {
            $iMaxLen = max(strlen((string)$item), $iMaxLen);
        }
    );
    $iSpace += $iMaxLen + strlen($sMarker);
    // BUILD HEADER
    foreach ($arrFiller as $sVal){
       $arrHeader[] = $sVal; 
    }
    $arrHeader[] = $sCalcKeyName;
    array_unshift($arrA, $arrHeader);
    // RUN PREPARE TO PRINT
    function fillRow($sHt, $iCount, $iSpace):string
    {
        return str_pad('', $iCount * $iSpace + $iCount + 1, $sHt); 
    }
    foreach ($arrA as $arrARowVal){
        $sContent .= fillRow($sHt, count($arrA[0]), $iSpace);
        $sContent .= PHP_EOL.$sVt;
        foreach ($arrARowVal as $sColKey => $sColVal){
            $sFiller = '';
            $iSpaceTmp = $iSpace;
            // SET MARKER (calc column && integer && odd)
            if (
                ($sColKey == $sCalcKeyName) && 
                is_numeric($sColVal) && 
                ((floor($sColVal) - $sColVal) == 0) && 
                (fmod($sColVal, 2) == 1)
            ){
                $iSpaceTmp = $iSpaceTmp - strlen($sMarker);
                $sFiller .= $sMarker;  
            }
            $sFiller = $sFiller.str_pad('', abs($iSpaceTmp - strlen($sColVal)), $sSpace);
            $sContent .= $sFiller.$sColVal.$sVt;
        }
        $sContent .= PHP_EOL;
    }
    $sContent .= fillRow($sHt, count($arrA[0]), $iSpace);
    if (! $bConsoleMode){
        $sContent = '<pre>'.$sContent.'</pre>';
    }
    // PRINT
    echo $sContent;
}





/**
 * FILTER 2xARRAY WITH CALC ITEM ON LIMIT (MAX) PARAM
 * 
 * @param array $a ( 2xArray of positive digits + calc value )
 *        Simple [[‘a’=>1,’b’=>2,’с’=>3,’s’=>4.5],[‘a’=>4,’b’=>5 ,’c’=>6,’s’=>27]]
 * 
 * @param float $b ( Max calc value in param in $a (0 < CALC ITEM <= MAX ) )
 * 
 * 
 * @return Array (Filtered param $a)
 */
function getSizeForLimit(array $a, float $b):array
{
    $sCalcKeyName = 's';
    $arrA = $a;
    $ftMax = $b;
    $arrReturn = array();
    
    if (! ($b > 0)) {
            throw new Exception("getSizeForLimit(): param $b must be greater than zero");
    }
    foreach ($arrA as $arrVal) {
        if (! (isset($arrVal[$sCalcKeyName]) && is_double($arrVal[$sCalcKeyName]))) {
            throw new Exception("getSizeForLimit(): One or more ParamsItem mismatch type or nonexist");
        }
        if ($arrVal[$sCalcKeyName] <= $ftMax) {
            $arrReturn[] = $arrVal;
        }
    }
    return $arrReturn;
    
}
