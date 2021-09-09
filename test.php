<?php

require "vendor/autoload.php";

use AlfredoMeschis\LaravelFedex\Dhl;
use AlfredoMeschis\LaravelFedex\Fedex;
use AlfredoMeschis\LaravelFedex\Gls;
use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Requests\TrackRequest;
use AlfredoMeschis\LaravelFedex\Ups;

$config = [
    'Ups' => [
        'userName' => "scaliagroup2017",
        'password' => "Spedizioni1",
        'accessLicenseNumber' => "2DA3451A0692A2D2",
        'locale' => 'it_IT',
        'shipperNumber' => "V5854W"
    ],
    'Fedex' => [
        'accountNumber' => '801405622',
        'meterNumber' => '100635345',
        'key' => 'LwqzW6viiNbVTzPF',
        'password' => 'lYWFAYdqWqav0HWzLdVKe5n0T'
    ],
    "Gls" => [
        "glsSite" => "PA",
        "glsCode" => "95794",
        "password" => "mnc579",
        "contractCode" => "1179"
    ],
    "Dhl" => [
        "username" => "scaliagrou2IT",
        "password" => "Z#5aZ^7eR@5n",
        "account" => 106067134
    ]
];

$rateRequest = new RateRequest;
$shippingRequest = new ShippingRequest;
$trackRequest = new TrackRequest;

$ups = new Ups($config['Ups']);
$fedex = new Fedex($config['Fedex']); 
$gls = new Gls($config['Gls']);
$dhl = new Dhl($config['Dhl']);

$rateRequest->shipperAddress->addressLine = "Via Maresciallo Caviglia 10";
$rateRequest->shipperAddress->city = "Palermo";
$rateRequest->shipperAddress->stateProvinceCode = "PA";
$rateRequest->shipperAddress->postalCode = "90143";
$rateRequest->shipperAddress->countryCode = "IT";

$rateRequest->shipToAddress->addressLine = "Via Goffredo Mameli 164";
$rateRequest->shipToAddress->city = "San Clemente Di Leonessa";
$rateRequest->shipToAddress->stateProvinceCode = "RI";
$rateRequest->shipToAddress->postalCode = "02010";
$rateRequest->shipToAddress->countryCode = "IT";

$rateRequest->shipFromAddress->addressLine = "Via delle Mura Gianicolensi 93";
$rateRequest->shipFromAddress->city = "Cascano";
$rateRequest->shipFromAddress->stateProvinceCode = "CE";
$rateRequest->shipFromAddress->postalCode = "81037";
$rateRequest->shipFromAddress->countryCode = "IT";

$rateRequest->serviceCode = "03";
$rateRequest->serviceType = "INTERNATIONAL_ECONOMY";
$rateRequest->packageCount = "1";

$rateRequest->unitOfMeasurementWeight = "KGS";
$rateRequest->weight = "1";

$rateRequest->totalWeight = "1";

$rateRequest->unitOfMeasurementDimention = "CM";
$rateRequest->length = "5";
$rateRequest->width = "5";
$rateRequest->height = "5";

/* fedex */
$shippingRequest->fedexDropoffType = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, DROP_BOX, BUSINESS_SERVICE_CENTER and 
$shippingRequest->fedexServiceType = 'PRIORITY_OVERNIGHT';// valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
$shippingRequest->fedexPackagingType = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...

/* ups */
$shippingRequest->upsServiceCode = "65";
$shippingRequest->upsServiceDescription = "Expedited";
$shippingRequest->upsPackageDescription = "International Goods";
$shippingRequest->upsPackagingCode = "00";
$shippingRequest->upsShipmentRequestDescription = "1206 PTR";
$shippingRequest->upsShipperNumber = "V5854W";

/* dhl */
$shippingRequest->dhlDropoffType = 'REGULAR_PICKUP';
$shippingRequest->dhlServiceType = 'P';
/* $shippingRequest->dhlAccountNumber = 106067134; */

/* gls */
$shippingRequest->pdfFormat = "A5";
$shippingRequest->portType = "F";
$shippingRequest->note = "es: T: 3475161912";

$shippingRequest->shipperPersonName = 'Pino';
$shippingRequest->shipperCompanyName = 'Scalia Group';
$shippingRequest->shipperPhoneNumber = '400-2345-3489';
$shippingRequest->shipperEmail = 'jb@acme.com';
$shippingRequest->shipperAttentionName = "AttentionName";
$shippingRequest->shipperTaxIdentificationNumber = "TaxID";

$shippingRequest->shipperAddressStreetLines = "via maresciallo caviglia 10";
$shippingRequest->shipperAddressCity = "Palermo";
$shippingRequest->shipperAddressStateOrProvinceCode = "PA";
$shippingRequest->shipperAddressPostalCode = "90143";
$shippingRequest->shipperAddressCountryCode = "IT";

$shippingRequest->recipientPersonName = 'Liana';
$shippingRequest->recipientCompanyName = 'Erida';
$shippingRequest->recipientPhoneNumber = '1234567890';
$shippingRequest->recipientEmail = 'jackie.chan@eei.com';
$shippingRequest->recipientAttentionName = "AttentionName";
$shippingRequest->recipientTaxIdentificationNumber = "456999";

$shippingRequest->recipientAddressStreetLines = '500 Hunt Valley Road';
$shippingRequest->recipientAddressCity = 'New Kensington PA';
$shippingRequest->recipientAddressStateOrProvinceCode = "PA";
$shippingRequest->recipientAddressPostalCode = '15068';
$shippingRequest->recipientAddressCountryCode = 'US';

$shippingRequest->shipFromName = "ShipperName";
$shippingRequest->shipFromAttentionName = "AttentionName";
$shippingRequest->shipFromPhoneNumber = "1234567890";
$shippingRequest->shipFromTaxIdentificationNumber = "456999";
$shippingRequest->shipFromAddressLine = "via roma 6";
$shippingRequest->shipFromCity = "Palermo";
$shippingRequest->shipFromStateProvinceCode = "PA";
$shippingRequest->shipFromPostalCode = "90124";
$shippingRequest->shipFromCountryCode = "IT";

$shippingRequest->labelFormatType = 'COMMON2D';
$shippingRequest->imageType = 'PNG';
$shippingRequest->labelStockType = 'PAPER_7X4.75';

$shippingRequest->packages = [
    [
        "weightValue" => "10",
        "dimensionsLength" => 5,
        "dimensionsWidth" => 5,
        "dimensionsHeight" => 5,
        "upsPackageDescription" => "International Goods",
        "upsPackagingCode" => "00",
        "weightUnits" => 'KGS',
    ],
    [
        "weightValue" => "15",
        "dimensionsLength" => 5,
        "dimensionsWidth" => 5,
        "dimensionsHeight" => 5,
        "upsPackageDescription" => "International Goods",
        "upsPackagingCode" => "00",
        "weightUnits" => 'KGS',
    ]
];

$shippingRequest->packageCount = 1;

$shippingRequest->sequenceNumber = 1;
$shippingRequest->groupPackageCount = 1;
$shippingRequest->weightValue = "10";
$shippingRequest->weightUnits = 'KGS';

$shippingRequest->dimensionsLength = 5;
$shippingRequest->dimensionsWidth = 5;
$shippingRequest->dimensionsHeight = 5;
$shippingRequest->dimensionsUnits = 'CM';

/* test to trackRequest 
fedex trackNumber = '774613744771'
ups trackNumber = '1ZX00F226894705880'
gls trackNumber = "610598168"
dhl trackNumber = 9356579890
*/

$trackRequest->trackNumber = '774613744771';

dump($ups->shipping($shippingRequest));