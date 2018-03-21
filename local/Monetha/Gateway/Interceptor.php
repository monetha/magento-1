<?php

interface Monetha_Gateway_Interceptor {
    public function getPrice();

    public function getName();

    public function getQtyOrdered();
}
