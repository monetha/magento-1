<?php

class Monetha_Gateway_InterceptorAdapter implements Monetha_Gateway_Interceptor {
    /**
     * @var \Mage_Sales_Model_Order_Item
     */
    private $item;

    private $data;

    public function __construct(\Mage_Sales_Model_Order_Item $item) {
        $this->item = $item;
        $this->data = $this->item->getData();
    }

    public function getPrice() {
        return $this->item->getOriginalPrice();
    }

    public function getName() {
        return $this->data['name'];
    }

    public function getQtyOrdered() {
        return $this->data['qty_ordered'];
    }
}
