<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Tests\Dto;

use PHPUnit\Framework\TestCase;
use XIIFactors\AzureFunctions\Dto\RequestDto;

class RequestDtoTest extends TestCase
{
    /** @test */
    public function whenFunctionNameAvailableItCanBeRetrieved(): void
    {
        $sut = new RequestDto([], ['sys' => ['MethodName' => 'HttpTrigger']]);

        $this->assertSame('HttpTrigger', $sut->getFunctionName());
    }
}
