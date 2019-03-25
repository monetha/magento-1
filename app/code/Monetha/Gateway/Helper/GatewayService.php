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
        if(isset($response->integration_secret))
        {
            return $response->integration_secret == $this->merchantSecret;
        }
        return false;
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
            if($price > 0)
            {
                $items[] = $li;
            }
        }

        $itemsPrice = round($itemsPrice, 2);

        $grandTotal = round($order->getGrandTotalAmount(), 2);

        // Add shipping and taxes
        $shipping = [
            'name' => 'Shipping and taxes',
            'quantity' => 1,
            'amount_fiat' => round($grandTotal - $itemsPrice, 2),
        ];

        if($shipping['amount_fiat'] > 0)
        {
            $items[] = $shipping;
        }

        $client_id = 0;
        $order_data = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $billing_address = $order_data->getBillingAddress();


        if($billing_address->getTelephone()) {
            $client_body = array(
                "contact_name" => $billing_address->getFirstname() . ' ' . $billing_address->getLastname(),
                "contact_email" => $billing_address->getEmail(),
                "contact_phone_number" => $billing_address->getTelephone(),
                "country_code_iso" => $billing_address->getCountryId(),
                "zipcode" => $billing_address->getPostcode(),
                "address" => $billing_address->getStreet()[0],
                "city" => $billing_address->getCity()

            );

            $apiUrl = $this->getApiUrl();
            $apiUrl = $apiUrl . 'v1/clients';

            $resJson = Monetha_Gateway_Helper_HttpService::callApi($apiUrl, 'POST', $client_body, ["Authorization: Bearer " . $this->mthApiKey]);

            if(isset($resJson->client_id)) {
                $client_id = $resJson->client_id;
            }
        }
        $deal = array(
            'deal' => array(
                'amount_fiat' => $grandTotal,
                'currency_fiat' => $order->getCurrencyCode(),
                'line_items' => $items,
                'client_id' => $client_id
            ),
            'return_url' => $order->getBaseUrl(),
            'callback_url' => $order->getBaseUrl() . "monetha/callback/handle",
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

    public function processAction($data)
    {

        $order = Mage::getModel('sales/order')->loadByIncrementId($data->payload->external_order_id);

        switch ($data->resource) {
            case Monetha_Gateway_Const_Resource::ORDER:
                switch ($data->event) {
                    case Monetha_Gateway_Const_EventType::CANCELLED:
                        $this->cancelOrderInvoice($order);
                        $order->cancel();
                        $order->save();
                        $this->addOrderComment($order, $data->payload->note);
                        break;
                    case Monetha_Gateway_Const_EventType::FINALIZED:
                        $this->setInvoicePaid($order);
                        $this->addOrderComment($order, 'Order has been successfully paid.');
                        break;
                    case Monetha_Gateway_Const_EventType::MONEY_AUTHORIZED:
                        $this->setInvoicePaid($order);
                        $this->addOrderComment($order, 'Order has been successfully paid by card.');
                        break;
                    default:
                        return 'Bad event type!';
                        break;
                }
                break;

            default:
            return 'Bad resource type!';
            break;
        }
    }

    public function validateSignature($signature, $data)
    {
        return $signature == base64_encode(hash_hmac('sha256', $data, $this->merchantSecret, true));
    }

    public function setInvoicePaid($order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoice->pay();
            $invoice->save();
        }
    }

    public function cancelOrderInvoice($order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->canCancel()) {
                $invoice->cancel();
                $invoice->save();
                $order->save();
            }
        }
    }

    public function addOrderComment($order, $comment)
    {
        if (!empty($comment)) {
            $order->addStatusHistoryComment($comment);
            $order->save();
        }
    }
}
