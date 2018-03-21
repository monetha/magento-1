<?php

class Monetha_Gateway_OrderAdapter implements Monetha_Gateway_OrderAdapterInterface {
    /**
     * @var \Mage_Sales_Model_Order
     */
    private $order;

    /**
     * @var \Mage_Core_Model_Store
     */
    private $store;

    /**
     * @var array
     */
    private $items = [];

    public function __construct(\Mage_Sales_Model_Order $order) {
        $this->order = $order;
        $this->store = $this->order->getStore();

        $items = $this->order->getAllItems();
        foreach ($items as $item) {
            $this->items[] = new Monetha_Gateway_InterceptorAdapter($item);
        }
    }

    public function getItems() {
        return $this->items;
    }

    public function getGrandTotalAmount() {
        return $this->order->getTotalDue();
    }

    public function getCurrencyCode() {
        return $this->order->getOrderCurrency()->getData('currency_code');
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getBaseUrl() {
        return $this->store->getBaseUrl();
    }
}
