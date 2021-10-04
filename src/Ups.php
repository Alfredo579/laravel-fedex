<?php

namespace AlfredoMeschis\LaravelFedex;

use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Requests\TrackRequest;
use AlfredoMeschis\LaravelFedex\Responses\RateResponse;
use AlfredoMeschis\LaravelFedex\Responses\ShippingResponse;
use AlfredoMeschis\LaravelFedex\Responses\TrackResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Ups implements CourrierManagementInterface
{
    private $userName;
    private $password;
    private $accessLicenseNumber;
    private $accountNumber;
    private $locale;
    private $shipperNumber;
    private $imageType;
    private $chargeCodes = [
        "100" => "ADDITIONAL HANDLING",
        "110" => "COD",
        "120" => "DELIVERY CONFIRMATION",
        "121" => "SHIP DELIVERY CONFIRMATION",
        "190" => "EXTENDED AREA",
        "199" => "HAZ MAT",
        "200" => "DRY ICE",
        "201" => "ISC SEEDS",
        "202" => "ISC PERISHABLES",
        "203" => "ISC TOBACCO",
        "204" => "ISC PLANTS",
        "205" => "ISC ALCOHOLIC BEVERAGES",
        "206" => "ISC BIOLOGICAL SUBSTANCES",
        "207" => "ISC SPECIAL EXCEPTIONS",
        "220" => "HOLD FOR PICKUP",
        "240" => "ORIGIN CERTIFICATE",
        "250" => "PRINT RETURN LABEL",
        "258" => "EXPORT LICENSE VERIFICATION",
        "260" => "PRINT N MAIL",
        "270" => "RESIDENTIAL ADDRESS",
        "280" => "RETURN SERVICE 1ATTEMPT",
        "290" => "RETURN SERVICE 3ATTEMPT",
        "300" => "SATURDAY DELIVERY",
        "310" => "SATURDAY INTERNATIONAL PROCESSING FEE",
        "350" => "ELECTRONIC RETURN LABEL",
        "374" => "UPS PREPARED SED FORM",
        "375" => "FUEL SURCHARGE",
        "376" => "DELIVERY AREA",
        "377" => "LARGE PACKAGE",
        "378" => "SHIPPER PAYS DUTY TAX",
        "379" => "SHIPPER PAYS DUTY TAX UNPAID",
        "380" => "EXPRESS PLUS SURCHARGE",
        "400" => "INSURANCE",
        "401" => "SHIP ADDITIONAL HANDLING",
        "402" => "SHIPPER RELEASE",
        "403" => "CHECK TO SHIPPER",
        "404" => "UPS PROACTIVE RESPONSE",
        "405" => "GERMAN PICKUP",
        "406" => "GERMAN ROAD TAX",
        "407" => "EXTENDED AREA PICKUP",
        "410" => "RETURN OF DOCUMENT",
        "430" => "PEAK SEASON",
        "431" => "LARGE PACKAGE SEASONAL SURCHARGE",
        "432" => "ADDITIONAL HANDLING SEASONAL SURCHARGE",
        "440" => "SHIP LARGE PACKAGE",
        "441" => "CARBON NEUTRAL",
        "444" => "IMPORT CONTROL",
        "445" => "COMMERCIAL INVOICE REMOVAL",
        "446" => "IMPORT CONTROL ELECTRONIC LABEL",
        "447" => "IMPORT CONTROL PRINT LABEL",
        "448" => "IMPORT CONTROL PRINT AND MAIL LABEL",
        "449" => "IMPORT CONTROL ONE PICK UP ATTEMPT LABEL",
        "450" => "IMPORT CONTROL THREE PICK UP ATTEMPT LABEL",
        "452" => "REFRIGERATION",
        "464" => "EXCHANGE PRINT RETURN LABEL",
        "470" => "COMMITTED DELIVERY WINDOW",
        "480" => "SECURITY SURCHARGE",
        "492" => "CUSTOMER TRANSACTION FEE",
        "500" => "SHIPMENT COD",
        "510" => "LIFT GATE FOR PICKUP",
        "511" => "LIFT GATE FOR DELIVERY",
        "512" => "DROP OFF AT UPS FACILITY",
        "515" => "UPS PREMIUM CARE",
        "520" => "OVERSIZE PALLET",
    ];

    public $upsServiceCodes = [
        '01' => 'Next Day Air',
        '02' => '2nd Day Air',
        '03' => 'Ground',
        '07' => 'Express',
        '08' => 'Expedited',
        '11' => 'UPS Standard',
        '12' => '3 Day Select',
        '13' => 'Next Day Air Saver',
        '14' => 'UPS Next Day Air® Early',
        '17' => 'UPS Worldwide Economy DDU',
        '54' => 'Express Plus',
        '59' => '2nd Day Air A.M.',
        '65' => 'UPS Saver',
        'M2' => 'First Class Mail',
        'M3' => 'Priority Mail',
        'M4' => 'Expedited MaiI Innovations',
        'M5' => 'Priority Mail Innovations',
        'M6' => 'Economy Mail Innovations',
        'M7' => 'MaiI Innovations (MI) Returns',
        '70' => 'UPS Access PointTM Economy',
        '71' => 'UPS Worldwide Express Freight Midday',
        '72' => 'UPS Worldwide Economy',
        '74' => 'UPS Express®12:00',
        '82' => 'UPS Today Standard',
        '83' => 'UPS Today Dedicated Courier',
        '84' => 'UPS Today Intercity',
        '85' => 'UPS Today Express',
        '86' => 'UPS Today Express Saver',
        '96' => 'UPS Worldwide Express Freight.'
    ];

    public $serviceTypes = [
        '01' => 'Next Day Air',
        '02' => '2nd Day Air',
        '03' => 'Ground',
        '07' => 'Express',
        '08' => 'Expedited',
        '11' => 'UPS Standard',
        '12' => '3 Day Select',
        '13' => 'Next Day Air Saver',
        '14' => 'UPS Next Day Air® Early',
        '17' => 'UPS Worldwide Economy DDU',
        '54' => 'Express Plus',
        '59' => '2nd Day Air A.M.',
        '65' => 'UPS Saver',
        'M2' => 'First Class Mail',
        'M3' => 'Priority Mail',
        'M4' => 'Expedited MaiI Innovations',
        'M5' => 'Priority Mail Innovations',
        'M6' => 'Economy Mail Innovations',
        'M7' => 'MaiI Innovations (MI) Returns',
        '70' => 'UPS Access PointTM Economy',
        '71' => 'UPS Worldwide Express Freight Midday',
        '72' => 'UPS Worldwide Economy',
        '74' => 'UPS Express®12:00',
        '82' => 'UPS Today Standard',
        '83' => 'UPS Today Dedicated Courier',
        '84' => 'UPS Today Intercity',
        '85' => 'UPS Today Express',
        '86' => 'UPS Today Express Saver',
        '96' => 'UPS Worldwide Express Freight.'
    ];

    public $packagingCodes = [
        "00" => "Unknown",
        "01" => "UPS Letter",
        "02" => "Customer Supplied Package",
        "03" => "Tube",
        "04" => "PAK",
        "21" => "UPS Express Box",
        "24" => "UPS 25KG Box",
        "25" => "UPS 10KG Box",
        "30" => "Pallet",
        "2a" => "Small Express Box",
        "2b" => "Medium Express Box",
        "2c" => "Large Express Box",
        "56" => "Flats",
        "57" => "Parcels",
        "58" => "BPM",
        "59" => "First Class",
        "60" => "Priority",
        "61" => "Machineables",
        "62" => "Irregulars",
        "63" => "Parcel Post",
        "64" => "BPM Parcel",
        "65" => "Media Mail",
        "66" => "BPM Flat",
        "67" => "Standard Flat",
    ];

    public function __construct(array $config)
    {
        $this->userName = $config['userName'];
        $this->password = $config['password'];
        $this->accessLicenseNumber = $config['accessLicenseNumber'];
        $this->accountNumber = $config['accountNumber'];
        $this->locale = $config['locale'];
        $this->shipperNumber = $config['shipperNumber'];
        $this->imageType = $config['imageType'];
    }

    public function getServicesTypes()
    {

        return ["serviceType" => $this->serviceTypes];
    }

    public function addressValidation()
    {
        $url = 'https://wwwcie.ups.com/addressvalidation/v1/3';

        try {

            $client = new Client();
            $response = $client->post($url, [
                "headers" =>  [
                    "Username" => $this->userName,
                    "Password" => $this->password,
                    "AccessLicenseNumber" => $this->accessLicenseNumber,
                    "Content-Type" => "application/json",
                    "Accept" => "application/json"
                ],
                "query" => [
                    "locale" => $this->locale
                ],
                "json" => [
                    "XAVRequest" => [
                        "AddressKeyFormat" => [
                            "ConsigneeName" => "RITZ CAMERA CENTERS-1749",
                            "BuildingName" => "Innoplex",
                            "AddressLine" => [
                                "26601 ALISO CREEK ROAD", "STE D",
                                "ALISO VIEJO TOWN CENTER"
                            ],
                            "Region" => "ROSWELL,GA,30076-1521",
                            "PoliticalDivision2" => "ALISO VIEJO",
                            "PoliticalDivision1" => "CA",
                            "PostcodePrimaryLow" => "92656",
                            "PostcodeExtendedLow" => "1521",
                            "Urbanization" => "porto arundal",
                            "CountryCode" => "US"
                        ]
                    ]
                ]
            ]);

           /*  dump(json_decode($response->getBody()->getContents())); */
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            foreach ($response->response->errors as $error) {
                var_dump($error->message);
            }
        }
    }

    public function rate(RateRequest $rateRequest): RateResponse
    {

        $url = 'https://wwwcie.ups.com/ship/v1801/rating/Rate';

        $totalWeight = 0;

        foreach ($rateRequest->packages as $package) {

            $totalWeight += $package['weight'];

            $packQuery[] =  [
                "PackagingType" => [
                    "Code" => "00",
                    "Description" => "Package"
                ],
                "Dimensions" => [
                    "UnitOfMeasurement" => [
                        "Code" => "CM",
                    ],
                    "Length" =>  $package['length'],
                    "Width" =>  $package['width'],
                    "Height" =>  $package['height']
                ],
                /* "Description" => $package['upsPackageDescription'], */
                "Packaging" => [
                    "Code" => /* $package['upsPackagingCode'] */ '00'
                ],
                "PackageWeight" => [
                    "UnitOfMeasurement" => [
                        "Code" => 'KGS'
                    ],
                    "Weight" => $package['weight']
                ],
            ];
        }

        try {

            $client = new Client();

            $response = $client->post(
                $url,
                [
                    "headers" =>  [
                        "transId" => "12345",
                        "transactionSrc" => "XOLT",
                        "Username" => $this->userName,
                        "Password" => $this->password,
                        "AccessLicenseNumber" => $this->accessLicenseNumber,
                        "Content-Type" => "application/json",
                        "Accept" => "application/json"
                    ],
                    "query" => [
                        "local" => "it_IT"
                    ],
                    "json" => [
                        "RateRequest" => [
                            "Shipment" => [
                                "ShipmentRatingOptions" => [
                                    "UserLevelDiscountIndicator" => "TRUE"
                                ],
                                "Shipper" => [
                                    "Address" => [
                                        "AddressLine" => $rateRequest->shipperAddress->addressLine,
                                        "City" => $rateRequest->shipperAddress->city,
                                        "StateProvinceCode" => $rateRequest->shipperAddress->stateProvinceCode,
                                        "PostalCode" => $rateRequest->shipperAddress->postalCode,
                                        "CountryCode" => $rateRequest->shipperAddress->countryCode
                                    ]
                                ],
                                "ShipTo" => [
                                    "Address" => [
                                        "AddressLine" => $rateRequest->shipToAddress->addressLine,
                                        "City" => $rateRequest->shipToAddress->city,
                                        "StateProvinceCode" => $rateRequest->shipToAddress->stateProvinceCode,
                                        "PostalCode" => $rateRequest->shipToAddress->postalCode,
                                        "CountryCode" => $rateRequest->shipToAddress->countryCode
                                    ]
                                ],
                                /*  "ShipFrom" => [
                                    "Name" => "Billy Blanks",
                                    "Address" => [
                                        "AddressLine" => $rateRequest->shipFromAddress->addressLine,
                                        "City" => $rateRequest->shipFromAddress->city,
                                        "StateProvinceCode" => $rateRequest->shipFromAddress->stateProvinceCode,
                                        "PostalCode" => $rateRequest->shipFromAddress->postalCode,
                                        "CountryCode" => $rateRequest->shipFromAddress->countryCode
                                    ]
                                ], */
                                "Service" => [
                                    "Code" => "03", /* $rateRequest->serviceCode */
                                ],

                                "ShipmentTotalWeight" => [
                                    "UnitOfMeasurement" => [
                                        "Code" => "KGS",
                                    ],
                                    "Weight" => $totalWeight
                                ],
                                "Package" => $packQuery /* [
                                    "PackagingType" => [
                                        "Code" => "00",
                                        "Description" => "Package"
                                    ],
                                    "Dimensions" => [
                                        "UnitOfMeasurement" => [
                                            "Code" => "CM"
                                        ],
                                        "Length" => $rateRequest->length,
                                        "Width" => $rateRequest->width,
                                        "Height" => $rateRequest->height
                                    ],
                                    "PackageWeight" => [
                                        "UnitOfMeasurement" => [
                                            "Code" => "KGS"
                                        ],
                                        "Weight" => $rateRequest->weight
                                    ]
                                ] */
                            ]
                        ]
                    ]
                ]
            );

            $response = json_decode($response->getBody()->getContents());

            $rateResponse = new RateResponse;

            if (is_array($response->RateResponse->RatedShipment->ItemizedCharges)) {

                foreach ($response->RateResponse->RatedShipment->ItemizedCharges as $ItemizedCharge) {

                    $rateResponse->detailPrice[] = [
                        "name" => $this->chargeCodes[$ItemizedCharge->Code] ?? $ItemizedCharge->Code,
                        "currency" => $ItemizedCharge->CurrencyCode,
                        "value" => $ItemizedCharge->MonetaryValue
                    ];
                }
            } else {

                $rateResponse->detailPrice[] = [
                    "name" => "ItemizedCharges",
                    "currency" => $response->RateResponse->RatedShipment->ItemizedCharges->CurrencyCode,
                    "value" => $response->RateResponse->RatedShipment->ItemizedCharges->MonetaryValue
                ];
            }

            $rateResponse->detailPrice[] = [
                "name" => "BaseServiceCharge",
                "currency" => $response->RateResponse->RatedShipment->BaseServiceCharge->CurrencyCode,
                "value" => $response->RateResponse->RatedShipment->BaseServiceCharge->MonetaryValue
            ];

            $rateResponse->totalPrice = [
                "currency" => $response->RateResponse->RatedShipment->TotalCharges->CurrencyCode,
                "value" => $response->RateResponse->RatedShipment->TotalCharges->MonetaryValue
            ];

            return $rateResponse;
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            foreach ($response->response->errors as $error) {

                var_dump($error->message);
            }
        }
    }

    public function track(TrackRequest $trackRequest): TrackResponse
    {
        $url = 'https://onlinetools.ups.com/track/v1/details/' . $trackRequest->trackNumber;

        try {

            $client = new Client();

            $response = $client->get($url, [
                "headers" =>  [
                    "transId" => "12345",
                    "transactionSrc" => "TestTrack",
                    "Username" => $this->userName,
                    "Password" => $this->password,
                    "AccessLicenseNumber" => $this->accessLicenseNumber,
                    "Content-Type" => "application/json",
                    "Accept" => "application/json"
                ],
                "query" => [
                    "locale" => $this->locale
                ]
            ]);

            $resp = json_decode($response->getBody()->getContents());
            var_dump($resp);
            die;

            $trackResponse = new TrackResponse;

            foreach ($resp->trackResponse->shipment[0]->package[0]->activity as $activityItem) {

                $trackResponse->setHistory(
                    $activityItem->location->address->city,
                    $activityItem->status->description,
                    /*need to change Carbon in DataTime when track back to work Carbon::parse($activityItem->date) */
                    '01/01/2021'
                );
            }

            $trackResponse->setState();

            return $trackResponse;
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            foreach ($response->response->errors as $error) {

                var_dump($error->message);
            }
        }
    }

    public function shipping(ShippingRequest $shippingRequest): ShippingResponse
    {
        $version = 'v1807';
        $packQuery = [];

        foreach ($shippingRequest->packages as $package) {

            $packQuery[] =  [
                "Dimensions" => [
                    "UnitOfMeasurement" => [
                        "Code" => "CM",
                    ],
                    "Length" => $package['lengthValue'],
                    "Width" => $package['widthValue'],
                    "Height" => $package['heightValue']
                ],
                /* "Description" => $package['upsPackageDescription'], */
                "Packaging" => [
                    "Code" => /* $package['upsPackagingCode'] */ '00'
                ],
                "PackageWeight" => [
                    "UnitOfMeasurement" => [
                        "Code" => 'KGS'
                    ],
                    "Weight" => $package['weightValue']
                ], /*  "PackageServiceOptions" => [
                    "COD" => [
                        "CODFundsCode" => "0",
                        "CODAmount" => [
                            "CurrencyCode" => "EUR",
                            "MonetaryValue" => "8.90"
                        ]
                    ]
                ]  */
            ];
        }

        $url = 'https://wwwcie.ups.com/ship/' . $version . '/shipments';

        try {

            $client = new Client();

            if (isset($shippingRequest->cashOnDeliveryValue)) {

                $cashOnDelivery = [
                    "COD" => [
                        "CODFundsCode" => "1",
                        "CODAmount" => [
                            "CurrencyCode" => "EUR",
                            "MonetaryValue" => $shippingRequest->cashOnDeliveryValue
                        ]
                    ]
                ];
                
            } else {
                $cashOnDelivery = [];
            }

            $response = $client->post(
                $url,
                [
                    "headers" =>  [
                        "transId" => "12345",
                        "transactionSrc" => "TestTrack",
                        "Username" => $this->userName,
                        "Password" => $this->password,
                        "AccessLicenseNumber" => $this->accessLicenseNumber,
                        "Content-Type" => "application/json",
                        "Accept" => "application/json"
                    ],
                    "query" => [
                        "locale" => $this->locale
                    ],
                    "json" => [
                        "ShipmentRequest" => [
                            "Shipment" => [

                                "Shipper" => [
                                    "Name" => $shippingRequest->shipperPersonName,
                                    "AttentionName" => $shippingRequest->shipperAttentionName,
                                    "TaxIdentificationNumber" => $shippingRequest->shipperTaxIdentificationNumber,
                                    "Phone" => [
                                        "Number" => $shippingRequest->shipperPhoneNumber
                                    ],
                                    "ShipperNumber" => $this->shipperNumber,
                                    "Address" => [
                                        "AddressLine" => $shippingRequest->shipperAddress->addressLine,
                                        "City" => $shippingRequest->shipperAddress->city,
                                        "StateProvinceCode" => $shippingRequest->shipperAddress->stateProvinceCode,
                                        "PostalCode" => $shippingRequest->shipperAddress->postalCode,
                                        "CountryCode" => $shippingRequest->shipperAddress->countryCode
                                    ]
                                ],
                                "ShipTo" => [
                                    "Name" => $shippingRequest->recipientPersonName,
                                    "AttentionName" => $shippingRequest->recipientAttentionName,
                                    "Phone" => [
                                        "Number" => $shippingRequest->recipientPhoneNumber
                                    ],
                                    /*  "TaxIdentificationNumber" => $shippingRequest->recipientTaxIdentificationNumber, */
                                    "Address" => [
                                        "AddressLine" => $shippingRequest->shipToAddress->addressLine,
                                        "City" => $shippingRequest->shipToAddress->city,
                                        "StateProvinceCode" => $shippingRequest->shipToAddress->stateProvinceCode,
                                        "PostalCode" => $shippingRequest->shipToAddress->postalCode,
                                        "CountryCode" => $shippingRequest->shipToAddress->countryCode
                                    ]
                                ],
                                /*  "ShipFrom" => [
                                    "Name" => $shippingRequest->shipFromName,
                                    "AttentionName" => $shippingRequest->shipFromAttentionName,
                                    "Phone" => [
                                        "Number" => $shippingRequest->shipFromPhoneNumber
                                    ],
                                    "FaxNumber" => "1234567999",
                                    "TaxIdentificationNumber" => $shippingRequest->shipFromTaxIdentificationNumber,
                                    "Address" => [
                                        "AddressLine" => $shippingRequest->shipFromAddressLine,
                                        "City" => $shippingRequest->shipFromCity,
                                        "StateProvinceCode" => $shippingRequest->shipFromStateProvinceCode,
                                        "PostalCode" => $shippingRequest->shipFromPostalCode,
                                        "CountryCode" => $shippingRequest->shipFromCountryCode
                                    ]
                                ], */
                                "PaymentInformation" => [
                                    "ShipmentCharge" => [
                                        "Type" => "01",
                                        "BillShipper" => [
                                            "AccountNumber" => $this->accountNumber
                                        ]
                                    ]
                                ],
                                "Service" => [
                                    "Code" => $shippingRequest->serviceType,
                                    "Description" => "Expedited"
                                ],
                                "Package" => $packQuery,
                                /*    "ItemizedChargesRequestedIndicator" => "",
                                "RatingMethodRequestedIndicator" => "",
                                "TaxInformationIndicator" => "", "ShipmentRatingOptions" => [
                                    "NegotiatedRatesIndicator" => ""
                                ] */
                                "ShipmentServiceOptions" => $cashOnDelivery
                            ],
                            "LabelSpecification" => [
                                "LabelImageFormat" => [
                                    "Code" => $this->imageType
                                ]
                            ]
                        ]
                    ]
                ]
            );

            $response = json_decode($response->getBody()->getContents());

            $shippingResponse = new ShippingResponse;

            $shippingResponse->trackNumber = $response->ShipmentResponse->Response->TransactionReference->TransactionIdentifier;

            if (is_array($response->ShipmentResponse->ShipmentResults->ShipmentCharges->ItemizedCharges)) {

                foreach ($response->ShipmentResponse->ShipmentResults->ShipmentCharges->ItemizedCharges as $ItemizedCharge) {

                    $shippingResponse->detailPrice[] = [
                        "name" => $this->chargeCodes[$ItemizedCharge->Code] ?? $ItemizedCharge->Code,
                        "currency" => $ItemizedCharge->CurrencyCode,
                        "value" => $ItemizedCharge->MonetaryValue
                    ];
                }
            } else {

                $shippingResponse->detailPrice[] = [
                    "name" => "ItemizedCharges",
                    "currency" => $response->ShipmentResponse->ShipmentResults->ShipmentCharges->ItemizedCharges->CurrencyCode,
                    "value" => $response->ShipmentResponse->ShipmentResults->ShipmentCharges->ItemizedCharges->MonetaryValue
                ];
            }


            $shippingResponse->detailPrice[] = [
                "name" => "BaseServiceCharge",
                "currency" => $response->ShipmentResponse->ShipmentResults->ShipmentCharges->BaseServiceCharge->CurrencyCode,
                "value" => $response->ShipmentResponse->ShipmentResults->ShipmentCharges->BaseServiceCharge->MonetaryValue
            ];

            $shippingResponse->totalPrice = [
                "currency" => $response->ShipmentResponse->ShipmentResults->ShipmentCharges->TotalCharges->CurrencyCode,
                "value" => $response->ShipmentResponse->ShipmentResults->ShipmentCharges->TotalCharges->MonetaryValue
            ];

            $count = 1;
            if (is_array($response->ShipmentResponse->ShipmentResults->PackageResults)) {


                foreach ($response->ShipmentResponse->ShipmentResults->PackageResults as $pack) {

                    $shippingResponse->labels[] = [
                        "format" => $pack->ShippingLabel->ImageFormat->Code,
                        "image" => $pack->ShippingLabel->GraphicImage
                    ];

                    file_put_contents("upsLabel" . $count . ".png", base64_decode($pack->ShippingLabel->GraphicImage));

                    $count++;
                }
            } else {

                $shippingResponse->labels = [
                    "format" => $response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->ImageFormat->Code,
                    "image" => $response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->GraphicImage
                ];

                file_put_contents("upsLabel" . $count . ".png", base64_decode($response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->GraphicImage));

                $count++;
            }

            return $shippingResponse;
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            var_dump($response);

            die;

            foreach ($response->response->errors as $error) {
                var_dump($error->message);
            }
        }
    }

    public function labelRecovery($trackingNumber = "1ZV5854W6815445507")
    {
        $version = 'v1807';

        $url = 'https://onlinetools.ups.com/ship/' . $version . '/shipments/labels';

        try {

            $client = new Client();

            $response = $client->post($url, [
                "headers" =>  [
                    "transId" => "12345",
                    "transactionSrc" => "TestTrack",
                    "Username" => $this->userName,
                    "Password" => $this->password,
                    "AccessLicenseNumber" => $this->accessLicenseNumber,
                    "Content-Type" => "application/json",
                    "Accept" => "application/json"
                ],
                "query" => [
                    "locale" => $this->locale
                ],
                "json" => [
                    "LabelRecoveryRequest" => [
                        "LabelSpecification" => [
                            "HTTPUserAgent" => "",
                            "LabelImageFormat" => [
                                "Code" => "ZPL"
                            ],
                            "LabelStockSize" => [
                                "Height" => "6",
                                "Width" => "4"
                            ]
                        ],
                        "Translate" => [
                            "LanguageCode" => "eng",
                            "DialectCode" => "US",
                            "Code" => "01"
                        ], "LabelDelivery" => [
                            "LabelLinkIndicator" => "",
                            "ResendEMailIndicator" => "",
                            "EMailMessage" => [
                                "EMailAddress" => ""
                            ]
                        ],
                        "TrackingNumber" => $trackingNumber
                    ]
                ]
            ]);

            $response = json_decode($response->getBody()->getContents());

            $label = $response->LabelRecoveryResponse->LabelResults->LabelImage->GraphicImage;

            var_dump($response);

            file_put_contents($response->LabelRecoveryResponse->LabelResults->TrackingNumber . ".png", base64_decode($label));
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            foreach ($response->response->errors as $error) {
                var_dump($error->message);
            }
        }
    }
}