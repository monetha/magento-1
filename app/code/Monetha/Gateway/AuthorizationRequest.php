<?php

class Monetha_Gateway_AuthorizationRequest
{
    private $merchantKey = '';
    private $merchantSecret = '';
    private $testMode = false;

    public function __construct()
    {
        $this->testMode = (bool)Mage::getStoreConfig('payment/monetha/testMode');
        $this->merchantKey = Mage::getStoreConfig('payment/monetha/merchantKey');
        $this->merchantSecret = Mage::getStoreConfig('payment/monetha/merchantSecret');
    }

    /**
     * @param array $deal
     *
     * @return string
     * @throws \Exception
     */
    public function getPaymentUrl(Monetha_Gateway_OrderAdapterInterface $order, $orderId)
    {
        $gatewayService = new Monetha_Gateway_Helper_GatewayService($this->merchantSecret, $this->merchantKey, $this->testMode);
        try {
            $offerBody = $gatewayService->prepareOfferBody($order, $orderId);
            Mage::Log(json_encode($offerBody));
            $offerResponse = $gatewayService->createOffer($offerBody);
            $executeOfferResponse = $gatewayService->executeOffer($offerResponse->token);

            $currentOrder = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $currentOrder->addStatusHistoryComment('Peyment url: ' . $executeOfferResponse->order->payment_url);
            $currentOrder->save();
            $payment = $currentOrder->getPayment();
            $payment->setAdditionalInformation('payment_url', $executeOfferResponse->order->payment_url);
            $payment->setAdditionalInformation('external_order_id', $executeOfferResponse->order->id);
            $payment->save();

            return $executeOfferResponse->order->payment_url;
        } catch (\Exception $ex) {
            Mage::Log($ex->getMessage());
            Mage::throwException('Failed to create order. ' . $ex->getMessage());
        }
    }
}
