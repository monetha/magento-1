<?php

namespace Monetha\Adapter\MG1;
use Mage;
use Monetha\Adapter\ConfigAdapterInterface;
use Monetha\ConfigAdapterTrait;
use Monetha_Gateway_OrderAdapterInterface;

class ConfigAdapter implements ConfigAdapterInterface
{
    use ConfigAdapterTrait;

    public function __construct()
    {
        $this->testMode = Mage::getStoreConfig('payment/monetha/testMode');
        $this->monethaApiKey = Mage::getStoreConfig('payment/monetha/merchantKey');
        $this->merchantSecret = Mage::getStoreConfig('payment/monetha/merchantSecret');
    }
}
