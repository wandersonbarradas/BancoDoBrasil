<?php

namespace SeuVendor\BancoDoBrasil;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BancoDoBrasilAPI
{
    private string $client_id;
    private string $client_secret;
    private string $gw_app_key;
    private string $environment;
    private string $base_auth_url;
    private string $base_api_url;
    private ?string $access_token = null;
    private ?int $expires_in = null;
    private Client $http_client;
    private string $app_key;
    private string $cacheMethod;

    // Atributos para os parâmetros da listagem de boletos
    private ?string $indicadorSituacao = null;
    private ?int $contaCaucao = null;
    private ?int $agenciaBeneficiario = null;
    private ?int $contaBeneficiario = null;
    private ?int $carteiraConvenio = null;
    private ?int $variacaoCarteiraConvenio = null;
    private ?int $modalidadeCobranca = null;
    private ?int $cnpjPagador = null;
    private ?int $digitoCNPJPagador = null;
    private ?int $cpfPagador = null;
    private ?int $digitoCPFPagador = null;
    private ?string $dataInicioVencimento = null;
    private ?string $dataFimVencimento = null;
    private ?string $dataInicioRegistro = null;
    private ?string $dataFimRegistro = null;
    private ?string $dataInicioMovimento = null;
    private ?string $dataFimMovimento = null;
    private ?int $codigoEstadoTituloCobranca = null;
    private ?string $boletoVencido = null;
    private ?int $indice = null;

    // Atributos para os parâmetros de obtenção de boletos
    private ?int $numeroConvenio = null;

    /**
     * Construtor da classe BancoDoBrasilAPI.
     *
     * @param string $environment O ambiente a ser utilizado: 'sandbox' ou 'production'.
     * @param string $cacheMethod Método de cache: 'redis' ou 'file'.
     * @throws \Exception
     */
    public function __construct(
        string $environment = 'sandbox',
        string $cacheMethod = 'redis'
    ) {
        $bancoBrasil = Helpers::getConvenioSettings('banco_brasil');
        $this->client_id = $bancoBrasil['id_client'];
        $this->client_secret = $bancoBrasil['chave_secreta'];
        $this->gw_app_key = $bancoBrasil['id_desenvolvedor'];
        $this->numeroConvenio = $bancoBrasil['convenio'];
        $this->environment = $environment;
        $this->cacheMethod = $cacheMethod;

        if ($environment === 'production') {
            $this->base_auth_url = 'https://oauth.bb.com.br/oauth/token';
            $this->base_api_url = 'https://api.bb.com.br/cobrancas/v2/';
            $this->app_key = 'gw-app-key';
        } else {
            $this->base_auth_url = 'https://oauth.hm.bb.com.br/oauth/token';
            $this->base_api_url = 'https://api.hm.bb.com.br/cobrancas/v2/';
            $this->app_key = 'gw-dev-app-key';
        }

        // Inicializa o cliente Guzzle com configurações padrão
        $this->http_client = new Client([
            'timeout' => 10,
            'connect_timeout' => 10,
        ]);
    }

    // Métodos set e get para os parâmetros

    public function setIndicadorSituacao(string $indicadorSituacao): void
    {
        $this->indicadorSituacao = $indicadorSituacao;
    }

    public function getIndicadorSituacao(): ?string
    {
        return $this->indicadorSituacao;
    }

    public function setContaCaucao(int $contaCaucao): void
    {
        $this->contaCaucao = $contaCaucao;
    }

    public function getContaCaucao(): ?int
    {
        return $this->contaCaucao;
    }

    public function setAgenciaBeneficiario(int $agenciaBeneficiario): void
    {
        $this->agenciaBeneficiario = $agenciaBeneficiario;
    }

    public function getAgenciaBeneficiario(): ?int
    {
        return $this->agenciaBeneficiario;
    }

    public function setContaBeneficiario(int $contaBeneficiario): void
    {
        $this->contaBeneficiario = $contaBeneficiario;
    }

    public function getContaBeneficiario(): ?int
    {
        return $this->contaBeneficiario;
    }

    public function setCarteiraConvenio(int $carteiraConvenio): void
    {
        $this->carteiraConvenio = $carteiraConvenio;
    }

    public function getCarteiraConvenio(): ?int
    {
        return $this->carteiraConvenio;
    }

