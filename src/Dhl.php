<?php

namespace AlfredoMeschis\LaravelFedex;

use AlfredoMeschis\LaravelFedex\Courriers\CourrierBase;
use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Requests\TrackRequest;
use AlfredoMeschis\LaravelFedex\Responses\RateResponse;
use AlfredoMeschis\LaravelFedex\Responses\ShippingResponse;
use AlfredoMeschis\LaravelFedex\Responses\TrackResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/* TODO: 
    addressValidation function,
*/

class Dhl extends CourrierBase implements CourrierManagementInterface
{
    private $username;
    private $password;
    private $account;
    public $dropOffTypes = [
        'REGULAR_PICKUP' => 'REGULAR_PICKUP',
        'REQUEST_COURIER' => 'REQUEST_COURIER'
    ];
    public $serviceTypes = [
        "2" => "Acquisto Semplice",
        "5" => "SprintLine",
        "6" => "SecureLine",
        "7" => "Corriere Semplice",
        "9" => "Europack",
        "B" => "Breack bulk express",
        "C" => "Corriere Medico",
        "D" => "Corriere internazionale",
        "U" => "Corriere internazionale",
        "K" => "Corriere 9:00",
        "L" => "Corriere 10:00",
        "G" => "Seleziona Risparmio Familiare",
        "W" => "Seleziona Risparmio",
        "I" => "Breack bulk economy",
        "N" => "Domestic Express",
        "O" => "Altri",
        "R" => "Globalmail business",
        "S" => "Stesso Giorno",
        "T" => "Corriere 12:00",
        "X" => "Corriere Busta",
        "P" => "Express Wordwilde (WPX)"

    ];

    public function __construct(array $config)
    {
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->account = $config['account'];
    }

    public function getServicesTypes()
    {

        return ["serviceType" => $this->serviceTypes, "dropOffType" => $this->dropOffTypes];
    }

    public function getServices()
    {

        $url = "https://express.api.dhl.com/mydhlapi/test/products";

        $client = new Client();

        $response = $client->get($url, [
            "headers" => [
                "Authorization" => $this->encodeAuth($this->username, $this->password)
            ],
            "query" =>  [
                "accountNumber" => "106067134",
                "originCountryCode" => "IT",
                "originPostalCode" => "90143",
                "originCityName" => "Palermo",
                "destinationCountryCode" => "IT",
                "destinationPostalCode" => "90124",
                "destinationCityName" => "Palermo",
                "weight" => "5",
                "length" => "4",
                "width" => "4",
                "height" => "4",
                "plannedShippingDate" => "2021-11-26",
                "isCustomsDeclarable" => "false",
                "unitOfMeasurement" => "metric",
                "nextBusinessDay" => "false"
            ]
        ]);

        /*  dump(json_decode($response->getBody()->getContents())); */
    }

