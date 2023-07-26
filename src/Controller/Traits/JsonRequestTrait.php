<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;

trait JsonRequestTrait
{
    public function getRequestBody(Request $request): array
    {
        $body = $request->getContent();

        if (is_array($body) === false) {
            $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        return $body;
    }
}
