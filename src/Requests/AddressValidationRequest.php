<?php

namespace AlfredoMeschis\LaravelFedex\Requests;

use AlfredoMeschis\LaravelFedex\Models\Address;

class AddressValidationRequest {

    /** @var Address  */
    public $address;

    public function __construct() 
    {
        $this->address = new Address;
    }
}