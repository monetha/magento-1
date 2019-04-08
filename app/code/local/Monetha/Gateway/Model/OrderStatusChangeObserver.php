<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Monetha\ConfigAdapterTrait;
use Monetha\Adapter\ConfigAdapterInterface;
use Monetha\Services\GatewayService;

class Monetha_Gateway_Model_OrderStatusChangeObserver implements ConfigAdapterInterface
{
    use ConfigAdapterTrait;

    public function handleOrderStatusChange($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethod();


        if ($paymentMethod != 'monetha') {
            return $this;
        }

        if ($order->getState() != 'canceled') {
            return $this;
        }

        // TODO: what is store?
        $monethaGatewayActive = (bool)Mage::getStoreConfig('payment/monetha/active', $store);
        if (!$monethaGatewayActive) {
            return $this;
        }

        $this->monethaApiKey = Mage::getStoreConfig('payment/monetha/merchantKey', $store);
        $this->merchantSecret = Mage::getStoreConfig('payment/monetha/merchantSecret', $store);
        $this->testMode = Mage::getStoreConfig('payment/monetha/testMode', $store);

        $externalOrderId = $payment->getAdditionalInformation('external_order_id');

        $gs = new GatewayService($this);

        try {
            $gs->cancelExternalOrder($externalOrderId);
        } catch (\Exception $ex) {
            Mage::Log($ex->getMessage());
        }
    }
}
