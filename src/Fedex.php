<?php

namespace AlfredoMeschis\LaravelFedex;

use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Requests\TrackRequest;
use AlfredoMeschis\LaravelFedex\Responses\RateResponse;
use AlfredoMeschis\LaravelFedex\Responses\ShippingResponse;
use AlfredoMeschis\LaravelFedex\Responses\TrackResponse;
use SoapClient;
class Fedex 
{
    private $accountNumber;
    private $meterNumber;
    private $key;
    private $password;
    private $labelFormatType;
    private $imageType;
    private $labelStockType;
    public $dropOffTypes = [
        'REGULAR_PICKUP' => 'REGULAR_PICKUP', 
        'REQUEST_COURIER' => 'REQUEST_COURIER',
        'DROP_BOX' => 'DROP_BOX'
    ];

    public $serviceTypes = [
        'STANDARD_OVERNIGHT' => 'STANDARD_OVERNIGHT',
        'PRIORITY_OVERNIGHT' => 'PRIORITY_OVERNIGHT',
        'FEDEX_GROUND' => 'FEDEX_GROUND',
    ];

    public function __construct(array $config)
    {
        $this->accountNumber = $config['accountNumber'];
        $this->meterNumber = $config['meterNumber'];
        $this->key = $config['key'];
        $this->password = $config['password'];
        $this->labelFormatType = $config['labelFormatType'];
        $this->imageType = $config['imageType'];
        $this->labelStockType = $config['labelStockType'];
    }

    public function getServicesTypes() {

        return ["serviceType" => $this->serviceTypes,"dropOffType" => $this->dropOffTypes];
    }

    public function addressValidation()
    {
        $client = new SoapClient('CountryService_v8.wsdl');

        $response = $client->validatePostal([
            "ClientDetail" => [
                "AccountNumber" => $this->accountNumber,
                "MeterNumber" => $this->meterNumber,
            ],
            "Version" => [
                "ServiceId" => 'cnty',
                "Minor" => '0',
                "Major" => '8',
                "Intermediate" => '0',
            ],
            "Address" => [
                "PostalCode" => '90143',
                "CountryCode" => 'IT',
                "residencial" => '1'
            ],
            "WebAuthenticationDetail" => [
                "UserCredential" => [
                    "Key" => $this->key,
                    "Password" => $this->password
                ],
            ],
            "CarrierCode" => 'FDXG',
        ]);

        var_dump($response);
    }

