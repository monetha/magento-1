<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Monetha\ConfigAdapterTrait;
use Monetha\Services\GatewayService;
use Monetha\Adapter\ConfigAdapterInterface;

class Monetha_Gateway_Model_Observer implements ConfigAdapterInterface
{
    use ConfigAdapterTrait;

    public function changeSystemConfig(Varien_Event_Observer $observer)
    {
        $store = Mage::app()->getStore();
        $monethaGatewayActive = Mage::getStoreConfig('payment/monetha/active', $store);

        if (!$monethaGatewayActive) {
            return $this;
        }

        $title = Mage::getStoreConfig('payment/monetha/title', $store);
        $this->monethaApiKey = Mage::getStoreConfig('payment/monetha/merchantKey', $store);
        $this->merchantSecret = Mage::getStoreConfig('payment/monetha/merchantSecret', $store);
        $this->testMode = Mage::getStoreConfig('payment/monetha/testMode', $store);

        // Validate empty fields
        if (empty($title) ||
            empty($this->monethaApiKey) ||
            empty($this->merchantSecret)) {
            Mage::throwException('Please fill in required fields');
            return $this;
        }

        $gs = new GatewayService($this);

        // Validate api token
        if (!$gs->validateApiKey()) {
            Mage::throwException('Merchant secret or Monetha Api Key is not valid.');
        }

        return $this;
    }
}
