<?php

namespace AlfredoMeschis\LaravelFedex;

use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Responses\RateResponse;
use AlfredoMeschis\LaravelFedex\Responses\ShippingResponse;

interface CourrierManagementInterface {

    public function __construct(array $config); 
    
    /*   public function addressValidation($usr, $psw, $address);
    public function rate(RateRequest $rateRequest);
    public function shipping(ShippingRequest $shippingRequest): ShippingResponse;
    public function track($inquiryNumber): TrackResponse; */
}