    public function setVariacaoCarteiraConvenio(int $variacaoCarteiraConvenio): void
    {
        $this->variacaoCarteiraConvenio = $variacaoCarteiraConvenio;
    }

    public function getVariacaoCarteiraConvenio(): ?int
    {
        return $this->variacaoCarteiraConvenio;
    }

    public function setModalidadeCobranca(int $modalidadeCobranca): void
    {
        $this->modalidadeCobranca = $modalidadeCobranca;
    }

    public function getModalidadeCobranca(): ?int
    {
        return $this->modalidadeCobranca;
    }

    public function setCnpjPagador(int $cnpjPagador): void
    {
        $this->cnpjPagador = $cnpjPagador;
    }

    public function getCnpjPagador(): ?int
    {
        return $this->cnpjPagador;
    }

    public function setDigitoCNPJPagador(int $digitoCNPJPagador): void
    {
        $this->digitoCNPJPagador = $digitoCNPJPagador;
    }

    public function getDigitoCNPJPagador(): ?int
    {
        return $this->digitoCNPJPagador;
    }

    public function setCpfPagador(int $cpfPagador): void
    {
        $this->cpfPagador = $cpfPagador;
    }

    public function getCpfPagador(): ?int
    {
        return $this->cpfPagador;
    }

    public function setDigitoCPFPagador(int $digitoCPFPagador): void
    {
        $this->digitoCPFPagador = $digitoCPFPagador;
    }

    public function getDigitoCPFPagador(): ?int
    {
        return $this->digitoCPFPagador;
    }

    public function setDataInicioVencimento(string $dataInicioVencimento): void
    {
        $this->dataInicioVencimento = $dataInicioVencimento;
    }

    public function getDataInicioVencimento(): ?string
    {
        return $this->dataInicioVencimento;
    }

    public function setDataFimVencimento(string $dataFimVencimento): void
    {
        $this->dataFimVencimento = $dataFimVencimento;
    }

    public function getDataFimVencimento(): ?string
    {
        return $this->dataFimVencimento;
    }

    public function setDataInicioRegistro(string $dataInicioRegistro): void
    {
        $this->dataInicioRegistro = $dataInicioRegistro;
    }

    public function getDataInicioRegistro(): ?string
    {
        return $this->dataInicioRegistro;
    }

    public function setDataFimRegistro(string $dataFimRegistro): void
    {
        $this->dataFimRegistro = $dataFimRegistro;
    }

    public function getDataFimRegistro(): ?string
    {
        return $this->dataFimRegistro;
    }

    public function setDataInicioMovimento(string $dataInicioMovimento): void
    {
        $this->dataInicioMovimento = $dataInicioMovimento;
    }

    public function getDataInicioMovimento(): ?string
    {
        return $this->dataInicioMovimento;
    }

    public function setDataFimMovimento(string $dataFimMovimento): void
    {
        $this->dataFimMovimento = $dataFimMovimento;
    }

    public function getDataFimMovimento(): ?string
    {
        return $this->dataFimMovimento;
    }

    public function setCodigoEstadoTituloCobranca(int $codigoEstadoTituloCobranca): void
    {
        $this->codigoEstadoTituloCobranca = $codigoEstadoTituloCobranca;
    }

    public function getCodigoEstadoTituloCobranca(): ?int
    {
        return $this->codigoEstadoTituloCobranca;
    }

    public function setBoletoVencido(string $boletoVencido): void
    {
        $this->boletoVencido = $boletoVencido;
    }

    public function getBoletoVencido(): ?string
    {
        return $this->boletoVencido;
    }

    public function setIndice(int $indice): void
    {
        $this->indice = $indice;
    }

    public function getIndice(): ?int
    {
        return $this->indice;
    }

    public function setNumeroConvenio(int $numeroConvenio): void
    {
        $this->numeroConvenio = $numeroConvenio;
    }

    public function getNumeroConvenio(): ?int
    {
        return $this->numeroConvenio;
    }

