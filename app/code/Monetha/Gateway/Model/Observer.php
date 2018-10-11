<?php

//require_once dirname(__FILE__) . '/../Helper/GatewayService.php';

class Monetha_Gateway_Model_Observer
{
    public function changeSystemConfig(Varien_Event_Observer $observer)
    {
        $store = Mage::app()->getStore();
        $monethaGatewayActive = (bool)Mage::getStoreConfig('payment/monetha/active', $store);

        if ($monethaGatewayActive) {
            $title = Mage::getStoreConfig('payment/monetha/title', $store);
            $merchantKey = Mage::getStoreConfig('payment/monetha/merchantKey', $store);
            $merchantSecret = Mage::getStoreConfig('payment/monetha/merchantSecret', $store);
            $mode = (bool)Mage::getStoreConfig('payment/monetha/testMode', $store);

            // Validate empty fields
            if (empty($title) ||
                empty($merchantKey) ||
                empty($merchantSecret)) {
                Mage::throwException('Please fill in required fields');
                return;
            }

            // Validate api token
            $gatewayService = new Monetha_Gateway_Helper_GatewayService($merchantSecret, $merchantKey, $mode);
            if (!$gatewayService->validateApiKey()) {
                Mage::throwException('Merchant secret or Monetha Api Key is not valid.');
            }
        }

        return $this;
    }
}
