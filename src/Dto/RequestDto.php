<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Dto;

class RequestDto
{
    public function __construct(
        public readonly array $Data,
        public readonly array $Metadata,
    ) {
    }
}