    /**
     * Realiza a autenticação com o webservice do Banco do Brasil e obtém o access_token.
     * Implementa um sistema de cache para evitar gerar tokens desnecessários.
     *
     * @throws \Exception
     */
    public function authenticate(): void
    {
        // Primeiro, tenta recuperar o token do cache
        if ($this->cacheMethod === 'redis') {
            // Utiliza comandos diretos no Redis
            $cachedTokenJson = Redis::get('bb_api_access_token');
            if ($cachedTokenJson) {
                $cachedToken = json_decode($cachedTokenJson, true);
                if ($cachedToken && isset($cachedToken['access_token']) && isset($cachedToken['expires_at'])) {
                    // Verifica se o token ainda é válido usando Carbon
                    $now = Carbon::now();
                    $expiresAt = Carbon::createFromTimestamp($cachedToken['expires_at']);
                    if ($expiresAt->gt($now)) {
                        $this->access_token = $cachedToken['access_token'];
                        $this->expires_in = $expiresAt->diffInSeconds($now);
                        return;
                    }
                }
            }
        } elseif ($this->cacheMethod === 'file') {
            // Utiliza um arquivo no disco 'public' do Laravel
            if (Storage::disk('public')->exists('bb_api_access_token.json')) {
                $tokenData = Storage::disk('public')->get('bb_api_access_token.json');
                $cachedToken = json_decode($tokenData, true);
                if ($cachedToken && isset($cachedToken['access_token']) && isset($cachedToken['expires_at'])) {
                    // Verifica se o token ainda é válido usando Carbon
                    $now = Carbon::now();
                    $expiresAt = Carbon::createFromTimestamp($cachedToken['expires_at']);
                    if ($expiresAt->gt($now)) {
                        $this->access_token = $cachedToken['access_token'];
                        $this->expires_in = $expiresAt->diffInSeconds($now);
                        return;
                    }
                }
            }
        }

        // Se não encontrou um token válido no cache, realiza a autenticação
        $url = $this->base_auth_url;

        // Dados para a requisição POST
        $data = [
            'grant_type' => 'client_credentials',
        ];

        // Cabeçalhos da requisição
        $auth = base64_encode($this->client_id . ':' . $this->client_secret);
        $headers = [
            'Authorization' => "Basic $auth",
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        try {
            $response = $this->http_client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data,
            ]);

            $response_data = json_decode((string)$response->getBody(), false);

            // Autenticação bem-sucedida
            $this->access_token = $response_data->access_token;
            $this->expires_in = $response_data->expires_in;

            // Calcula o timestamp de expiração usando Carbon
            $expiresAt = Carbon::now()->addSeconds($this->expires_in)->timestamp;

            // Armazena o token no cache
            $cachedToken = [
                'access_token' => $this->access_token,
                'expires_at' => $expiresAt,
            ];

            if ($this->cacheMethod === 'redis') {
                // Armazena no Redis com tempo de expiração
                Redis::setex('bb_api_access_token', $this->expires_in, json_encode($cachedToken));
            } elseif ($this->cacheMethod === 'file') {
                // Armazena no arquivo no disco 'public', criando o arquivo se não existir
                $tokenData = json_encode($cachedToken);
                Storage::disk('public')->put('bb_api_access_token.json', $tokenData);
            }
        } catch (RequestException $e) {
            $response_body = $e->hasResponse() ? (string)$e->getResponse()->getBody() : '';
            $response_data = json_decode($response_body, false);
            $error = $response_data->error ?? 'Erro desconhecido';
            $error_description = $response_data->error_description ?? $e->getMessage();

            throw new \Exception("Autenticação falhou: $error - $error_description");
        }
    }

    /**
     * Método geral para enviar requisições à API.
     *
     * @param string $method Método HTTP (GET, POST, PATCH, etc.).
     * @param string $endpoint Endpoint da API após a URL base.
     * @param array $query_params Parâmetros de consulta (query parameters).
     * @param array $body Dados a serem enviados no corpo da requisição.
     * @return object Resposta da API como objeto.
     * @throws \Exception
     */
    private function sendRequest(string $method, string $endpoint, array $query_params = [], array $body = []): object
    {
        $this->authenticate();
        $url = $this->base_api_url . $endpoint;

        // Adiciona a chave de aplicação aos parâmetros de consulta
        $query_params[$this->app_key] = $this->gw_app_key;

        $headers = $this->getAuthHeaders();

        $options = [
            'headers' => $headers,
            'query' => $query_params,
        ];

        if (!empty($body)) {
            $options['json'] = $body;
        }

        try {
            $response = $this->http_client->request($method, $url, $options);

            // Decodifica a resposta JSON como objeto
            $response_data = json_decode((string)$response->getBody(), false);

            return $response_data;
        } catch (ConnectException $e) {
            // Captura erro de conexão (connect_timeout)
            throw new \Exception('Erro de conexão: Não foi possível se conectar com o Banco do Brasil', 500);
        } catch (RequestException $e) {
            // Captura outros erros de timeout e requisição
            $handlerContext = $e->getHandlerContext();
            if (isset($handlerContext['errno']) && $handlerContext['errno'] === 28) {
                // O número de erro 28 indica timeout
                throw new \Exception('Timeout da requisição: ' . $e->getMessage(), 500);
            }
            // Obter o código de status HTTP
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $response_body = $e->hasResponse() ? (string)$e->getResponse()->getBody() : null;
            $errorData = json_decode($response_body, true);
            if (isset($errorData['erros']) && is_array($errorData['erros'])) {
                $errorMessages = [];
                foreach ($errorData['erros'] as $key => $error) {
                    if (isset($error['textoMensagem'])) {
                        $errorMessages[$key]['mensagem'] = $error['textoMensagem'];
                    } else if (isset($error['mensagem'])) {
                        $errorMessages[$key]['mensagem'] = $error['mensagem'];
                    } else {
                        $errorMessages[$key]['mensagem'] = 'Erro desconhecido';
                    }
                    if (isset($error['codigo'])) {
                        $errorMessages[$key]['codigo'] = $error['codigo'];
                    }
                }
                if ($errorMessages[0]['codigo'] == '4874915') {
                    throw new \Exception(json_encode($errorMessages[0]), $statusCode);
                }
                throw new \Exception("Erro :{$errorMessages[0]['mensagem']}", $statusCode);
            } elseif (isset($errorData['errors']) && is_array($errorData['errors'])) {
                $errorMessages = [];
                foreach ($errorData['errors'] as $error) {
                    $errorMessages[] = $error['message'] ?? 'Erro desconhecido';
                }
                $errorMessage = implode('; ', $errorMessages);
                throw new \Exception("Erro {$statusCode}: {$errorMessage}", $statusCode);
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
                throw new \Exception("Erro: {$errorMessage}", $statusCode);
            } else {
                $errorMessage = $e->getMessage();
                throw new \Exception("Erro na requisição: {$errorMessage}", $statusCode);
            }
        }
    }

    /**
     * Lista boletos registrados com critérios específicos.
     *
     * @param array $params Parâmetros opcionais de consulta.
     * @return object Resposta completa da API.
     * @throws \Exception
     */
    public function listarBoletos(array $params = []): object
    {
        if (!empty($params)) {
            // Se $params foi fornecido, utiliza apenas ele e descarta os atributos
            $queryParams = $params;
        } else {
            // Caso contrário, utiliza os métodos get para obter os valores dos atributos
            $queryParams = [];

            // Lista dos nomes dos parâmetros
            $paramNames = [
                'indicadorSituacao',
                'contaCaucao',
                'agenciaBeneficiario',
                'contaBeneficiario',
                'carteiraConvenio',
                'variacaoCarteiraConvenio',
                'modalidadeCobranca',
                'cnpjPagador',
                'digitoCNPJPagador',
                'cpfPagador',
                'digitoCPFPagador',
                'dataInicioVencimento',
                'dataFimVencimento',
                'dataInicioRegistro',
                'dataFimRegistro',
                'dataInicioMovimento',
                'dataFimMovimento',
                'codigoEstadoTituloCobranca',
                'boletoVencido',
                'indice',
            ];

            // Loop para adicionar apenas os parâmetros que não são nulos
            foreach ($paramNames as $paramName) {
                // Pegue a primeira letra do nome do atributo, deixe maiúscula e concatene com o restante
                $methodSuffix = ucfirst($paramName);
                // Concatene com 'get' para formar o nome do método
                $getterMethod = 'get' . $methodSuffix;
                // Verificar se o método existe
                if (method_exists($this, $getterMethod)) {
                    $value = $this->$getterMethod();
                    if (!empty($value)) {
                        $queryParams[$paramName] = $value;
                    }
                }
            }
        }

        // Verifica se os parâmetros obrigatórios foram informados
        if (!isset($queryParams['indicadorSituacao'])) {
            throw new \Exception("O parâmetro 'indicadorSituacao' é obrigatório.");
        }

        if (!isset($queryParams['agenciaBeneficiario'])) {
            throw new \Exception("O parâmetro 'agenciaBeneficiario' é obrigatório.");
        }

        if (!isset($queryParams['contaBeneficiario'])) {
            throw new \Exception("O parâmetro 'contaBeneficiario' é obrigatório.");
        }

        return $this->sendRequest('GET', 'boletos', $queryParams);
    }

    /**
     * Cria um boleto registrado.
     *
     * @param array $boleto_data Dados do boleto a ser criado.
     * @return object Resposta da API.
     * @throws \Exception
     */
    public function criarBoleto(array $boleto_data): object
    {
        $bancoBrasil = Helpers::getConvenioSettings('banco_brasil');
        $default_data = [
            "numeroConvenio" => $bancoBrasil['convenio'],
            "numeroCarteira" => $bancoBrasil['numero_carteira'],
            "numeroVariacaoCarteira" => $bancoBrasil['numero_variacao_carteira'],
            "codigoModalidade" => 1,
            "indicadorAceiteTituloVencido" => "N",
            "codigoAceite" => "A",
            "codigoTipoTitulo" => 2,
            "descricaoTipoTitulo" => "DM",
            "indicadorPermissaoRecebimentoParcial" => "N",
            "numeroTituloBeneficiario" => "0DABC-DSD-1", //TODO: Trocar por valor correto
            "indicadorPix" => $bancoBrasil['pagamento_pix'] ? "S" : "N"
        ];

        $tentativas = 1;
        $success = false;
        $resposta = null;

        while ($tentativas <= 3 && !$success) {
            try {
                $default_data['numeroTituloCliente'] = Helpers::generateNumeroTituloCliente($this->numeroConvenio);

                $resposta = $this->sendRequest('POST', 'boletos', [], array_merge($default_data, $boleto_data));
                $success = true;
            } catch (\Exception $e) {
                if (!Str::isJson($e->getMessage())) {
                    throw $e;
                }
                $errorJson = json_decode($e->getMessage());
                if (!isset($errorJson->codigo) || $errorJson->codigo !== "4874915") {
                    throw $e;
                }
                if ($tentativas === 3) {
                    throw new \Exception($errorJson->mensagem ?? 'Nosso Número já incluído anteriormente.');
                }
            }
            $tentativas++;
        }

        return $resposta;
    }

    /**
     * Altera um boleto registrado.
     *
     * @param string $id ID do boleto a ser alterado.
     * @param array $boleto_data Dados a serem atualizados no boleto.
     * @return object Resposta da API.
     * @throws \Exception
     */
    public function alterarBoleto(string $id, array $boleto_data): object
    {
        return $this->sendRequest('PATCH', 'boletos/' . $id, [], $boleto_data);
    }

    /**
     * Obtém informações de um boleto registrado.
     *
     * @param string $id ID do boleto.
     * @return object Resposta da API.
     * @throws \Exception
     */
    public function obterBoleto(string $id, array $params = []): object
    {
        if (!empty($params)) {
            // Se $params foi fornecido, utiliza apenas ele e descarta os atributos
            $queryParams = $params;
        } else {
            // Caso contrário, utiliza o método getNumeroConvenio para obter o valor de numeroConvenio
            $queryParams = [];
            if (!empty($this->getNumeroConvenio())) {
                $queryParams['numeroConvenio'] = $this->getNumeroConvenio();
            }
        }

        // Verifica se os parâmetros obrigatórios foram informados
        if (!isset($queryParams['numeroConvenio'])) {
            throw new \Exception("O parâmetro 'numeroConvenio' é obrigatório.");
        }

        return $this->sendRequest('GET', 'boletos/' . $id, $queryParams);
    }

    /**
     * Baixa ou cancela um boleto registrado.
     *
     * @param string $id ID do boleto.
     * @return object Resposta da API.
     * @throws \Exception
     */
    public function baixarBoleto(string $id): object
    {
        $data = ['numeroConvenio' => $this->numeroConvenio];
        return $this->sendRequest('POST', 'boletos/' . $id . '/baixar', [], $data);
    }

    /**
     * Obtém os cabeçalhos de autenticação para as requisições.
     *
     * @return array
     */
    private function getAuthHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->access_token}",
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Obtém o access_token atual.
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->access_token;
    }

    /**
     * Obtém o ambiente atual.
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Obtém a URL base de autenticação.
     *
     * @return string
     */
    public function getBaseAuthUrl(): string
    {
        return $this->base_auth_url;
    }

    /**
     * Obtém a URL base da API.
     *
     * @return string
     */
    public function getBaseApiUrl(): string
    {
        return $this->base_api_url;
    }
}
