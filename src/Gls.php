<?php

namespace AlfredoMeschis\LaravelFedex;

use AlfredoMeschis\LaravelFedex\Courriers\CourrierBase;
use AlfredoMeschis\LaravelFedex\Requests\AddressValidationRequest;
use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Requests\TrackRequest;
use AlfredoMeschis\LaravelFedex\Responses\AddressValidationResponse;
use AlfredoMeschis\LaravelFedex\Responses\ShippingResponse;
use AlfredoMeschis\LaravelFedex\Responses\TrackResponse;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Gls implements CourrierManagementInterface
{
    private $glsSite;
    private $glsCode;
    private $password;
    private $contractCode;

    public function __construct(array $config)
    {
        $this->glsSite = $config['glsSite'];
        $this->glsCode = $config['glsCode'];
        $this->password = $config['password'];
        $this->contractCode = $config['contractCode'];
    }

    public function getServicesTypes()
    {

        return [];
    }

    public function addressValidation(AddressValidationRequest $addressValidationRequest)
    {
        $url = ('https://checkaddress.gls-italy.com/wscheckaddress.asmx/CheckAddress');

        $client = new Client();

        $response = $client->get($url, [
            "headers" => [
                "Content-Type" => "json",
            ],
            "query" => [
                "SedeGls" => $this->glsSite,
                "CodiceClienteGls" => $this->glsCode,
                "PasswordClienteGls" => $this->password,
                "SiglaNazione" => $addressValidationRequest->address->countryCode,
                "Cap" => $addressValidationRequest->address->postalCode,
                "Localita" => $addressValidationRequest->address->city,
                "Indirizzo" => $addressValidationRequest->address->addressLine,
                "SiglaProvincia" => $addressValidationRequest->address->stateProvinceCode
            ]
        ]);

        $response = simplexml_load_string($response->getBody()->getContents());

        $addressValidationResponse = new AddressValidationResponse;
        $addressValidationResponse->addressExist = $response->Esito->__toString();

        $addressCount = 0;
        if(count($response->Address) > 1) {
            foreach($response->Address as $address) {

                $addressValidationResponse->addresses[$addressCount]['postalCode'] = $address->Cap->__toString();
                $addressValidationResponse->addresses[$addressCount]['city'] = $address->Comune->__toString();
                $addressValidationResponse->addresses[$addressCount]['addressLine'] = $address->Indirizzo->__toString();
                $addressValidationResponse->addresses[$addressCount]['stateProvinceCode'] = $address->SiglaProvincia->__toString();
                $addressCount++;
            }
        }

        return $addressValidationResponse;
    }

    public function track(TrackRequest $trackRequest): TrackResponse
    {
        $url = "https://infoweb.gls-italy.com/XML/get_xml_track.php";

        $client = new Client();

        $response = $client->get($url, [
            "query" => [
                "locpartenza" => "PA",
                "NumSped" => $trackRequest->trackNumber,
                "Cli" => $this->glsCode
            ]
        ]);

        $courrierBase = new CourrierBase([]);

        $responseArray = $courrierBase->responseToArray($response);

        $trackResponse = new TrackResponse;

        $events = $responseArray['SPEDIZIONE']['TRACKING'];

        $count = 0;

        
        foreach ($events['Data'] as $eventData) {

            $trackResponse->setHistory(
                $events['Luogo'][$count],
                $events['Stato'][$count],
                is_string($events['Data'][$count]) && is_string($events['Ora'][$count]) ? DateTime::createFromFormat('d/m/y-H:i', $events['Data'][$count] . "-" . $events['Ora'][$count])->format('d/m/y-H:i') : DateTime::createFromFormat('d/m/y-H:i', $events['Data'][$count] . "-" . "00:00")->format('d/m/y-H:i'),
            ); 
            $count++;
        }
        $trackResponse->setState();

        return $trackResponse;
    }

    /* service Rate not work temporally */
    public function rate(RateRequest $rateRequest)
    {
        $url = "https://labelservice.gls-italy.com/ilswebservice.asmx/RequestQuotation";

        $client = new Client();

        $response = $client->get($url, [

            "query" => [
                "XMLInfoQuotation" => str_replace("\n", "", "
<Info>
<SedeGls>PA</SedeGls>
<CodiceClienteGls>95794</CodiceClienteGls>
<PasswordClienteGls>mnc579</PasswordClienteGls>
<Quotation>
<CodiceContrattoGls>1179</CodiceContrattoGls>
<RagioneSociale>Scalia</RagioneSociale>
<Indirizzo>Via Maresciallo Caviglia, 10</Indirizzo>
<Localita>Palermo</Localita>
<Zipcode>90143</Zipcode>
<Provincia>PA</Provincia>
<Colli>1</Colli>
<PesoReale>10</PesoReale>
<MisureColli>10,10,10</MisureColli>
<ImportoContrassegno>500</ImportoContrassegno>
<TipoPorto>F</TipoPorto>
<Assicurazione>500</Assicurazione>
<TipoCollo>0</TipoCollo>
<ServiziAccessori>22,6</ServiziAccessori>
<TipoSpedizione></TipoSpedizione>
<Bda></Bda>
<PersonaRiferimento></PersonaRiferimento>
<TelefonoDestinatario></TelefonoDestinatario>
<AssicurazioneIntegrativa></AssicurazioneIntegrativa>
<Consenso>0</Consenso>
</Quotation>
</Info>")
            ]
        ]);

        $courrierBase = new CourrierBase([]);

        $responseArray = $courrierBase->responseToArray($response);

        /* dump($responseArray); */
    }

    public function shipping(ShippingRequest $shippingRequest): ShippingResponse
    {

        $url = "https://labelservice.gls-italy.com/ilswebservice.asmx/AddParcel";

        $client = new Client();
        $parcel = '';

        if (isset($shippingRequest->cashOnDeliveryValue)) {

            $cashOnDelivery = '<ImportoContrassegno>'.$shippingRequest->cashOnDeliveryValue.'</ImportoContrassegno><ModalitaIncasso>CONT</ModalitaIncasso>';
            
        } else {
            $cashOnDelivery = '';
        }

         var_dump($cashOnDelivery);
        die;

        foreach ($shippingRequest->packages as $key => $package) {

            $weight = $package['weightValue'];
            $pesoVolume = $package['lengthValue'] * $package['widthValue'] * $package['heightValue'] / 5000;

            $parcel .= str_replace("\n", "", "<Parcel>
<CodiceContrattoGls>" . $this->contractCode . "</CodiceContrattoGls> 
<RagioneSociale>$shippingRequest->recipientPersonName" . " " . "$shippingRequest->recipientCompanyName</RagioneSociale> 
<Indirizzo>".$shippingRequest->shipToAddress->addressLine."</Indirizzo>
<Localita>".$shippingRequest->shipToAddress->city."</Localita>
<Zipcode>".$shippingRequest->shipToAddress->postalCode."</Zipcode>
<Provincia>".$shippingRequest->shipToAddress->stateProvinceCode."</Provincia>
<Colli>$shippingRequest->packageCount</Colli>
<FormatoPdf>$shippingRequest->pdfFormat</FormatoPdf>
<GeneraPdf>4</GeneraPdf>
<ContatoreProgressivo>001</ContatoreProgressivo>
<PesoReale>$weight</PesoReale>
".$cashOnDelivery."
<PesoVolume>$pesoVolume</PesoVolume>
<TipoPorto>$shippingRequest->portType</TipoPorto>
<NoteSpedizione>$shippingRequest->note</NoteSpedizione>
</Parcel>");
        }

        $response = $client->post($url, [
            "form_params" => [
                "XMLInfoParcel" => str_replace("\n", "", "<Info>
<SedeGls>" . $this->glsSite . "</SedeGls>
<CodiceClienteGls>" . $this->glsCode . "</CodiceClienteGls> 
<PasswordClienteGls>" . $this->password . "</PasswordClienteGls> 
$parcel
</Info>")
            ]
        ]);

       
        $shippingResponse = new ShippingResponse;

        $courrierBase = new CourrierBase([]);

        $responseArray = $courrierBase->responseToArray($response);



        /* dump($responseArray); */

        if(isset($responseArray['Parcel'][0])) {

            $count = 1;
            foreach ($responseArray['Parcel'] as $resp) {
    
              /*   file_put_contents("gls_create_label_" . $count . ".pdf", base64_decode($resp['PdfLabel'])); */
    
                $shippingResponse->trackNumber = $resp["NumeroSpedizione"];
    
                $shippingResponse->labels = [$resp["PdfLabel"]];
                
                $nowTimestamp = $_SERVER['REQUEST_TIME'];
                
                $fileName = "glsLabel" . $count ."-". $nowTimestamp . ".pdf";
                
                file_put_contents( $fileName , base64_decode($resp['PdfLabel']));
                
                $shippingResponse->labelPath[] = $fileName;
                $count++;
            }
            
        } else {
           /*  file_put_contents("gls_create_label_1.pdf", base64_decode($responseArray['Parcel']['PdfLabel'])); */
    
            $shippingResponse->trackNumber = $responseArray['Parcel']["NumeroSpedizione"];

            $shippingResponse->labels = [$responseArray['Parcel']["PdfLabel"]];

            $nowTimestamp = $_SERVER['REQUEST_TIME'];
                
            $fileName = "glsLabel-". $nowTimestamp . ".pdf";
            
            file_put_contents( $fileName , base64_decode($responseArray['Parcel']["PdfLabel"]));
            
            $shippingResponse->labelPath[] = $fileName;
        }

        return $shippingResponse;
    }

    public function listSped()
    {
        $url = "https://labelservice.gls-italy.com/ilswebservice.asmx/ListSped";

        $client = new Client();

        $response = $client->get($url, [

            "query" => [
                "SedeGls" => $this->glsSite,
                "CodiceClienteGls" => $this->glsCode,
                "PasswordClienteGls" => $this->password
            ]
        ]);

        $courrierBase = new CourrierBase([]);

        $responseArray = $courrierBase->responseToArray($response);


        /* dump($responseArray); */
    }

    public function closeWorkDayByShipmentNumber()
    {
        $url = "https://labelservice.gls-italy.com/ilswebservice.asmx/CloseWorkDayByShipmentNumber";

        $client = new Client();

        $response = $client->get($url, [
            "query" => [
                "_xmlRequest" =>
                str_replace(
                    "\n",
                    "",
                    "<Info>
                        <SedeGls>" . $this->glsSite . "</SedeGls>
                        <CodiceClienteGls>" . $this->glsCode . "</CodiceClienteGls> 
                        <PasswordClienteGls>" . $this->password . "</PasswordClienteGls> 
                        <Parcel>
                        <NumeroDiSpedizioneGLSDaConfermare>610605889</NumeroDiSpedizioneGLSDaConfermare>
                        </Parcel>
                        </Info>"
                )
            ]
        ]);

        $courrierBase = new CourrierBase([]);

        $responseArray = $courrierBase->responseToArray($response);

        /* dump($responseArray); */
    }

    public function recoveryLabel()
    {
        $url = "https://labelservice.gls-italy.com/ilswebservice.asmx/GetPdf";

        $client = new Client();

        $response = $client->get($url, [
            "query" => [
                "SedeGls" => $this->glsSite,
                "CodiceCliente" => $this->glsCode,
                "Password" => $this->password,
                "CodiceContratto" => $this->contractCode,
                "ContatoreProgressivo" => "1"
            ]
        ]);

        $courrierBase = new CourrierBase([]);
        $responseArray = $courrierBase->responseToArray($response);
        file_put_contents("gls_recovery_label.pdf", base64_decode($responseArray[0]));

        /* dump($responseArray); */
    }
}