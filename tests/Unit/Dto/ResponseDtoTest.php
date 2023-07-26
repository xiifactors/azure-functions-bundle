<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Tests\Dto;

use PHPUnit\Framework\TestCase;
use XIIFactors\AzureFunctions\Dto\ResponseDto;

class ResponseDtoTest extends TestCase
{
    /** @test */
    public function whenReturnValueIsNullItIsConvertedToEmptyString(): void
    {
        $sut = new ResponseDto(
            ReturnValue: null
        );

        $this->assertSame('', $sut->ReturnValue);
    }
}
