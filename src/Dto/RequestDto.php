<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Dto;

class RequestDto
{
    /**
     * @param array<string, mixed> $Data
     * @param array<string, mixed> $Metadata
     */
    public function __construct(
        public readonly array $Data,
        public readonly array $Metadata,
    ) {
    }

    public function getFunctionName(): string
    {
        return $this->Metadata['sys']['MethodName'] ?? 'UNKNOWN';
    }
}
