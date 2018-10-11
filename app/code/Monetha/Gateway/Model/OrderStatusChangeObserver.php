<?php

class Monetha_Gateway_Model_OrderStatusChangeObserver
{
    public function handleOrderStatusChange($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethod();


        if ($paymentMethod == 'monetha') {
            if ($order->getState() == 'canceled') {
                $monethaGatewayActive = (bool)Mage::getStoreConfig('payment/monetha/active', $store);
                if ($monethaGatewayActive) {
                    $merchantKey = Mage::getStoreConfig('payment/monetha/merchantKey', $store);
                    $merchantSecret = Mage::getStoreConfig('payment/monetha/merchantSecret', $store);
                    $mode = (bool)Mage::getStoreConfig('payment/monetha/testMode', $store);
                    $externalOrderId = $payment->getAdditionalInformation('external_order_id');
                    $gatewayService = new Monetha_Gateway_Helper_GatewayService($merchantSecret, $merchantKey, $mode);

                    try {
                        $gatewayService->cancelOrder($externalOrderId);
                    } catch (\Exception $ex) {
                        Mage::Log($ex->getMessage());
                    }
                }
            }
        }

        return $this;
    }
}
