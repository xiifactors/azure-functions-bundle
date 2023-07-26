<?php

declare(strict_types=1);

namespace XIIFactors\AzureFunctions\EventListener;

use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Converts the default azure functions response, for when
 * "enableForwardingHttpRequest" is set to true in host.json.
 *
 * Otherwise the clients will receive the default response
 * structure like so:
 *
 * <code>
 *   [
 *     'Outputs' => [...],
 *     'Logs' => [...],
 *     'ReturnValue' => [...],
 *   ]
 * </code>
 *
 * You can disable this behaviour by setting the request header
 * "X-Convert-Response" to "0".
 */
class ConvertResponseListener
{
    public const HEADER = 'X-Convert-Response';

    public const JSON_MIME_TYPES = [
        'application/json'
    ];

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        $request = $event->getRequest();

        try {
            $requestBody = $request->toArray();
        } catch (JsonException $e) {
            $requestBody = null;
        }

        // Ignore for requests sent with enableForwardingHttpRequest: false
        if (is_array($requestBody) && isset($requestBody['Data']) && isset($requestBody['Metadata'])) {
            return;
        }

        // Ignore if the special header is given
        if ($request->headers->get(self::HEADER) === '0') {
            return;
        }

        $response = $event->getResponse();

        // Ignore if the response is not a JSON type
        if (in_array($response->headers->get('Content-Type'), self::JSON_MIME_TYPES, true) === false) {
            return;
        }

        // Ignore if the response is not potentially json-decodable
        if (is_string($response->getContent()) === false) {
            return;
        }

        $content = json_decode($response->getContent(), true);

        if (isset($content['ReturnValue'])) {
            if (is_array($content['ReturnValue'])) {
                $response->setStatusCode($content['ReturnValue']['status'] ?? Response::HTTP_OK);
                $response->headers->replace($content['ReturnValue']['headers'] ?? []);
                $response->setContent($content['ReturnValue']['body'] ?? '');
            } else {
                $response->setContent($content['ReturnValue']);
            }
            $event->setResponse($response);
        }
    }
}
