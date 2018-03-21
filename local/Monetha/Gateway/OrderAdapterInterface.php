<?php

interface Monetha_Gateway_OrderAdapterInterface {
    public function getItems();

    public function getGrandTotalAmount();

    public function getCurrencyCode();

    public function getBaseUrl();
}
