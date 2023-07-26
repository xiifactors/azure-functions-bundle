<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

$_ENV['APP_ENV'] = getenv('APP_ENV', true) ?: getenv('APP_ENV');
$_ENV['APP_DEBUG'] = getenv('APP_DEBUG', true) ?: getenv('APP_DEBUG');

return function (array $context): Kernel {
    return new Kernel($context['APP_ENV'], $context['APP_DEBUG']);
};