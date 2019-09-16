<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function __construct()
    {
        setlocale(LC_ALL, 'es_ES');
        Carbon::setLocale('es');
    }
}
