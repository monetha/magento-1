<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-29
 * Time: 09:03
 */

namespace Monetha\Adapter\MG1;


use Monetha\Adapter\ClientAdapterInterface;

class ClientAdapter implements ClientAdapterInterface
{
    private $zipCode;

    private $countryIsoCode;

    private $contactPhoneNumber;

    private $contactName;

    private $contactEmail;

    private $city;

    private $address;

    public function __construct($billing_address)
    {
        $this->zipCode = $billing_address->getPostcode();
        $this->countryIsoCode = $billing_address->getCountryId();
        $this->contactPhoneNumber = preg_replace('/\D/', '', $billing_address->getTelephone());
        $this->contactName = $billing_address->getFirstname() . ' ' . $billing_address->getLastname();
        $this->contactEmail = $billing_address->getEmail();
        $this->city = $billing_address->getCity();
        $this->address = reset($billing_address->getStreet());
    }

    public function getZipCode()
    {
        return $this->zipCode;
    }

    public function getCountryIsoCode()
    {
        return $this->countryIsoCode;
    }

    public function getContactPhoneNumber()
    {
        return $this->contactPhoneNumber;
    }

    public function getContactName()
    {
        return $this->contactName;
    }

    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getAddress()
    {
        return $this->address;
    }
}