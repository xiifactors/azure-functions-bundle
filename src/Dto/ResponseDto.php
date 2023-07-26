<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Dto;

class ResponseDto
{
    public function __construct(
        public readonly ?array $Outputs = null,
        public readonly array $Logs = [],
        public mixed $ReturnValue = null,
    ) {
        $this->ReturnValue = $ReturnValue ?? '';
    }
}