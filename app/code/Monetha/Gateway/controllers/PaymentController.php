<?php

class Monetha_Gateway_PaymentController extends Mage_Core_Controller_Front_Action {
	public function redirectAction() {
        $_order = new Mage_Sales_Model_Order();
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $_order->loadByIncrementId($orderId);

        $orderAdapter = new Monetha_Gateway_OrderAdapter($_order);

        $authorizationRequest = new Monetha_Gateway_AuthorizationRequest();
        $deal = $authorizationRequest->createDeal($orderAdapter, $orderId);
        $paymentUrl = $authorizationRequest->getPaymentUrl($deal);
        $this->_redirectUrl($paymentUrl);
	}
}
