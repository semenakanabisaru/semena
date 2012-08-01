<?php

abstract class __doc_generator_adm extends baseModuleAdmin {

    public function order_list() {
    	$sel = new selector('objects');
        $sel->types('object-type')->name('emarket', 'order');
        $sel->where('status_id')->equals(array('int'=> 213));
		$sel->where('name')->notequals('dummy');
        $sel->order('order_date')->desc();
		selectorHelper::detectFilters($sel);
		$data = $this->prepareData($sel->result, "objects");

        $this->setDataType("list");
        $this->setActionType("view");
		$this->setData($data, $sel->length);

        return $this->doData();
    }

    public function get_package_labels() {
		$this->__loadLib("libs/tcpdf/tcpdf.php");
		$this->__loadLib("package_labels.php");
        if (isset($_POST['orders']) && sizeof($_POST['orders']) != 0 && sizeof($_POST['orders']) <= 12) {
            $package_labels = new package_labels($_POST['orders']);
            $package_labels->generate();
        }
    }

    public function get_shipment_blank() {
		$this->__loadLib("libs/PHPExcel.php");
		$this->__loadLib("libs/PHPExcel/iofactory.php");
		$this->__loadLib("shipment_blank.php");
        if (isset($_POST['orders']) && sizeof($_POST['orders']) != 0) {
            $shipment_blank = new shipment_blank($_POST['orders']);
            $shipment_blank->generate();
        }
    }

    public function get_cash_on_delivery_blank() {
		$this->__loadLib("libs/PHPExcel.php");
		$this->__loadLib("libs/PHPExcel/iofactory.php");
		$this->__loadLib("cash_on_delivery_blank.php");

		$order_id = (int) getRequest('param0');
        if ($order_id) {
            $cash_on_delivery_blank = new cash_on_delivery_blank($order_id);
            $cash_on_delivery_blank->generate();
        }
    }

}

?>
