<?php

namespace WandersonBarradas\BancoDoBrasil\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use WandersonBarradas\BancoDoBrasil\Exceptions\BBAuthException;

class AuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $developerKey;
    private string $environment;
    private string $cacheMethod;
    private string $baseAuthUrl;
    private string $baseApiUrl;
    private string $appKeyParam;

    private Client $httpClient;
    private ?string $accessToken = null;
    private ?int $expiresIn = null;

    /**
     * Construtor do serviço de autenticação.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $developerKey
     * @param string $environment
     * @param string $cacheMethod
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $developerKey,
        string $environment = 'sandbox',
        string $cacheMethod = 'file'
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->developerKey = $developerKey;
        $this->environment = $environment;
        $this->cacheMethod = $cacheMethod;

        // Define URLs baseadas no ambiente
        if ($environment === 'production') {
            $this->baseAuthUrl = 'https://oauth.bb.com.br/oauth/token';
            $this->baseApiUrl = 'https://api.bb.com.br/cobrancas/v2/';
            $this->appKeyParam = 'gw-app-key';
        } else {
            $this->baseAuthUrl = 'https://oauth.hm.bb.com.br/oauth/token';
            $this->baseApiUrl = 'https://api.hm.bb.com.br/cobrancas/v2/';
            $this->appKeyParam = 'gw-dev-app-key';
        }

        // Inicializa o cliente HTTP
        $this->httpClient = new Client([
            'timeout' => config('banco-do-brasil.timeout', 15),
            'connect_timeout' => config('banco-do-brasil.connect_timeout', 5),
        ]);
    }

    /**
     * Retorna o token de acesso atual ou obtém um novo.
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        // Tenta buscar do cache ou autentica novamente
        $cacheKey = "bb_api_token_{$this->environment}";

        if ($this->cacheMethod === 'redis' || $this->cacheMethod === 'file') {
            $tokenData = Cache::get($cacheKey);

            if ($tokenData && isset($tokenData['access_token']) && isset($tokenData['expires_at'])) {
                // Verifica se o token ainda é válido (com margem de segurança)
                $now = Carbon::now();
                $expiresAt = Carbon::createFromTimestamp($tokenData['expires_at']);

                if ($expiresAt->subMinutes(5)->gt($now)) {
                    $this->accessToken = $tokenData['access_token'];
                    $this->expiresIn = $expiresAt->diffInSeconds($now);
                    return $this->accessToken;
                }
            }
        }

        // Autentica e retorna o token
        $this->authenticate();
        return $this->accessToken;
    }

    /**
     * Realiza a autenticação e obtém um novo token de acesso.
     *
     * @return void
     * @throws BBAuthException
     */
    private function authenticate(): void
    {
        // Dados para a requisição de autenticação
        $data = [
            'grant_type' => 'client_credentials',
        ];

        // Cabeçalhos da requisição
        $auth = base64_encode("{$this->clientId}:{$this->clientSecret}");
        $headers = [
            'Authorization' => "Basic {$auth}",
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        try {
            $response = $this->httpClient->request('POST', $this->baseAuthUrl, [
                'headers' => $headers,
                'form_params' => $data,
            ]);

            $responseData = json_decode((string)$response->getBody(), true);

            // Verifica se o token foi retornado
            if (!isset($responseData['access_token']) || empty($responseData['access_token'])) {
                throw new BBAuthException('Token de acesso não retornado pelo servidor');
            }

            // Atualiza propriedades com os dados do token
            $this->accessToken = $responseData['access_token'];
            $this->expiresIn = $responseData['expires_in'] ?? 3600;

            // Calcula timestamp de expiração
            $expiresAt = Carbon::now()->addSeconds($this->expiresIn)->timestamp;

            // Armazena no cache
            $tokenData = [
                'access_token' => $this->accessToken,
                'expires_at' => $expiresAt,
            ];

            $cacheKey = "bb_api_token_{$this->environment}";
            Cache::put($cacheKey, $tokenData, Carbon::now()->addSeconds($this->expiresIn - 300)); // -5 minutos de margem

        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? (string)$e->getResponse()->getBody() : '';
            $responseData = json_decode($responseBody, true);

            $error = $responseData['error'] ?? 'erro_desconhecido';
            $errorDescription = $responseData['error_description'] ?? $e->getMessage();

            throw new BBAuthException("Falha na autenticação: {$error} - {$errorDescription}", $e->getCode(), $e);
        }
    }

    /**
     * Retorna a URL base da API.
     *
     * @return string
     */
    public function getBaseApiUrl(): string
    {
        return $this->baseApiUrl;
    }

    /**
     * Retorna o parâmetro de app key.
     *
     * @return string
     */
    public function getAppKeyParam(): string
    {
        return $this->appKeyParam;
    }

    /**
     * Retorna a chave de desenvolvedor.
     *
     * @return string
     */
    public function getDeveloperKey(): string
    {
        return $this->developerKey;
    }

    /**
     * Retorna os cabeçalhos de autenticação.
     *
     * @return array
     */
    public function getAuthHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->getAccessToken()}",
            'Content-Type' => 'application/json',
        ];
    }
}
