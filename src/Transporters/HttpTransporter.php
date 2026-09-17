<?php

namespace Sveda\LaravelClient\Transporters;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Sveda\Client\Contracts\Transporter;
use Sveda\Client\Exceptions\AuthenticationException;
use Sveda\Client\Exceptions\ErrorException;
use Sveda\Client\Exceptions\TransporterException;
use Sveda\Client\Exceptions\UnserializableResponse;

final class HttpTransporter implements Transporter
{
    /**
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        private readonly string $baseUri,
        private readonly array $defaultHeaders = [],
        private readonly int $timeout = 30,
        private readonly int $connectTimeout = 5,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function requestJson(string $method, string $uri, array $payload = [], array $headers = []): array
    {
        $response = $this->pendingRequest($headers)
            ->send(strtoupper($method), $this->resolveUri($uri), match (strtoupper($method)) {
                'GET', 'DELETE' => ['query' => $payload],
                default => ['json' => $payload],
            });

        return $this->decodeResponse($response->status(), $response->body());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function requestStream(string $method, string $uri, array $payload = [], array $headers = []): StreamInterface
    {
        try {
            $response = $this->pendingRequest(array_merge($headers, [
                'Accept' => 'application/vnd.sveda.stream+json',
                'Content-Type' => 'application/json',
            ]))
                ->withOptions(['stream' => true])
                ->send(strtoupper($method), $this->resolveUri($uri), [
                    'json' => $payload,
                ]);
        } catch (ConnectionException|RequestException $e) {
            throw new TransporterException($e->getMessage(), null, $e);
        }

        if ($response->failed()) {
            throw new ErrorException(
                'Sveda stream request failed with status '.$response->status(),
                $response->status(),
            );
        }

        $psr = $response->toPsrResponse();
        $stream = $psr->getBody();

        if ($stream instanceof StreamInterface) {
            return $stream;
        }

        throw new TransporterException('Sveda stream response has no readable body.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $multipart
     * @param  array<string, string>  $headers
     */
    public function requestMultipart(string $method, string $uri, array $multipart, array $headers = []): ResponseInterface
    {
        try {
            $response = $this->pendingRequest(array_merge($headers, [
                'Accept' => 'application/json',
            ]))
                ->asMultipart()
                ->send(strtoupper($method), $this->resolveUri($uri), [
                    'multipart' => $multipart,
                ]);
        } catch (ConnectionException|RequestException $e) {
            throw new TransporterException($e->getMessage(), null, $e);
        }

        return $response->toPsrResponse();
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function pendingRequest(array $headers = []): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders(array_merge($this->defaultHeaders, $headers));
    }

    private function resolveUri(string $uri): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        return rtrim($this->baseUri, '/').'/'.ltrim($uri, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(int $status, string $body): array
    {
        if ($status === 401 || $status === 403) {
            throw new AuthenticationException('Sveda API authentication failed with status '.$status);
        }

        if ($status < 200 || $status >= 300) {
            $decoded = json_decode($body, true);

            throw new ErrorException(
                is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])
                    ? $decoded['message']
                    : 'Sveda API request failed with status '.$status,
                $status,
                is_array($decoded) ? $decoded : null,
            );
        }

        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new UnserializableResponse('Unable to decode Sveda API response as JSON.');
        }

        return $decoded;
    }
}
