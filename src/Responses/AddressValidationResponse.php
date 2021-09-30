<?php

namespace AlfredoMeschis\LaravelFedex\Responses;

use AlfredoMeschis\LaravelFedex\Models\Address;

class AddressValidationResponse {

    public $addressExist;
    public $address;
    public $addresses;

    public function __construct() 
    {
        $this->address = new Address;
        $this->addresses = [];
    }
}