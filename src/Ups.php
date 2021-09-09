<?php

namespace AlfredoMeschis\LaravelFedex;

use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Requests\TrackRequest;
use AlfredoMeschis\LaravelFedex\Responses\RateResponse;
use AlfredoMeschis\LaravelFedex\Responses\ShippingResponse;
use AlfredoMeschis\LaravelFedex\Responses\TrackResponse;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Ups implements CourrierManagementInterface
{

    private $userName;
    private $password;
    private $accessLicenseNumber;
    private $locale;
    private $shipperNumber;
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

    public function __construct(array $config)
    {
        $this->userName = $config['userName'];
        $this->password = $config['password'];
        $this->accessLicenseNumber = $config['accessLicenseNumber'];
        $this->locale = $config['locale'];
        $this->shipperNumber = $config['shipperNumber'];
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

            dump(json_decode($response->getBody()->getContents()));
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            foreach ($response->response->errors as $error) {
                dump($error->message);
            }
        }
    }

    public function rate(RateRequest $rateRequest): RateResponse
    {

        $url = 'https://wwwcie.ups.com/ship/v1801/rating/Rate';

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
                                "ShipFrom" => [
                                    "Name" => "Billy Blanks",
                                    "Address" => [
                                        "AddressLine" => $rateRequest->shipFromAddress->addressLine,
                                        "City" => $rateRequest->shipFromAddress->city,
                                        "StateProvinceCode" => $rateRequest->shipFromAddress->stateProvinceCode,
                                        "PostalCode" => $rateRequest->shipFromAddress->postalCode,
                                        "CountryCode" => $rateRequest->shipFromAddress->countryCode
                                    ]
                                ],
                                "Service" => [
                                    "Code" => $rateRequest->serviceCode,
                                    "Description" => "Ground"
                                ],

                                "ShipmentTotalWeight" => [
                                    "UnitOfMeasurement" => [
                                        "Code" => $rateRequest->unitOfMeasurementWeight,
                                        "Description" => "Pounds"
                                    ],
                                    "Weight" => $rateRequest->totalWeight
                                ],
                                "Package" => [
                                    "PackagingType" => [
                                        "Code" => "00",
                                        "Description" => "Package"
                                    ],
                                    "Dimensions" => [
                                        "UnitOfMeasurement" => [
                                            "Code" => $rateRequest->unitOfMeasurementDimention
                                        ],
                                        "Length" => $rateRequest->length,
                                        "Width" => $rateRequest->width,
                                        "Height" => $rateRequest->height
                                    ],
                                    "PackageWeight" => [
                                        "UnitOfMeasurement" => [
                                            "Code" => $rateRequest->unitOfMeasurementWeight
                                        ],
                                        "Weight" => $rateRequest->weight
                                    ]
                                ]
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

                dump($error->message);
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

            $trackResponse = new TrackResponse;

            foreach ($resp->trackResponse->shipment[0]->package[0]->activity as $activityItem) {

                $trackResponse->setHistory(
                    $activityItem->location->address->city,
                    $activityItem->status->description,
                    Carbon::parse($activityItem->date)
                );
            }

            $trackResponse->setState();

            return $trackResponse;
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            foreach ($response->response->errors as $error) {

                dump($error->message);
            }
        }
    }

    public function shipping(ShippingRequest $shippingRequest): ShippingResponse
    {
        $version = 'v1807';
        $packQuery = [];

        foreach ($shippingRequest->packages as $package) {

            $packQuery[] =  [
                "Description" => $package['upsPackageDescription'],
                "Packaging" => [
                    "Code" => $package['upsPackagingCode']
                ],
                "PackageWeight" => [
                    "UnitOfMeasurement" => [
                        "Code" => $package['weightUnits']
                    ],
                    "Weight" => $package['weightValue']
                ],
                "PackageServiceOptions" => ""
            ];
        }



        $url = 'https://wwwcie.ups.com/ship/' . $version . '/shipments';

        try {

            $client = new Client();

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

                                "Description" => $shippingRequest->upsShipmentRequestDescription,
                                "Shipper" => [
                                    "Name" => $shippingRequest->shipperPersonName,
                                    "AttentionName" => $shippingRequest->shipperAttentionName,
                                    "TaxIdentificationNumber" => $shippingRequest->shipperTaxIdentificationNumber,
                                    "Phone" => [
                                        "Number" => $shippingRequest->shipperPhoneNumber
                                    ],
                                    "ShipperNumber" => $this->shipperNumber,
                                    "Address" => [
                                        "AddressLine" => $shippingRequest->shipperAddressStreetLines,
                                        "City" => $shippingRequest->shipperAddressCity,
                                        "StateProvinceCode" => $shippingRequest->shipperAddressStateOrProvinceCode,
                                        "PostalCode" => $shippingRequest->shipperAddressPostalCode,
                                        "CountryCode" => $shippingRequest->shipperAddressCountryCode
                                    ]
                                ],
                                "ShipTo" => [
                                    "Name" => $shippingRequest->recipientPersonName,
                                    "AttentionName" => $shippingRequest->recipientAttentionName,
                                    "Phone" => [
                                        "Number" => $shippingRequest->recipientPhoneNumber
                                    ],
                                    "FaxNumber" => "1234567999",
                                    "TaxIdentificationNumber" => $shippingRequest->recipientTaxIdentificationNumber,
                                    "Address" => [
                                        "AddressLine" => $shippingRequest->recipientAddressStreetLines,
                                        "City" => $shippingRequest->recipientAddressCity,
                                        "StateProvinceCode" => $shippingRequest->recipientAddressStateOrProvinceCode,
                                        "PostalCode" => $shippingRequest->recipientAddressPostalCode,
                                        "CountryCode" => $shippingRequest->recipientAddressCountryCode
                                    ]
                                ],
                                "ShipFrom" => [
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
                                ],
                                "PaymentInformation" => [
                                    "ShipmentCharge" => [
                                        "Type" => "01",
                                        "BillShipper" => [
                                            "AccountNumber" => "V5854W"
                                        ]
                                    ]
                                ],
                                "Service" => [
                                    "Code" => $shippingRequest->upsServiceCode,
                                    "Description" => "Expedited"
                                ],
                                "Package" => $packQuery,
                                "ItemizedChargesRequestedIndicator" => "",
                                "RatingMethodRequestedIndicator" => "",
                                "TaxInformationIndicator" => "", "ShipmentRatingOptions" => [
                                    "NegotiatedRatesIndicator" => ""
                                ]
                            ],
                            "LabelSpecification" => [
                                "LabelImageFormat" => [
                                    "Code" => $shippingRequest->imageType
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

            if (is_array($response->ShipmentResponse->ShipmentResults->PackageResults)) {

                foreach ($response->ShipmentResponse->ShipmentResults->PackageResults as $pack) {

                    $shippingResponse->labels[] = [
                        "format" => $pack->ShippingLabel->ImageFormat->Code,
                        "image" => $pack->ShippingLabel->GraphicImage
                    ];
                }
            } else {

                $shippingResponse->labels = [
                    "format" => $response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->ImageFormat->Code,
                    "image" => $response->ShipmentResponse->ShipmentResults->PackageResults->ShippingLabel->GraphicImage
                ];
            }

            return $shippingResponse;
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            foreach ($response->response->errors as $error) {
                dump($error->message);
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

            dump($response);

            file_put_contents($response->LabelRecoveryResponse->LabelResults->TrackingNumber . ".png", base64_decode($label));
        } catch (ClientException $e) {

            $response = json_decode($e->getResponse()->getBody()->getContents());

            foreach ($response->response->errors as $error) {
                dump($error->message);
            }
        }
    }
}