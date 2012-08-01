<?php
class cash_on_delivery_blank {

    private $order;

    private $objectsCollection; 

    public function __construct($order_id) {
        $this->objectsCollection = umiObjectsCollection::getInstance();
        $this->order = $this->objectsCollection->getObject($order_id);
    }

    public function generate() {
        if(!$this->order) {
            return false;
        }

		$payment = $this->objectsCollection->getObject($this->order->payment_id);
        if ($payment->key === 'russian_post') {
            $total_price = 250 + intval($this->order->total_price*1.1); 
        } else {
            return false;
        }
        $total_price_in_words = price_to_str($total_price);

        $blank_template = $_SERVER['DOCUMENT_ROOT'].'/classes/modules/doc_generator/templates/cash_on_delivery_template.xls';
        $objPHPExcel = PHPExcel_IOFactory::load($blank_template);
        
        $objPHPExcel->setActiveSheetIndex(0);
        $worksheet = $objPHPExcel->getActiveSheet();
        $worksheet->setCellValue('W13', $total_price.' - 00');
        $worksheet->setCellValue('J16', $total_price_in_words);

        $objPHPExcel->setActiveSheetIndex(1);
        $worksheet = $objPHPExcel->getActiveSheet();
        $worksheet->setCellValue('AC8', $total_price.' - 00');
        
        $objPHPExcel->setActiveSheetIndex(0);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="order_blank.xls"');
        header('Cache-Control: max-age=0');

        $objWriter->save('php://output');
        exit();
    }
} 
?>
