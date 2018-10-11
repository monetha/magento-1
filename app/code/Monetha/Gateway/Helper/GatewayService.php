<?php

class Monetha_Gateway_Helper_GatewayService extends Mage_Core_Helper_Abstract
{
    public $merchantSecret;
    public $mthApiKey;
    public $testMode;

    public function __construct($merchantSecret, $mthApiKey, $testMode)
    {
        $this->merchantSecret = $merchantSecret;
        $this->mthApiKey = $mthApiKey;
        $this->testMode = $testMode;
    }

    public function validateApiKey()
    {
        $apiUrl = $this->getApiUrl();
        $merchantId = $this->getMerchantId();

        if ($merchantId == null) {
            return false;
        }

        $apiUrl = $apiUrl . 'v1/merchants/' . $merchantId .'/secret';

        $response = Monetha_Gateway_Helper_HttpService::callApi($apiUrl, 'GET', null, ["Authorization: Bearer " . $this->mthApiKey]);
        return ($response && $response->integration_secret && $response->integration_secret == $this->merchantSecret);
    }

    public function getMerchantId()
    {
        $tks = explode('.', $this->mthApiKey);
        if (count($tks) != 3) {
            return null;
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        $payload = Monetha_Gateway_Helper_JWT::jsonDecode(Monetha_Gateway_Helper_JWT::urlsafeB64Decode($bodyb64));

        if (isset($payload->mid)) {
            return $payload->mid;
        }

        return null;
    }

    public function getApiUrl()
    {
        $apiUrl = Monetha_Gateway_Const_ApiType::PROD;

        if ((bool)$this->testMode) {
            $apiUrl = Monetha_Gateway_Const_ApiType::TEST;
        }

        return $apiUrl;
    }

    public function cancelOrder($orderId)
    {
        $apiUrl = $this->getApiUrl();
        $apiUrl = $apiUrl . 'v1/orders/' . $orderId .'/cancel';
        $body = ['cancel_reason'=> 'Order cancelled from shop'];
        return Monetha_Gateway_Helper_HttpService::callApi($apiUrl, 'POST', $body, ["Authorization: Bearer " . $this->mthApiKey]);
    }

    public function createOffer($offerBody)
    {
        $apiUrl = $this->getApiUrl();
        $apiUrl = $apiUrl . 'v1/merchants/offer_auth';

        return Monetha_Gateway_Helper_HttpService::callApi($apiUrl, 'POST', $offerBody, ["Authorization: Bearer " . $this->mthApiKey]);
    }

    public function prepareOfferBody(Monetha_Gateway_OrderAdapterInterface $order, $orderId)
    {
        $items = [];
        $cartItems = $order->getItems();

        $itemsPrice = 0;
        foreach ($cartItems as $item) {
            /**
             * @var $item Monetha_Gateway_Interceptor
             */
            $price = round($item->getPrice(), 2);
            $quantity = $item->getQtyOrdered();
            $li = [
                'name' => $item->getName(),
                'quantity' => (int)$quantity,
                'amount_fiat' => $price,
            ];
            $itemsPrice += $price * $quantity;
            $items[] = $li;
        }

        $itemsPrice = round($itemsPrice, 2);

        $grandTotal = round($order->getGrandTotalAmount(), 2);

        // Add shipping and taxes
        $shipping = [
            'name' => 'Shipping and taxes',
            'quantity' => 1,
            'amount_fiat' => round($grandTotal - $itemsPrice, 2),
        ];
        $items[] = $shipping;

        $deal = array(
            'deal' => array(
                'amount_fiat' => $grandTotal,
                'currency_fiat' => $order->getCurrencyCode(),
                'line_items' => $items
            ),
            'return_url' => $order->getBaseUrl(),
            'callback_url' => $order->getBaseUrl(),
            'external_order_id' => $orderId . " ",
        );

        return $deal;
    }

    public function executeOffer($token)
    {
        $apiUrl = $this->getApiUrl();
        $apiUrl = $apiUrl . 'v1/deals/execute';

        return Monetha_Gateway_Helper_HttpService::callApi($apiUrl, 'POST', ["token" => $token], []);
    }
}