    public function rate(RateRequest $rateRequest)
    {
        $url = "https://wsbexpress.dhl.com/rest/sndpt/RateRequest";

        $client = new Client();

        foreach ($rateRequest->packages as $pack) {

            $packQuery[] =
                [
                    '@number' => $pack['packageCount'],
                    'Weight' => [
                        'Value' => $pack['weight'],
                    ],
                    'Dimensions' => [
                        'Length' => $pack['length'],
                        'Width' => $pack['width'],
                        'Height' => $pack['height']
                    ]
                ];
        }


        $response = $client->post($url, [
            "headers" => [
                "Authorization" => $this->encodeAuth($this->username, $this->password)
            ],
            "json" => [
                'RateRequest' => [
                    'ClientDetails' => NULL,
                    'RequestedShipment' => [
                        'DropOffType' => 'REQUEST_COURIER',
                        'ShipTimestamp' => date('c'),
                        'UnitOfMeasurement' => 'SI',
                        'Content' => 'NON_DOCUMENTS',
                        'PaymentInfo' => 'DAP',
                        'NextBusinessDay' => 'Y',
                        'Account' => 106067134,
                        'Ship' => [
                            'Shipper' => [
                                'City' => $rateRequest->shipperAddress->city,
                                'PostalCode' => $rateRequest->shipperAddress->postalCode,
                                'CountryCode' => $rateRequest->shipperAddress->countryCode,
                            ],
                            'Recipient' => [
                                'City' => $rateRequest->shipToAddress->city,
                                'PostalCode' => $rateRequest->shipToAddress->postalCode,
                                'CountryCode' => $rateRequest->shipToAddress->countryCode,
                            ],
                        ],
                        'Packages' => [
                            'RequestedPackages' => $packQuery
                        ],
                    ],
                ],
            ]
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $rateResponses = [];

        foreach ($responseBody->RateResponse->Provider[0]->Service as $service) {

            $rateResponse  = new RateResponse;

            $rateResponse->expectedDateDelivery = $service->DeliveryTime;

            $rateResponse->totalPrice = [
                "currency" => $service->Charges->Currency,
                "value" => $service->TotalNet->Amount
            ];

            if (is_array($service->Charges->Charge)) {

                foreach ($service->Charges->Charge as $charge) {
                    $rateResponse->detailPrice = [
                        "name" => $charge->ChargeType,
                        "currency" => $service->Charges->Currency,
                        "value" => $charge->ChargeAmount
                    ];
                }
            } else {

                $rateResponse->detailPrice = [
                    "name" => $service->Charges->Charge->ChargeType,
                    "currency" => $service->Charges->Currency,
                    "value" => $service->Charges->Charge->ChargeAmount
                ];
            }

            $rateResponses[] = $rateResponse;
        }

        return $rateResponses;
    }

    public function shipping(ShippingRequest $shippingRequest): ShippingResponse
    {
        $url = "https://wsbexpress.dhl.com/rest/sndpt/ShipmentRequest";

        $client = new Client();

        $packageCounter = 1;

        foreach ($shippingRequest->packages as $package) {

            $packQuery[] = [
                '@number' => 2 /* $packageCounter */,
                'Weight' => $package['weightValue'],
                'Dimensions' => [
                    'Length' => $package['lengthValue'],
                    'Width' => $package['widthValue'],
                    'Height' => $package['heightValue'],
                ],
            ];

            $packageCounter++;
        }

        if(isset($shippingRequest->cashOnDeliveryValue)) {

            $cashOnDelivery = [
                'Service' => [
                    'ServiceType' => 'KB',
                    'ServiceValue' => $shippingRequest->cashOnDeliveryValue,
                    'CurrencyCode' => 'EUR',
                    'PaymentMethods' => [
                        'PaymentMethod' => 'CSH'
                    ]
                ]
            ];
            
        } else {
            $cashOnDelivery = [];
        }

        $shippingTime = gmdate("Y-m-d\TH:i:s\G\M\T\+\\0\\0\:\\0\\0", strtotime('+3 hours'));

     
      /* dump(Carbon::now()->add("3 hours")->timezone("GMT")->format("Y-m-d\TH:i:seP")); */

     
        /* '2021-09-10T12:30:47GMT+01:00' */

        try {

            $originalRequest = [
                "headers" => [
                    "Authorization" => $this->encodeAuth($this->username, $this->password)
                ],
                "json" => [
                    'ShipmentRequest' => [
                        'RequestedShipment' => [
                            "GetRateEstimates" => "Y", /* not work */
                            'ShipmentInfo' => [
                                "LabelTemplate" => "ECOM26_84_001",
                                'DropOffType' => $shippingRequest->dropOffType,
                                'ServiceType' => $shippingRequest->serviceType,
                                'Account' => $this->account,
                                'Currency' => 'EUR',
                                'UnitOfMeasurement' => 'SI',
                                'SpecialServices' => $cashOnDelivery
                            ],
                            'ShipTimestamp' => $shippingTime/*  Carbon::now()->add("3 hours")->timezone("GMT")->format("Y-m-d\TH:i:seP") */,
                            'PaymentInfo' => 'DDP',
                            'InternationalDetail' => [
                                'Commodities' => [
                                    'NumberOfPieces' => $shippingRequest->packageCount,
                                    'Description' => 'Customer Reference 1',
                                    'CountryOfManufacture' => 'CN',
                                    'Quantity' => 1,
                                    'UnitPrice' => 5,
                                    'CustomsValue' => 10,
                                ],
                                'Content' => 'NON_DOCUMENTS',
                            ],
                            'Ship' => [
                                'Shipper' => [
                                    'Contact' => [
                                        'PersonName' => $shippingRequest->shipperPersonName,
                                        'CompanyName' => $shippingRequest->shipperCompanyName,
                                        'PhoneNumber' => $shippingRequest->shipperPhoneNumber,
                                        'EmailAddress' => $shippingRequest->shipperEmail,
                                    ],
                                    'Address' => [
                                        'StreetLines' => $shippingRequest->shipperAddress->addressLine,
                                        'City' => $shippingRequest->shipperAddress->city,
                                        'PostalCode' => $shippingRequest->shipperAddress->postalCode,
                                        'CountryCode' => $shippingRequest->shipperAddress->countryCode,
                                    ],
                                ],
                                'Recipient' => [
                                    'Contact' => [
                                        'PersonName' => $shippingRequest->recipientPersonName,
                                        'CompanyName' => $shippingRequest->recipientCompanyName,
                                        'PhoneNumber' => $shippingRequest->recipientPhoneNumber,
                                        'EmailAddress' => $shippingRequest->recipientEmail,
                                    ],
                                    'Address' => [
                                        'StreetLines' => $shippingRequest->shipToAddress->addressLine,
                                        'City' => $shippingRequest->shipToAddress->city,
                                        'StateOrProvinceCode' => $shippingRequest->shipToAddress->stateProvinceCode,
                                        'PostalCode' => $shippingRequest->shipToAddress->postalCode,
                                        'CountryCode' => $shippingRequest->shipToAddress->countryCode,
                                    ],
                                ],
                            ],
                            'Packages' => [
                                'RequestedPackages' => $packQuery
                            ],
                            'ManifestBypass' => 'N',
                        ],
                    ],
                ]
            ];
/* 
            echo '<pre>' . var_export($originalRequest, true) . '</pre>';
            die; */
            $response = $client->post($url, $originalRequest);
            

            $response = json_decode($response->getBody()->getContents());

           /*  dump($response); */
 
            /* file_put_contents("dhl_label.pdf", base64_decode($response->ShipmentResponse->LabelImage[0]->GraphicImage)); */

            $shippingResponse = new ShippingResponse;

            $shippingResponse->labels = [$response->ShipmentResponse->LabelImage[0]->GraphicImage];

            $shippingResponse->trackNumber = $response->ShipmentResponse->PackagesResult->PackageResult[0]->TrackingNumber; 

            $nowTimestamp = $_SERVER['REQUEST_TIME'];

            $fileName = "dhlLabel-". $nowTimestamp . ".pdf";

            file_put_contents( $fileName ,  base64_decode($response->ShipmentResponse->LabelImage[0]->GraphicImage));

            $shippingResponse->labelPath[] = $fileName;


            return $shippingResponse;
        } catch (ClientException $e) {

            var_dump($e);
        }
    }

    public function track(TrackRequest $trackRequest): TrackResponse
    {
        $url = "https://wsbexpress.dhl.com/rest/sndpt/TrackingRequest";

        $client = new Client();

        $response = $client->post($url, [
            "headers" => [
                "Authorization" => $this->encodeAuth($this->username, $this->password)
            ],
            "json" => [
                'trackShipmentRequest' => [
                    'trackingRequest' => [
                        'TrackingRequest' => [
                            "LanguageCode" => "ita",
                            'Request' => [
                                'ServiceHeader' => [
                                    'MessageTime' => '2015-10-10T12:40:00Z',
                                    'MessageReference' => '896ab310ba5311e38d9ffb21b7e57543',
                                ],
                            ],
                            'AWBNumber' => [
                                'ArrayOfAWBNumberItem' => $trackRequest->trackNumber,
                            ],
                            'LevelOfDetails' => 'ALL_CHECKPOINTS',
                        ],
                    ],
                ],
            ]
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $trackResponse = new TrackResponse;

        $events = $responseBody->trackShipmentRequestResponse->trackingResponse->TrackingResponse->AWBInfo->ArrayOfAWBInfoItem[1]->ShipmentInfo->ShipmentEvent->ArrayOfShipmentEventItem;

        foreach (array_reverse($events) as $event) {

            $trackResponse->setHistory(
                $event->ServiceArea->Description ?? '',
                $event->ServiceEvent->Description,
                $event->Date
            );
        }

        $trackResponse->setState();

        return $trackResponse;
    }
}