<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Monetha\Adapter\MG1\WebHookAdapter;
use Monetha\ConfigAdapterTrait;
use Monetha\Adapter\ConfigAdapterInterface;
use Monetha\Response\Exception\ValidationException;

class Monetha_Gateway_CallbackController extends Mage_Core_Controller_Front_Action implements ConfigAdapterInterface
{
    use ConfigAdapterTrait;

    public function handleAction()
    {
        $this->processAction();
    }

    public function processAction()
    {
        $this->testMode = Mage::getStoreConfig('payment/monetha/testMode');
        $this->monethaApiKey = Mage::getStoreConfig('payment/monetha/merchantKey');
        $this->merchantSecret = Mage::getStoreConfig('payment/monetha/merchantSecret');

        $bodyString = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_MTH_SIGNATURE'];
        $data = json_decode($bodyString);

        $orderId = $data->payload->external_order_id;

        /** @var \Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        try {
            if (!$order || !$order->hasData()) {
                throw new ValidationException('Order not found, order id = ' . $orderId, 404);
            }

            $webhookAdapter = new WebHookAdapter($order);

            $result = $webhookAdapter->processWebHook($this, $bodyString, $signature);
            if (!$result) {
                throw new ValidationException('Something went wrong.');
            }

        } catch (\Exception $e) {
            http_response_code($e->getCode() ? $e->getCode() : 500);

            echo json_encode([
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            return;
        }

        echo json_encode([
            'status' => 'OK',
            'message' => 'Processed successfully.',
        ]);
    }
}
