<?php
class shipment_blank {

    private $orders = array();

    private $objectsCollection; 
    private $objectTypesCollection;
	private $hierarchy;

    public function __construct($orders_ids = array()) {
        $this->objectsCollection = umiObjectsCollection::getInstance();
        $this->objectTypesCollection = umiObjectTypesCollection::getInstance();
		$this->hierarchy = umiHierarchy::getInstance();

        foreach($orders_ids as $order_id) {
            $order = $this->get_order(intval($order_id));
            if($order) {
                $this->orders[] = $order;
            }
        }    
    }

    public function generate() {
        $blank_template = $_SERVER['DOCUMENT_ROOT'].'/classes/modules/doc_generator/templates/shipment_template.xls';
        $objPHPExcel = PHPExcel_IOFactory::load($blank_template);
        $worksheet = $objPHPExcel->getActiveSheet();

        for($i = 0; $i < sizeof($this->orders); $i++) {
            $worksheet->setCellValue('A'.($i+2), $i+1);
            $worksheet->setCellValue('B'.($i+2), '#'.$this->orders[$i]['number']);
            $worksheet->setCellValue('C'.($i+2), implode("\n", $this->orders[$i]['goods']));
            $worksheet->getStyle('C'.($i+2))->getAlignment()->setWrapText(true);
            $worksheet->setCellValue('D'.($i+2), $this->orders[$i]['price']);
            $worksheet->setCellValue('E'.($i+2), $this->orders[$i]['customer_last_name']);
            $worksheet->setCellValue('F'.($i+2), $this->orders[$i]['comment']);
            $worksheet->getRowDimension($i+2)->setRowHeight(-1);
        }

        $worksheet->duplicateStyle($worksheet->getStyle('A2'), 'A2:F'.(sizeof($this->orders)+1));
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="shipment_blank.xls"');
        header('Cache-Control: max-age=0');

        $objWriter->save('php://output');
        exit();
    }

    private function get_order($order_id) {
        $order = $this->objectsCollection->getObject($order_id);
        if($order === false) {
            return false;
        }

        $delivery_address = $this->objectsCollection->getObject($order->delivery_address);
        $result['customer_last_name'] = $delivery_address->lname;
        $result['goods'] = $this->get_order_items_names($order);
        $result['number'] = $order->number;
        $result['comment'] = $order->kommentarij;
        $result['price'] = $this->calculate_order_price($order);

        return $result;
    }

    private function get_order_items_names($order)  {
        $items = $order->getValue('order_items');
		for ($i = 0; $i < sizeof($items); $i++){
			$item = $this->objectsCollection->getObject($items[$i]);
			$link_from_item_to_catalog = $item->getValue('item_link');
            if (isset($link_from_item_to_catalog[0])) {
                $link_to_catalog = $link_from_item_to_catalog[0];
                $page = $this->hierarchy->getElement($link_to_catalog->id);
                $parent = $this->hierarchy->getElement($page->getParentId());
                for($j = 0; $j < $item->item_amount; $j++) {
                    $items_names[] = $parent->getObject()->getName().' '.$page->getName();
                } 
            } else {
                $items_names[] = 'товара нет на складе (какого непонятно)';
            }
        }
        return $items_names;
    }

    private function calculate_order_price($order) {
		$payment = $this->objectsCollection->getObject($order->payment_id);
        if ($payment->key === 'russian_post') {
            return 250 + intval($order->total_price*1.1); 
        } else {
            return 'Заказное ('.str_replace('Оплата через ', '', $payment->getName()).')';
        }
    }
} 
?>
