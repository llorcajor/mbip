<?php

use App\Kernel;


$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    die();
}


require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
