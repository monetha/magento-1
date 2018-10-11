<?php

class Monetha_Gateway_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function redirectAction()
    {
        // Create order
        $order = new Mage_Sales_Model_Order();
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order->loadByIncrementId($orderId);

        // Create invoice
        $invoice = $order->prepareInvoice();
        $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);
        $invoice->save();
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transactionSave->save();

        // Prepare order in monetha side
        $orderAdapter = new Monetha_Gateway_OrderAdapter($order);
        $authorizationRequest = new Monetha_Gateway_AuthorizationRequest();
        $paymentUrl = $authorizationRequest->getPaymentUrl($orderAdapter, $orderId);
        $this->_redirectUrl($paymentUrl);
    }
}
