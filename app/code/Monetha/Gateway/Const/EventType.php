<?php

class Monetha_Gateway_Const_EventType extends Mage_Core_Helper_Abstract
{
    const CANCELLED = 'order.canceled';
    const FINALIZED = 'order.finalized';
    const MONEY_AUTHORIZED = 'order.money_authorized';
    const PING = 'order.ping';
}
