<?php

namespace AlfredoMeschis\LaravelFedex\Requests;

use AlfredoMeschis\LaravelFedex\Models\Address;

class RateRequest {

    /** @var Address */  
    public $shipperAddress;

    /** @var Address */  
    public $shipToAddress;

    /** @var Address */  
    public $shipFromAddress;

    public $serviceCode;

    public $serviceType;

    public $packageCount;

    public $unitOfMeasurementWeight;
    public $weight;
    public $totalWeight;

    public $unitOfMeasurementDimention;
    public $length;
    public $width;
    public $height;

    public function __construct() 
    {
        $this->shipperAddress = new Address;
        $this->shipToAddress = new Address;
        $this->shipFromAddress = new Address;
    }
}