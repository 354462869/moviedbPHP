<?php

namespace App\controller;



class Transmission extends \Transmission\Transmission
{
    public function __construct(string $host = null, int $port = null, string $path = null)
    {
        if ($host === null) {config('app.transmission_host');}
        if ($port === null) {config('app.transmission_port');}
        parent::__construct($host, $port, $path);
    }
}
