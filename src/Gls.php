<?php

namespace AlfredoMeschis\LaravelFedex;

use AlfredoMeschis\LaravelFedex\Courriers\CourrierBase;
use AlfredoMeschis\LaravelFedex\Requests\RateRequest;
use AlfredoMeschis\LaravelFedex\Requests\ShippingRequest;
use AlfredoMeschis\LaravelFedex\Requests\TrackRequest;
use AlfredoMeschis\LaravelFedex\Responses\ShippingResponse;
use AlfredoMeschis\LaravelFedex\Responses\TrackResponse;
use Carbon\Carbon;
use GuzzleHttp\Client;

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

    public function addressValidation()
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
                "SiglaNazione" => "IT",
                "Cap" => "21013",
                "Localita" => "Gallarate",
                "Indirizzo" => "Via alberico alberici 2",
                "SiglaProvincia" => "VA"
            ]
        ]);

        dump(simplexml_load_string($response->getBody()->getContents()));
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
                is_string($events['Data'][$count]) && is_string($events['Ora'][$count]) ? Carbon::createFromFormat('d/m/y-H:i', $events['Data'][$count] . "-" . $events['Ora'][$count]) : Carbon::createFromFormat('d/m/y', $events['Data'][$count])->startOfDay(),
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

        /*   $xml = simplexml_load_string($response->getBody()->getContents(), "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, TRUE); */

        $courrierBase = new CourrierBase([]);

        $responseArray = $courrierBase->responseToArray($response);

        dump($responseArray);
    }

    public function shipping(ShippingRequest $shippingRequest): ShippingResponse
    {

        $url = "https://labelservice.gls-italy.com/ilswebservice.asmx/AddParcel";

        $client = new Client();

        $response = $client->get($url, [

            "query" => [
                "XMLInfoParcel" => str_replace("\n", "", "<Info>
                <SedeGls>" . $this->glsSite . "</SedeGls>
                <CodiceClienteGls>" . $this->glsCode . "</CodiceClienteGls> 
                <PasswordClienteGls>" . $this->password . "</PasswordClienteGls> 
                <Parcel>
                    <CodiceContrattoGls>" . $this->contractCode . "</CodiceContrattoGls> 
                    <RagioneSociale>$shippingRequest->recipientPersonName" . " " . "$shippingRequest->recipientCompanyName</RagioneSociale> 
                    <Indirizzo>$shippingRequest->recipientAddressStreetLines</Indirizzo>
                    <Localita>$shippingRequest->recipientAddressCity</Localita>
                    <Zipcode>$shippingRequest->recipientAddressPostalCode</Zipcode>
                    <Provincia>$shippingRequest->recipientAddressStateOrProvinceCode</Provincia>
                    <Colli>$shippingRequest->packageCount</Colli>
                    <FormatoPdf>$shippingRequest->pdfFormat</FormatoPdf>
                    <GeneraPdf>4</GeneraPdf>
                    <ContatoreProgressivo>001</ContatoreProgressivo>
                    <PesoReale>$shippingRequest->weightValue</PesoReale>
                    <TipoPorto>$shippingRequest->portType</TipoPorto>
                    <NoteSpedizione>$shippingRequest->note</NoteSpedizione>
                </Parcel>
                </Info>")
            ]
        ]);

        $shippingResponse = new ShippingResponse;

        $courrierBase = new CourrierBase([]);

        $responseArray = $courrierBase->responseToArray($response);

        file_put_contents("gls_create_label.pdf", base64_decode($responseArray['Parcel']['PdfLabel']));

        dump($responseArray);

        $shippingResponse->trackNumber = $responseArray["Parcel"]["NumeroSpedizione"];

        $shippingResponse->labels = [$responseArray["Parcel"]["PdfLabel"]];

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


        dump($responseArray);
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

        dump($responseArray);
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

        dump($responseArray);
    }
}