    public function rate(RateRequest $rateRequest): RateResponse
    {
        $client = new SoapClient('RateService_v28.wsdl');
        $response = $client->getRates([

            "WebAuthenticationDetail" => [
                "ParentCredential" => [
                    "Key" => "",
                    "Password" => ""
                ],
                "UserCredential" => [
                    "Key" => $this->key,
                    "Password" => $this->password
                ],
            ],
            "ClientDetail" => [
                "AccountNumber" => $this->accountNumber,
                "MeterNumber" => $this->meterNumber,
            ],
            "TransactionDetail" => [
                "CustomerTransactionId" => '*** Rate Available Services Request using PHP ***',
                "Localization" => [
                    "LanguageCode" => "IT",
                    "LocalCode" => "IT"
                ]
            ],
            "ReturnTransitAndCommit" => true,
            "RequestedShipment" => [
                "DropoffType" => "REGULAR_PICKUP",
                "ShipTimestamp" => date('c'),
                "ServiceType" => 'PRIORITY_OVERNIGHT',
                "Shipper" => [
                    "Address" => [
                        "CountryCode" => 'IT',
                        "PostalCode" => '90143'
                    ]
                ],
                "Recipient" => [
                    "Address" => [
                        "CountryCode" => 'IT',
                        "PostalCode" => '90124'
                    ]
                ],
                "ShippingChargesPayment" => [
                    "PaymentType" => "SENDER",
                    "Payor" => [
                        "AccountNumber" => "billaccount",
                        "Contact" => null,
                        "Address" => [
                            "CountryCode" => 'IT'
                        ]
                    ]
                ],
                "PackageCount" => 1,
                "RequestedPackageLineItems" => [
                    "0" => [
                        'SequenceNumber' => 1,
                        'GroupPackageCount' => 1,
                        'Weight' => [
                            'Value' => '0,5',
                            'Units' => "KG"
                        ],
                        'Dimensions' => [
                            'Length' => '5',
                            'Width' => '5',
                            'Height' => '5',
                            'Units' => "CM"
                        ]
                    ],
                ]
            ],
            "RateRequestType" => "LIST",
            "Version" => [
                "ServiceId" => "crs",
                "Minor" => '0',
                "Major" => "28",
                "Intermediate" => '0',
            ],
        ]);

        var_dump($response);

        $rateResponse = new RateResponse;

        $rateResponse->expectedDateDelivery = $response->RateReplyDetails->DeliveryTimestamp;

        $rateResponse->totalPrice = [
            "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Currency,
            "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Amount
        ];

        $rateResponse->detailPrice = [
            [
                "name" => "TotalBaseCharge",
                "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalBaseCharge->Currency,
                "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalBaseCharge->Amount
            ],
            [
                "name" => "TotalNetFreight",
                "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetFreight->Currency,
                "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalNetFreight->Amount
            ],
            [
                "name" => "TotalDutiesAndTaxes",
                "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalDutiesAndTaxes->Currency,
                "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalDutiesAndTaxes->Amount
            ],
            [
                "name" => "TotalAncillaryFeesAndTaxes",
                "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalAncillaryFeesAndTaxes->Currency,
                "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalAncillaryFeesAndTaxes->Amount
            ],
            [
                "name" => "TotalDutiesTaxesAndFees",
                "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalDutiesTaxesAndFees->Currency,
                "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalDutiesTaxesAndFees->Amount
            ],
            [
                "name" => "Taxes",
                "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->Taxes->Amount->Currency,
                "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->Taxes->Amount->Amount
            ],
            [
                "name" => "TotalFreightDiscounts",
                "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalFreightDiscounts->Currency,
                "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalFreightDiscounts->Amount
            ],
            [
                "name" => "TotalRebates",
                "currency" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalRebates->Currency,
                "value" => $response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->TotalRebates->Amount
            ],
        ];

        foreach ($response->RateReplyDetails->RatedShipmentDetails->ShipmentRateDetail->Surcharges as $surcharge) {

            $rateResponse->detailPrice[] = [
                "name" => $surcharge->SurchargeType,
                "currency" => $surcharge->Amount->Currency,
                "value" => $surcharge->Amount->Amount
            ];
        }

        var_dump($response);

        return $rateResponse;
    }

