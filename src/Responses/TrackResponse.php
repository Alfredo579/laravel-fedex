<?php

namespace AlfredoMeschis\LaravelFedex\Responses;

use Carbon\Carbon;

class TrackResponse
{

    public $history = [];
    public $state;
    
    public function setHistory($city, $description, $date)
    {
        $this->history[]=[
            "city" => $city,
            "description" => $description,
            "date" => $date
        ];
        return $this->history;
    }

    public function setState()
    {
        $this->state = $this->history[0];
    }
}
