<?php

namespace Monetha\Adapter\MG1;

use Mage_Core_Exception;
use Monetha\Adapter\CallbackUrlInterface;
use Monetha\Adapter\OrderAdapterInterface;

class OrderAdapter implements OrderAdapterInterface, CallbackUrlInterface
{
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

    private $orderId;

    public function __construct(\Mage_Sales_Model_Order $order, $orderId)
    {
        $this->order = $order;
        $this->store = $this->order->getStore();
        $this->orderId = $orderId;

        $items = $this->order->getAllItems();
        foreach ($items as $item) {
            $this->items[] = new InterceptorAdapter($item);
        }
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getGrandTotalAmount()
    {
        return $this->order->getTotalDue();
    }

    public function getCurrencyCode()
    {
        return $this->order->getOrderCurrency()->getData('currency_code');
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getBaseUrl()
    {
        return $this->store->getBaseUrl();
    }

    public function getCartId()
    {
        return $this->orderId;
    }

    public function getCallbackUrl()
    {
        return $this->store->getBaseUrl() . 'monetha/callback/handle';
    }
}
