<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Dto;

class ResponseDto
{
    /**
     * @param array<string, mixed>|null $Outputs
     * @param array<string, string> $Logs
     * @param mixed $ReturnValue
     */
    public function __construct(
        public readonly ?array $Outputs = null,
        public readonly array $Logs = [],
        public mixed $ReturnValue = null,
    ) {
        $this->ReturnValue = $ReturnValue ?? '';
    }
}
