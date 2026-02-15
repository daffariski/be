<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use stdClass;

use App\Helpers\LightControllerHelper;

abstract class Controller
{
    use LightControllerHelper;
}
