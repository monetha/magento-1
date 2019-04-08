<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Monetha\Adapter\MG1\ConfigAdapter;
use Monetha\Adapter\MG1\OrderAdapter;
use Monetha\Services\GatewayService;
use Monetha\Adapter\MG1\ClientAdapter;
use Monetha\Response\Exception\ApiException;

class Monetha_Gateway_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function redirectAction()
    {
        // Create order
        $order = new Mage_Sales_Model_Order();
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order->loadByIncrementId($orderId);

        try {
            // Prepare order in monetha side
            $orderAdapter = new OrderAdapter($order, $orderId);

            $configAdapter = new ConfigAdapter();

            $order_data = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $billing_address = $order_data->getBillingAddress();

            $clientAdapter = new ClientAdapter($billing_address);
            $gatewayService = new GatewayService($configAdapter);

            $executeOfferResponse = $gatewayService->getExecuteOfferResponse($orderAdapter, $clientAdapter);

            $paymentUrl = $executeOfferResponse->getPaymentUrl();

            $currentOrder = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $currentOrder->addStatusHistoryComment('Peyment url: ' . $paymentUrl);
            $currentOrder->save();
            $payment = $currentOrder->getPayment();
            $payment->setAdditionalInformation('payment_url', $paymentUrl);
            $payment->setAdditionalInformation('external_order_id', $executeOfferResponse->getOrderId());
            $payment->save();

        } catch (ApiException $e) {
            $message = sprintf(
                'Status code: %s, error: %s, message: %s',
                $e->getApiStatusCode(),
                $e->getApiErrorCode(),
                $e->getMessage()
            );
            error_log($message);
            Mage::Log($e->getMessage());
            Mage::throwException('Failed to create order. ' . $e->getFriendlyMessage());

            return;

        } catch (\Exception $ex) {
            Mage::Log($ex->getMessage());
            Mage::throwException('Failed to create order. ' . $ex->getMessage());

            return;
        }

        if (!isset($paymentUrl)) {
            return;
        }

        // Create invoice
        $invoice = $order->prepareInvoice();
        $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);
        $invoice->save();
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transactionSave->save();

        $this->_redirectUrl($paymentUrl);
    }
}