    public function shipping(ShippingRequest $shippingRequest): ShippingResponse
    {
        $path_to_wsdl = "ShipService_v26.wsdl";

        $client = new SoapClient($path_to_wsdl);

        $response = $client->processShipment([

            'WebAuthenticationDetail' => [
                "UserCredential" => [
                    "Key" => $this->key,
                    "Password" => $this->password
                ],
            ],
            'ClientDetail' => [
                "AccountNumber" => '510087780',
                "MeterNumber" => '100702249',
            ],
            'Version' => [
                'ServiceId' => 'ship',
                'Major' => '26',
                'Intermediate' => '0',
                'Minor' => '0'
            ],
            'RequestedShipment' => [
                'ShipTimestamp' =>  date('c'),
                'DropoffType' => $shippingRequest->dropoffType,
                "ShippingChargesPayment" => [
                    "PaymentType" => "SENDER",
                    "Payor" => [
                        "ResponsibleParty" => [
                            "AccountNumber" => "510087780",
                            "CountryCode" => "US"
                        ]
                    ]
                ],
                'ServiceType' => $shippingRequest->serviceType, 
                'PackagingType' => $shippingRequest->fedexPackagingType, 
                'Shipper' => [
                    'Contact' => [
                        'PersonName' => $shippingRequest->shipperPersonName,
                        'CompanyName' => $shippingRequest->shipperCompanyName,
                        'PhoneNumber' => $shippingRequest->shipperPhoneNumber
                    ],
                    'Address' => [
                        'StreetLines' => [$shippingRequest->shipperAddressStreetLines],
                        'City' => $shippingRequest->shipperAddressCity,
                        'StateOrProvinceCode' => $shippingRequest->shipperAddressStateProvinceCode,
                        'PostalCode' => $shippingRequest->shipperAddressPostalCode,
                        'CountryCode' => $shippingRequest->shipperAddressCountryCode, 
                    ]
                ],
                'Recipient' => [
                    'Contact' => [
                        'PersonName' => $shippingRequest->recipientPersonName,
                        'CompanyName' => $shippingRequest->recipientCompanyName,
                        'PhoneNumber' => $shippingRequest->recipientPhoneNumber
                    ],
                    'Address' => [
                        'StreetLines' => [$shippingRequest->recipientAddressStreetLines],
                        'City' => $shippingRequest->recipientAddressCity,
                        'StateOrProvinceCode' => $shippingRequest->recipientAddressStateProvinceCode, // for Example TX
                        'PostalCode' => $shippingRequest->recipientAddressPostalCode,
                        'CountryCode' => $shippingRequest->recipientAddressCountryCode, // For example US
                        'Residential' => true
                    ]
                ],
                'LabelSpecification' => [
                    'LabelFormatType' => $this->labelFormatType, // valid values COMMON2D, LABEL_DATA_ONLY
                    'ImageType' => $this->imageType,  // valid values DPL, EPL2, PDF, ZPLII and PNG
                    'LabelStockType' => $this->labelStockType
                ],
                'PackageCount' => $shippingRequest->packageCount,
                'RequestedPackageLineItems' => [
                    '0' => [
                        /* 'SequenceNumber' => $shippingRequest->sequenceNumber, */
                        /* 'GroupPackageCount' => $shippingRequest->groupPackageCount, */
                        'Weight' => [
                            'Value' => $shippingRequest->weightValue,
                            'Units' => $shippingRequest->weightUnits
                        ],
                        'Dimensions' => [
                            'Length' => $shippingRequest->dimensionsLength,
                            'Width' => $shippingRequest->dimensionsWidth,
                            'Height' => $shippingRequest->dimensionsHeight,
                            'Units' => $shippingRequest->dimensionsUnits
                        ]
                    ],
                ]
            ],
        ]);

        var_dump($response);

        $shippingResponse = new ShippingResponse;
        $shippingResponse->trackNumber = $response->CompletedShipmentDetail->MasterTrackingId->TrackingNumber;
        $shippingResponse->labels = [
            "format" => $response->CompletedShipmentDetail->CompletedPackageDetails->Label->ImageType,
            "image" => $response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image
        ];
        return $shippingResponse;
    }

    public function track(TrackRequest $trackRequest): TrackResponse
    {
        $client = new SoapClient('TrackService_v19.wsdl');
        $response = $client->track([
            "ClientDetail" => [
                "AccountNumber" => $this->accountNumber,
                "MeterNumber" => $this->meterNumber,
            ],
            "Version" => [
                "ServiceId" => 'trck',
                "Minor" => '0',
                "Major" => '19',
                "Intermediate" => '0',
            ],
            "WebAuthenticationDetail" => [
                "UserCredential" => [
                    "Key" => $this->key,
                    "Password" => $this->password
                ],
            ],
            "SelectionDetails" => [
                "ShipmentAccountNumber" => '510088000',
                "PackageIdentifier" => [
                    "Type" => "TRACKING_NUMBER_OR_DOORTAG",
                    "Value" => $trackRequest->trackNumber
                ]
            ],
            "ProcessingOptions" => "INCLUDE_DETAILED_SCANS",
            "TransactionDetail" => [
                "Localization" => [
                    "LanguageCode" => "IT"
                ]
            ]
        ]);

        $trackResponse = new TrackResponse;

        foreach ($response->CompletedTrackDetails->TrackDetails->Events as $activityItem) {
            $trackResponse->setHistory(
                $activityItem->Address->City ?? '',
                $activityItem->EventDescription,
                $activityItem->Timestamp
            );
        }
        $trackResponse->setState();

        return $trackResponse;
    }
   
    public function labelRecovery()
    {
        $path_to_wsdl = "OpenshipService_v18.wsdl";

        $client = new SoapClient($path_to_wsdl);

        $response = $client->reprintShippingDocuments([

            'WebAuthenticationDetail' => [
                "UserCredential" => [
                    "Key" => $this->key,
                    "Password" => $this->password
                ],
            ],
            'ClientDetail' => [
                "AccountNumber" => $this->accountNumber,
                "MeterNumber" => $this->meterNumber,
            ],
            'Version' => [
                'ServiceId' => 'ship',
                'Major' => '18',
                'Intermediate' => '0',
                'Minor' => '0'
            ],
        ]);

        var_dump($response);
    }
}