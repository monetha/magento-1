<?php
class Monetha_Gateway_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'monetha';
	
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('monetha/payment/redirect', array('_secure' => true));
	}
}
