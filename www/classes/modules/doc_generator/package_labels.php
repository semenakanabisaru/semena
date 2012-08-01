<?php
class package_labels {

    private $orders = array();

    private $objectsCollection; 

    public function __construct($orders_ids = array()) {
        $this->objectsCollection = umiObjectsCollection::getInstance();

        foreach($orders_ids as $order_id) {
            $order = $this->get_order(intval($order_id));
            if($order) {
                if ($order['cash_on_delivery']) {
                    array_unshift($this->orders, $order);
                } else {
                    $this->orders[] = $order;
                }
            }
        }    
    }

    public function get_order($order_id) {
        $order = $this->objectsCollection->getObject($order_id);
        if(!$order) {
            return false; 
        }
        $delivery_address = $this->objectsCollection->getObject($order->delivery_address);
        $region = $this->objectsCollection->getObject($delivery_address->region);
        //удаляем код региона в конце названия
        $region_name = preg_replace('/\(.+\)/', '', $region->name);

        $result['name'] = $delivery_address->lname." ".$delivery_address->fname.' '. $delivery_address->mname;
        $result['first_name'] = trim($delivery_address->fname);
        $result['last_name'] = trim($delivery_address->lname);
        $result['middle_name'] = trim($delivery_address->mname);
        $result['address'] = 'ул. '.$delivery_address->street.' д. '.$delivery_address->house.' кв. '.$delivery_address->flat;
        $result['city'] = trim($delivery_address->city);
        $result['region'] = trim($region_name);
        $result['zipcode'] = $delivery_address->index;

		$payment = $this->objectsCollection->getObject($order->payment_id);
        if ($payment->key === 'russian_post') {
            $result['price'] = 250 + intval($order->total_price*1.1); 
            $result['cash_on_delivery'] = true;
        } else {
            $result['cash_on_delivery'] = false;
        }

        $result['poste_restante'] = $order->delivery_id == 873;
        return $result; 
    }

    public function generate() {
        $card_height = 44.28;
        $card_width = 98.64;
        $card_margin_bottom = 4.66;
        $card_margin_right = 5.16;
        $page_padding_left = 5.59;
        $page_padding_top = 5.84;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setImageScale(0);

        $pdf->AddPage();
        $droidsansmono = $pdf->addTTFfont($_SERVER['DOCUMENT_ROOT'].'/classes/modules/doc_generator/libs/tcpdf/fonts/droidsansmono.ttf', 'TrueTypeUnicode', '', 32);
        $pdf->SetFont($droidsansmono,'',10);


        $row_height = 7;
        $card_index['x'] = -2.6;
        $card_index['y'] = $row_height*5 + 4.2;

        $card_padding_top = 5.27;
        $card_padding_left = 12.62; 
        $card_text_indent = 12.95;
        $card_text_top_fix = - 0.5;

        $price_card_indent_for_digits = 44.03;
        $price_card_indent_for_words = 1.52;
        $price_card_top_padding = 1.52;


        $position = 1;
        foreach ($this->orders as $order) {
            if ($position%2 == 0 && !$order['cash_on_delivery']) {
                $margin_left = $page_padding_left + $card_margin_right + $card_width;
            } else {
                $margin_left = $page_padding_left;
            }

            $text_x = $margin_left + $card_text_indent;
            $text_y = $page_padding_top + floor(($position-1)/2)*($card_height + $card_margin_bottom) + $card_text_top_fix;

            $pdf->Image($_SERVER['DOCUMENT_ROOT'].'/classes/modules/doc_generator/templates/label_pattern.jpg', $margin_left, $page_padding_top + floor(($position-1)/2)*($card_height + $card_margin_bottom), $card_width, $card_height, '', '', '', false, 300, '', false, false, 0);
            if ((mb_strlen($order['first_name'], 'utf8') + 
                mb_strlen($order['last_name'], 'utf8') + 
                mb_strlen($order['middle_name'], 'utf8') + 2) > 40){
                $pdf->Text($text_x, $text_y, $order['last_name'].' '.$order['first_name']);
                $pdf->Text($text_x, $text_y + $row_height, $order['middle_name']);
            } else {
                $pdf->Text($text_x, $text_y, $order['last_name'].' '.$order['first_name'].' '.$order['middle_name']);
            }

            if($order['poste_restante']) {
                $pdf->Text($text_x, $text_y + $row_height*2, 'ДО ВОСТРЕБОВАНИЯ');
            } else {
                $pdf->Text($text_x, $text_y + $row_height*2, $order['address']);
            }

            if ((mb_strlen($order['city'], 'utf8') + mb_strlen($order['region'], 'utf8') + 2) > 40) {
                $pdf->Text($text_x, $text_y + $row_height*3, $order['city'].',');
                $pdf->Text($text_x, $text_y + $row_height*4, $order['region']);
            } else {
                $pdf->Text($text_x, $text_y + $row_height*3, $order['city'].', '.$order['region']);
            }

            $pdf->Text($text_x + $card_index['x'], $text_y + $card_index['y'], $order['zipcode']);

            if($order['cash_on_delivery']) {
                $margin_left = $page_padding_left + $card_margin_right + $card_width;
                $text_x = $margin_left + $card_text_indent;
                $pdf->Image($_SERVER['DOCUMENT_ROOT'].'/classes/modules/doc_generator/templates/label_cash_on_delivery.jpg', $margin_left, $page_padding_top + floor($position/2)*($card_height + $card_margin_bottom), $card_width, $card_height, '', '', '', false, 300, '', false, false, 0);

                $price_in_words = '('.str_replace(' 00 копеек', '', price_to_str($order['price'])).')';
                $text_x = $margin_left + $price_card_indent_for_digits;
                $text_y = $page_padding_top + floor(($position-1)/2)*($card_height + $card_margin_bottom) - 1.5 ;

                $pdf->Text($text_x, $text_y + $row_height, $order['price'].' руб.');
                $pdf->Text($text_x, $text_y + $row_height*3, $order['price'].' руб.');

                $text_x = $margin_left + $price_card_indent_for_words;
                $pdf->SetFont($droidsansmono,'', 7.5);
                $pdf->Text($text_x, $text_y + $row_height*2, $price_in_words);
                $pdf->Text($text_x, $text_y + $row_height*4, $price_in_words);
                $pdf->SetFont($droidsansmono,'',10);
                $position++;
            }

            $position++;
        }

        $pdf->Output();
        exit();
    }
}
