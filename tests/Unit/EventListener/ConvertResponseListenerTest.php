<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Tests\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use XIIFactors\AzureFunctions\EventListener\ConvertResponseListener;

class ConvertResponseListenerTest extends TestCase
{
    private ResponseEvent|MockObject $responseEvent;
    private Request|MockObject $request;
    private Response|MockObject $response;
    private ConvertResponseListener $sut;

    public function setUp(): void
    {
        parent::setUp();

        $this->responseEvent = $this
            ->getMockBuilder(ResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this
            ->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sut = new ConvertResponseListener();
    }

    /** @test */
    public function whenNotMainRequestThenReturn(): void
    {
        $this->responseEvent
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(false);

        $this->assertNull($this->sut->onKernelResponse($this->responseEvent));
    }

    /** @test */
    public function whenRequestIsNotForwardedThenReturn(): void
    {
        $this->responseEvent
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->responseEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'Data' => [],
                'Metadata' => [],
            ]);

        $this->assertNull($this->sut->onKernelResponse($this->responseEvent));
    }

    /** @test */
    public function whenExcludeHeaderIsSetThenReturn(): void
    {
        $this->responseEvent
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->responseEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->request->headers = new HeaderBag([
            ConvertResponseListener::HEADER => '0'
        ]);

        $this->assertNull($this->sut->onKernelResponse($this->responseEvent));
    }

    /** @test */
    public function whenResponseContentTypeIsNotJsonThenReturn(): void
    {
        $this->responseEvent
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->responseEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->responseEvent
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);

        $this->request
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->request->headers = new HeaderBag();

        $this->response->headers = new HeaderBag([
            'Content-Type' => 'text/plain'
        ]);

        $this->assertNull($this->sut->onKernelResponse($this->responseEvent));
    }

    /** @test */
    public function whenResponseContentIsNotAStringThenReturn(): void
    {
        $this->responseEvent
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->responseEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->responseEvent
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);

        $this->request
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->request->headers = new HeaderBag();

        $this->response->headers = new HeaderBag([
            'Content-Type' => 'application/json'
        ]);

        $this->response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(false);

        $this->assertNull($this->sut->onKernelResponse($this->responseEvent));
    }

    /**
     * @test
     * @dataProvider responses
     */
    public function whenReturnValueIsArrayThenConvert(): void
    {
        $responseBody = json_encode(['created' => true]);

        $this->responseEvent
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->responseEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->responseEvent
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);

        $this->request
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->request->headers = new HeaderBag();

        $this->response->headers = new HeaderBag([
            'Content-Type' => 'application/json'
        ]);

        $this->response
            ->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode([
                'ReturnValue' => [
                    'status' => 201,
                    'body' => $responseBody,
                    'headers' => [
                        'Content-Type' => 'text/html'
                    ],
                ]
            ]));

        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(201);

        $this->response
            ->expects($this->once())
            ->method('setContent')
            ->with($responseBody);

        $this->responseEvent
            ->expects($this->once())
            ->method('setResponse')
            ->with($this->response);

        $this->assertNull($this->sut->onKernelResponse($this->responseEvent));
    }
}
