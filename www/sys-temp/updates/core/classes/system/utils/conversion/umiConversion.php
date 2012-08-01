<?php
/**
 * Ядро библиотеки преобразований
 * @author Leeb <ignat@umisoft.ru>
 * @version 1.0
 */
class umiConversion {
    /**    
    * @var umiConversion экземпляр класса для Singleton-а
    */
    private static $m_oInstance = null;
    /**
    * @var Array содержит уже загруженные процедуры преобазования
    */
    private $m_aProcedures = array();
    /**
    * @desc Private конструктор, для запрещения создания объекта извне
    */
    private function __construct() {
        // Nothing to do        
    }
    /**
    * @desc Деструктор для совершения пост-действий (если таковые имеются)
    */
    public function __destruct() {
        // Nothing to do
    }
    /**
    * @desc Возвращает экземпляр класса
    * @return umiConversion
    */
    public static function getInstance() {
        if(self::$m_oInstance == null) {
            self::$m_oInstance = new umiConversion();
        }
        return self::$m_oInstance;
    }
    /**
    * @desc Перегруженный оператор вызова: транслирует вызов на соответствующую процедуру преобразования
    * @param String $_sMethodName Имя преобразования
    * @param Array $_aMethodArguments Аргументы преобразования
    * @return Mixed Результат преобразования
    */
    public function __call($_sMethodName, $_aMethodArguments) {
        if(!isset($this->m_aProcedures[$_sMethodName])) {
            $sClassName   = $_sMethodName;
            $sIncludePath = dirname(__FILE__) . '/procedures/' . $sClassName . '.conversion.php';
            if(!file_exists($sIncludePath)) return (count($_aMethodArguments) == 1) ? $_aMethodArguments[0] : $_aMethodArguments ; 
            include($sIncludePath);
            $oConversion  = new $sClassName();            
            $this->m_aProcedures[$_sMethodName] = $oConversion;            
        }
        $oConversion = $this->m_aProcedures[$_sMethodName];
        if($oConversion instanceof IGenericConversion)
            return $oConversion->convert($_aMethodArguments);
        else
            return (count($_aMethodArguments) == 1) ? $_aMethodArguments[0] : $_aMethodArguments ;
    }
}
?>
