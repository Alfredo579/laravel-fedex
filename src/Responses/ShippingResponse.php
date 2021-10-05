<?php

namespace AlfredoMeschis\LaravelFedex\Responses;

class ShippingResponse {

    public $trackNumber;

    /** @var */

    public $labels = [];

    public $detailPrice;

    public $totalPrice;

    public $labelPath = [];
}