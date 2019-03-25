<?php

class Monetha_Gateway_CallbackController extends Mage_Core_Controller_Front_Action
{
    public function handleAction()
    {
        $this->processAction();
    }

    public function processAction()
    {
        $testMode = (bool)Mage::getStoreConfig('payment/monetha/testMode');
        $merchantKey = Mage::getStoreConfig('payment/monetha/merchantKey');
        $merchantSecret = Mage::getStoreConfig('payment/monetha/merchantSecret');

        $gateway_service = new Monetha_Gateway_Helper_GatewayService($merchantSecret,$merchantKey,$testMode);

        $body = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_MTH_SIGNATURE'];
        $data = json_decode($body);

        if ($data->event == Monetha_Gateway_Const_EventType::PING) {
            return [
                'message' => 'e-shop healthy'
            ];
        }

        if ($gateway_service->validateSignature($signature, $body)) {
            return $gateway_service->processAction($data);
        } else {
            return 'Bad signature';
        }
    }
}
