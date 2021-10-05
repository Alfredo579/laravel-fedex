<?php

namespace AlfredoMeschis\LaravelFedex\Requests;

use AlfredoMeschis\LaravelFedex\Models\Address;

class ShippingRequest
{
    public $dropoffType;
    public $serviceType;
    public $packagingType;
    public $fedexDropoffType;
    public $fedexPackagingType;
    public $upsServiceCode;
    public $upsServiceDescription;
    public $upsPackageDescription;
    public $upsPackagingCode;
    public $fedexServiceType;
    public $upsShipmentRequestDescription;
    public $upsShipperNumber;
    public $dhlDropoffType;
    public $dhlServiceType;
    public $dhlAccountNumber;
    public $pdfFormat;
    public $portType;
    public $note;
    public $shipperPersonName;
    public $shipperCompanyName;
    public $shipperPhoneNumber;
    public $shipperEmail;
    public $shipperAttentionName;
    public $shipperTaxIdentificationNumber;
    public $shipperAddress;
    public $shipperAddressStreetLines;
    public $shipperAddressCity;
    public $shipperAddressStateProvinceCode;
    public $shipperAddressPostalCode;
    public $shipperAddressCountryCode;

    public $recipientPersonName;
    public $recipientCompanyName;
    public $recipientPhoneNumber;
    public $recipientEmail;
    public $recipientAttentionName;
    public $recipientTaxIdentificationNumber;
    public $recipientAddressStreetLines;
    public $recipientAddressCity;
    public $recipientAddressStateProvinceCode;
    public $recipientAddressPostalCode;
    public $recipientAddressCountryCode;
    
    public $shipFromName;
    public $shipFromAttentionName;
    public $shipFromPhoneNumber;
    public $shipFromTaxIdentificationNumber;
    public $shipFromAddressLine;
    public $shipFromCity;
    public $shipFromStateProvinceCode;
    public $shipFromPostalCode;
    public $shipFromCountryCode;
    public $labelFormatType;
    public $imageType;
    public $labelStockType;
    public $packageCount;
    public $sequenceNumber;
    public $groupPackageCount;
    public $weightValue;
    public $weightUnits;
    public $dimensionsLength;
    public $dimensionsWidth;
    public $dimensionsHeight;
    public $dimensionsUnits;

    public $cashOnDeliveryValue;

    public $packages = [];

    public function __construct() 
    {
        $this->shipperAddress = new Address;
        $this->shipToAddress = new Address;
        $this->shipFromAddress = new Address;
    }
}