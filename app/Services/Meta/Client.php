<?php
namespace App\Services\Meta;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;

class Client {
    private HttpClient $http;
    private string $accessToken;
    private string $accountId;
    private string $apiVersion = 'v21.0';

    public function __construct(string $accessToken, string $accountId) {
        $this->accessToken = $accessToken;
        $this->accountId = $accountId;
        $this->http = new HttpClient([
            'base_uri' => "https://graph.facebook.com/{$this->apiVersion}/",
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function request(string $method, string $endpoint, array $params = []): array {
        $params['access_token'] = $this->accessToken;
        try {
            $response = $this->http->request($method, $endpoint, [
                $method === 'GET' ? 'query' : 'form_params' => $params,
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $body = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            $decoded = json_decode($body, true);
            return ['error' => $decoded['error']['message'] ?? $body];
        }
    }

    public function get(string $endpoint, array $params = []): array {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $params = []): array {
        return $this->request('POST', $endpoint, $params);
    }

    public function getAccountId(): string {
        return $this->accountId;
    }

    public function getAccount(): array {
        $fields = 'name,currency,account_status,balance,spend_cap,amount_spent';
        return $this->get($this->accountId, ['fields' => $fields]);
    }

    public function getPageInfo(): array {
        return $this->get($this->accountId . '/effective_objectives', ['limit' => 1]);
    }
}
