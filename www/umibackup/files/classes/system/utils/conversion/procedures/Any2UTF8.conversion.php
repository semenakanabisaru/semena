<?php
class Any2UTF8 implements IGenericConversion {  
    
    public function convert($_aArguments) {
        $text          = isset($_aArguments[0]) ? $_aArguments[0] : '';
        $textConverted = rawurldecode($text);
        if ($textConverted) $text = $textConverted;        
        $sCharset = self::detectCharset($text);
        if (function_exists('iconv') && $sCharset !== 'UTF-8') {
            $textConverted = @iconv($sCharset, 'UTF-8', $text);
            if ($textConverted) $text = $textConverted;
        }        
        return $text;
    }
    
    private static function winToLowercase($sStr) {
        for($i=0;$i<strlen($sStr);$i++) {
            $c = ord($sStr[$i]);
            if ($c >= 0xC0 && $c <= 0xDF) { // А-Я
                  $sStr[$i] = chr($c+32);
            } elseif ($sStr[$i] >= 0x41 && $sStr[$i] <= 0x5A) { // A-Z
                  $sStr[$i] = chr($c+32);
            }
          }
         return $sStr;
    }
        
    private static function detectCharset($sStr) {
        /*
         * TODO :
         * проверка на заглавную после строчной
         * проверка на наиб. употребительные пары и тройки
         * проверка по чаркодам на вероятность чарсета
         * проверка на предпочтения в заголовках запроса клиента на вероятный чарсет
         */
        // ==== detect utf-8:
        if (preg_match("/[\x{0000}-\x{FFFF}]+/u", $sStr)) return 'UTF-8';
        // ==== detect others
        $sAnswer = 'CP1251';
        if (!function_exists('iconv')) return $sAnswer;
        //
        $arrCyrEncodings = array(
            'CP1251',
            // 'CP855', // --enable-extra-encodings
            // 'MacUkraine', // ukraine
            // 'KOI8-U', // ukraine
            'KOI8-R',
            'UTF-8',
            'ISO-8859-5',
            'MacCyrillic',
            'CP866'
        );
        //
        include_once(dirname(__FILE__)."/__charssequences.ru.php"); 
        //        
        $arrNonexWinRu3 = __charssequences_ru::getNonexistingWinRuTriplets();
        $arrNonexWinRu2 = __charssequences_ru::getNonexistingWinRuPairs();
        //
        $arrTestRezs = array();
        foreach ($arrCyrEncodings as $iCyrEnc=>$sCyrEnc) {
            if ($sCyrEnc !== 'CP1251') {
                $sTestStr = @iconv($sCyrEnc, 'CP1251', $sStr);
            } else {
                $sTestStr = $sStr;
            }
            if ($sTestStr && (strlen($sStr)/strlen($sTestStr))<=3) {
                $arrTestRezs[$sCyrEnc] = array('badtrios'=>0, 'badpairs'=>0, 'badchars'=>0, 'goodchars'=>0, 'length'=>0, 'goodindex'=>0);
                $sTestStr = str_replace(chr(0xB8), chr(0xE5), $sTestStr); // ё -> е
                $sTestStr = str_replace(chr(0xA8), chr(0xC5), $sTestStr); // Ё -> Е
                $sTestStr = self::winToLowercase($sTestStr);
                for ($i=0; $i<(strlen($sTestStr)-1); $i++) {
                    $iCh0 = ord($sTestStr[$i]);
                    if ($iCh0 < 0x20) {
                        $arrTestRezs[$sCyrEnc]['badchars'] += 1;
                    }
                    if (in_array(substr($sTestStr, $i, 2), $arrNonexWinRu2)) $arrTestRezs[$sCyrEnc]['badpairs'] += 1;
                    if (in_array(substr($sTestStr, $i, 3), $arrNonexWinRu3)) $arrTestRezs[$sCyrEnc]['badtrios'] += 1;
                }
                //
                $iGoodChars = intval(preg_match_all("/[\xE0-\xFF\x61-\x7A\-_ \"']/is", $sTestStr, $arrTmp));
                $iAllChars = strlen($sTestStr);
                $arrTestRezs[$sCyrEnc]['goodchars'] = $iGoodChars;
                $arrTestRezs[$sCyrEnc]['length'] = $iAllChars;
                //
                $fGoodPercents = $iGoodChars/($iAllChars/100);
                $arrTestRezs[$sCyrEnc]['goodindex'] = ceil($fGoodPercents);//*$iGoodChars;
            }
        }
        //
        $arrMbValids = array();
        foreach ($arrTestRezs as $sCyrEnc=>$arrEncRezs) {
            if ($arrEncRezs['goodindex'] === 100 || ($arrEncRezs['badchars'] === 0 && $arrEncRezs['badpairs'] === 0 && $arrEncRezs['badtrios'] === 0)) {
                if (!isset($arrMbValids[$arrEncRezs['goodindex']])) $arrMbValids[$arrEncRezs['goodindex']] = $sCyrEnc;
            }
        }
        //
        if (count($arrMbValids)) {
            krsort($arrMbValids);
            foreach ($arrMbValids as $iGoodIndex=>$sCyrEnc) {
                $sAnswer = $sCyrEnc;
                break;
            }
        }
        //
        return $sAnswer;
    }    
}
?>
