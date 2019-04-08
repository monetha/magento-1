<?php

namespace Monetha\Adapter\MG1;
use Monetha\Adapter\InterceptorInterface;
use Monetha_Gateway_Interceptor;

class InterceptorAdapter implements InterceptorInterface
{
    /**
     * @var \Mage_Sales_Model_Order_Item
     */
    private $item;

    private $data;

    public function __construct(\Mage_Sales_Model_Order_Item $item)
    {
        $this->item = $item;
        $this->data = $this->item->getData();
    }

    public function getPrice()
    {
        return $this->item->getOriginalPrice();
    }

    public function getName()
    {
        return $this->data['name'];
    }

    public function getQtyOrdered()
    {
        return $this->data['qty_ordered'];
    }
}
