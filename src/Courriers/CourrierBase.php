<?php 

namespace AlfredoMeschis\LaravelFedex\Courriers;

use AlfredoMeschis\LaravelFedex\CourrierManagementInterface;

class CourrierBase implements CourrierManagementInterface {

    public function __construct(array $config){}

    public function encodeAuth($username, $password) {

        return "basic " . base64_encode($username . ":" . $password);
    }

    public function responseToArray($response) {
        
        $xml = simplexml_load_string($response->getBody()->getContents(), "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        return json_decode($json, TRUE);
    }
}