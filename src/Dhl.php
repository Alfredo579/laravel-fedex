<?php

namespace AlfredoMeschis\LaravelFedex;

use AlfredoMeschis\LaravelFedex\Courriers\CourrierBase;
use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Requests\TrackRequest;
use AlfredoMeschis\LaravelFedex\Responses\RateResponse;
use AlfredoMeschis\LaravelFedex\Responses\ShippingResponse;
use AlfredoMeschis\LaravelFedex\Responses\TrackResponse;
use Carbon\Carbon;
use GuzzleHttp\Client;

/* TODO: 
    addressValidation function,
*/

class Dhl extends CourrierBase implements CourrierManagementInterface
{
    private $username;
    private $password;
    private $account;

    public function __construct(array $config)
    {
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->account = $config['account'];
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

        dump(json_decode($response->getBody()->getContents()));
    }

    public function rate(RateRequest $rateRequest)
    {
        $url = "https://wsbexpress.dhl.com/rest/sndpt/RateRequest";

        $client = new Client();

        $response = $client->post($url, [
            "headers" => [
                "Authorization" => $this->encodeAuth($this->username, $this->password)
            ],
            "json" => [
                'RateRequest' => [
                    'ClientDetails' => NULL,
                    'RequestedShipment' => [
                        'DropOffType' => 'REQUEST_COURIER',
                        'ShipTimestamp' => Carbon::today(),
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
                                'City' => $rateRequest->shipFromAddress->city,
                                'PostalCode' => $rateRequest->shipFromAddress->postalCode,
                                'CountryCode' => $rateRequest->shipFromAddress->countryCode,
                            ],
                        ],
                        'Packages' => [
                            'RequestedPackages' => [
                                '@number' => $rateRequest->packageCount,
                                'Weight' => [
                                    'Value' => $rateRequest->weight,
                                ],
                                'Dimensions' => [
                                    'Length' => $rateRequest->length,
                                    'Width' => $rateRequest->width,
                                    'Height' => $rateRequest->height
                                ],
                            ],
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

            $rateResponse->totalPrice = $service->TotalNet->Amount;

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

        dump($rateResponses);
    }

    public function shipping(ShippingRequest $shippingRequest): ShippingResponse
    {
        $url = "https://wsbexpress.dhl.com/rest/sndpt/ShipmentRequest";

        $client = new Client();

        /* '2021-09-10T12:30:47GMT+01:00' */

        $response = $client->post($url, [
            "headers" => [
                "Authorization" => $this->encodeAuth($this->username, $this->password)
            ],
            "json" => [
                'ShipmentRequest' => [
                    'RequestedShipment' => [
                        'ShipmentInfo' => [
                            'DropOffType' => $shippingRequest->dhlDropoffType,
                            'ServiceType' => $shippingRequest->dhlServiceType,
                            'Account' => $this->account,
                            'Currency' => 'SGD',
                            'UnitOfMeasurement' => 'SI',
                        ],
                        'ShipTimestamp' => Carbon::now()->add("3 hours")->timezone("GMT")->format("Y-m-d\TH:i:seP"),
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
                                    'CompanyName' => 'DHL',
                                    'PhoneNumber' => $shippingRequest->shipperPhoneNumber,
                                    'EmailAddress' => $shippingRequest->shipperEmail,
                                ],
                                'Address' => [
                                    'StreetLines' => $shippingRequest->shipperAddressStreetLines,
                                    'City' => $shippingRequest->shipperAddressCity,
                                    'PostalCode' => $shippingRequest->shipperAddressPostalCode,
                                    'CountryCode' => $shippingRequest->shipperAddressCountryCode,
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
                                    'StreetLines' => $shippingRequest->recipientAddressStreetLines,
                                    'City' => $shippingRequest->recipientAddressCity,
                                    'StateOrProvinceCode' => $shippingRequest->recipientAddressStateOrProvinceCode,
                                    'PostalCode' => $shippingRequest->recipientAddressPostalCode,
                                    'CountryCode' => $shippingRequest->recipientAddressCountryCode,
                                ],
                            ],
                        ],
                        'Packages' => [
                            'RequestedPackages' => [
                                0 => [
                                    '@number' => '1',
                                    'Weight' => $shippingRequest->weightValue,
                                    'Dimensions' => [
                                        'Length' => $shippingRequest->dimensionsLength,
                                        'Width' => $shippingRequest->dimensionsWidth,
                                        'Height' => $shippingRequest->dimensionsHeight,
                                    ],
                                    'CustomerReferences' => 'Piece 1',
                                ],
                            ],
                        ],
                        'ManifestBypass' => 'N',
                    ],
                ],
            ]
        ]);

        $response = json_decode($response->getBody()->getContents());

        /* dump($response);
        die; */

        file_put_contents("dhl_label.pdf", base64_decode($response->ShipmentResponse->LabelImage[0]->GraphicImage));

        $shippingResponse = new ShippingResponse;

        $shippingResponse->labels = [$response->ShipmentResponse->LabelImage[0]->GraphicImage];

        $shippingResponse->trackNumber = $response->ShipmentResponse->PackagesResult->PackageResult[0]->TrackingNumber;

        dump($shippingResponse);

        return $shippingResponse;
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