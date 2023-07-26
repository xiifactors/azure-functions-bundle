<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\Controller;

use XIIFactors\AzureFunctions\Dto\RequestDto;
use XIIFactors\AzureFunctions\Dto\ResponseDto;
use App\Kernel;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[
    Route(
        path: '/HttpEntrypoint',
        name: 'entrypoint',
        defaults: ['_format' => 'json'],
        methods: ['POST'],
        priority: -1
    )
]
class HttpEntrypointController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] RequestDto $rd
    ): Response {
        $requestData = $rd->Data;
        $bindingName = key($requestData);
        $functionData = $requestData[$bindingName] ?? [];

        try {
            return $this->makeInternalHttpRequest($functionData);
        } catch (Throwable $e) {
            return new JsonResponse(new ResponseDto(
                ReturnValue: [
                    'status' => $e instanceof HttpExceptionInterface
                        ? $e->getStatusCode()
                        : Response::HTTP_INTERNAL_SERVER_ERROR,
                    'body' => $this->buildError($e),
                ],
            ));
        }
    }

    /**
     * @param array<string, mixed> $functionData
     * @return Response
     */
    private function makeInternalHttpRequest(
        array $functionData,
    ): Response {
        $url = $functionData['Url'] ?? throw new RuntimeException('Could not determine Url');
        $method = $functionData['Method'] ?? throw new RuntimeException('Could not determine Method');
        $body = json_decode($functionData['Body'] ?? '{}', true);
        $headers = array_map(fn($val) => current($val), $functionData['Headers'] ?? []);

        $this->logger->info(sprintf('Making internal %s request to %s', $method, $url));

        $request = Request::create(
            uri: $url,
            content: $body,
            method: $method,
        );
        $request->headers->add($headers);

        $kernel = new Kernel(
            $this->getParameter('kernel.environment'),
            $this->getParameter('kernel.debug')
        );

        return $kernel->handle($request, HttpKernelInterface::SUB_REQUEST, false);
    }

    /**
     * @param Throwable $e
     * @return array<string, mixed>
     */
    private function buildError(Throwable $e): array
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);

        if ($this->getParameter('kernel.debug') === true) {
            return [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'code' => $e->getCode(),
            ];
        }

        return [
            'message' => 'An unexpected error occurred'
        ];
    }
}
