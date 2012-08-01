<?php
    class doc_generator extends def_module {
        public function __construct() {
            parent::__construct(); 
            if(cmsController::getInstance()->getCurrentMode() == "admin") {
                $this->__loadLib("functions.php");
                $this->__loadLib("__admin.php");
                $this->__implement("__doc_generator_adm");
            } 
        }
    }
?>
