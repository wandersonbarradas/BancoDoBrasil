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
            $cachedTokenJson = Redis::get('bb_api_access_token_' . $this->environment);
            if ($cachedTokenJson) {
                $cachedToken = json_decode($cachedTokenJson, true);
                if ($cachedToken && isset($cachedToken['access_token']) && isset($cachedToken['expires_at'])) {
                    // Verifica se o token ainda é válido usando Carbon
                    $now = Carbon::now();
                    $expiresAt = Carbon::createFromTimestamp($cachedToken['expires_at']);

                    // Adiciona uma margem de segurança de 5 minutos para expiração
                    if ($expiresAt->subMinutes(5)->gt($now)) {
                        $this->access_token = $cachedToken['access_token'];
                        $this->expires_in = $expiresAt->diffInSeconds($now);
                        return;
                    }
                }
            }
        } elseif ($this->cacheMethod === 'file') {
            // Utiliza um arquivo no disco 'public' do Laravel
            $cacheFilename = 'bb_api_access_token_' . $this->environment . '.json';
            if (Storage::disk('public')->exists($cacheFilename)) {
                $tokenData = Storage::disk('public')->get($cacheFilename);
                $cachedToken = json_decode($tokenData, true);
                if ($cachedToken && isset($cachedToken['access_token']) && isset($cachedToken['expires_at'])) {
                    // Verifica se o token ainda é válido usando Carbon
                    $now = Carbon::now();
                    $expiresAt = Carbon::createFromTimestamp($cachedToken['expires_at']);

                    // Adiciona uma margem de segurança de 5 minutos para expiração
                    if ($expiresAt->subMinutes(5)->gt($now)) {
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
                Redis::setex('bb_api_access_token_' . $this->environment, $this->expires_in, json_encode($cachedToken));
            } elseif ($this->cacheMethod === 'file') {
                // Armazena no arquivo no disco 'public', criando o arquivo se não existir
                $tokenData = json_encode($cachedToken);
                Storage::disk('public')->put('bb_api_access_token_' . $this->environment . '.json', $tokenData);
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
        // Garante que temos um token de acesso válido
        $this->authenticate();
        $url = $this->base_api_url . $endpoint;

        // Adiciona a chave de aplicação aos parâmetros de consulta
        $query_params[$this->app_key] = $this->gw_app_key;

        $headers = $this->getAuthHeaders();

        $options = [
            'headers' => $headers,
            'query' => $query_params,
            'http_errors' => true, // Mantém habilitado para capturarmos exceções
        ];

        if (!empty($body)) {
            $options['json'] = $body;
        }

        // Log da requisição para debugging se necessário
        $this->logRequest($method, $url, $options);

        try {
            $response = $this->http_client->request($method, $url, $options);

            // Decodifica a resposta JSON como objeto
            $response_data = json_decode((string)$response->getBody(), false);

            // Log da resposta para debugging
            $this->logResponse($response_data);

            return $response_data;
        } catch (ConnectException $e) {
            // Captura erro de conexão (connect_timeout)
            $this->logError('Erro de conexão', $e);
            throw new \Exception('Erro de conexão: Não foi possível se conectar com o Banco do Brasil', 500);
        } catch (RequestException $e) {
            $this->logError('Erro de requisição', $e);

            // Captura outros erros de timeout e requisição
            $handlerContext = $e->getHandlerContext();
            if (isset($handlerContext['errno']) && $handlerContext['errno'] === 28) {
                // O número de erro 28 indica timeout
                throw new \Exception('Timeout da requisição: ' . $e->getMessage(), 500);
            }

            // Obter o código de status HTTP
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
            $response_body = $e->hasResponse() ? (string)$e->getResponse()->getBody() : null;

            if (!$response_body) {
                throw new \Exception("Erro na requisição: " . $e->getMessage(), $statusCode);
            }

            $errorData = json_decode($response_body, true);

            // Tratamento aprimorado de erros
            if (isset($errorData['erros']) && is_array($errorData['erros'])) {
                $errorMessages = $this->formatErrorMessages($errorData['erros'], 'textoMensagem');

                // Caso específico de erro de "Nosso Número" já incluído
                if (isset($errorMessages[0]['codigo']) && $errorMessages[0]['codigo'] == '4874915') {
                    throw new \Exception(json_encode($errorMessages[0]), $statusCode);
                }

                throw new \Exception("Erro: " . $errorMessages[0]['mensagem'], $statusCode);
            } elseif (isset($errorData['errors']) && is_array($errorData['errors'])) {
                $errorMessages = $this->formatErrorMessages($errorData['errors'], 'message');
                $errorMessage = implode('; ', array_column($errorMessages, 'mensagem'));
                throw new \Exception("Erro {$statusCode}: {$errorMessage}", $statusCode);
            } elseif (isset($errorData['message'])) {
                throw new \Exception("Erro: {$errorData['message']}", $statusCode);
            } else {
                throw new \Exception("Erro na requisição: {$e->getMessage()}", $statusCode);
            }
        } catch (\Exception $e) {
            $this->logError('Erro geral', $e);
            throw $e;
        }
    }

    /**
     * Formata as mensagens de erro para um formato padronizado
     * 
     * @param array $errors Array com os erros retornados pela API
     * @param string $messageKey Chave onde está a mensagem de erro
     * @return array
     */
    private function formatErrorMessages(array $errors, string $messageKey): array
    {
        $formattedErrors = [];

        foreach ($errors as $key => $error) {
            $formattedError = ['mensagem' => 'Erro desconhecido'];

            if (isset($error[$messageKey])) {
                $formattedError['mensagem'] = $error[$messageKey];
            } elseif (isset($error['mensagem'])) {
                $formattedError['mensagem'] = $error['mensagem'];
            }

            if (isset($error['codigo'])) {
                $formattedError['codigo'] = $error['codigo'];
            }

            $formattedErrors[$key] = $formattedError;
        }

        return $formattedErrors;
    }

    /**
     * Registra informações da requisição para debug
     * 
     * @param string $method
     * @param string $url
     * @param array $options
     * @return void
     */
    private function logRequest(string $method, string $url, array $options): void
    {
        if (env('BB_API_DEBUG', false)) {
            // Copia as opções para não modificar o original
            $logOptions = $options;

            // Remove ou mascara informações sensíveis
            if (isset($logOptions['headers']['Authorization'])) {
                $logOptions['headers']['Authorization'] = 'Bearer [REDACTED]';
            }

            $logData = [
                'timestamp' => Carbon::now()->toIso8601String(),
                'method' => $method,
                'url' => $url,
                'options' => $logOptions
            ];

            // Salva o log
            $this->saveLog('request', $logData);
        }
    }

    /**
     * Registra informações da resposta para debug
     * 
     * @param object $response
     * @return void
     */
    private function logResponse($response): void
    {
        if (env('BB_API_DEBUG', false)) {
            $logData = [
                'timestamp' => Carbon::now()->toIso8601String(),
                'response' => json_decode(json_encode($response), true) // Converte para array
            ];

            // Salva o log
            $this->saveLog('response', $logData);
        }
    }

    /**
     * Registra informações de erro para debug
     * 
     * @param string $type
     * @param \Exception $exception
     * @return void
     */
    private function logError(string $type, \Exception $exception): void
    {
        if (env('BB_API_DEBUG', false)) {
            $logData = [
                'timestamp' => Carbon::now()->toIso8601String(),
                'type' => $type,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ];

            if ($exception instanceof RequestException && $exception->hasResponse()) {
                $logData['response'] = (string)$exception->getResponse()->getBody();
            }

            // Salva o log
            $this->saveLog('error', $logData);
        }
    }

    /**
     * Salva o log em arquivo
     * 
     * @param string $type
     * @param array $data
     * @return void
     */
    private function saveLog(string $type, array $data): void
    {
        $date = Carbon::now()->format('Y-m-d');
        $logDir = storage_path('logs/bb_api');

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = "{$logDir}/{$date}_{$type}.log";
        $logEntry = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);
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

        // Validações dos dados obrigatórios
        $camposObrigatorios = [
            'valorOriginal',
            'dataVencimento',
            'pagador'
        ];

        foreach ($camposObrigatorios as $campo) {
            if (!isset($boleto_data[$campo])) {
                throw new \Exception("Campo obrigatório '{$campo}' não informado");
            }
        }

        // Validações de pagador
        if (isset($boleto_data['pagador'])) {
            $camposPagador = [
                'tipoInscricao',
                'numeroInscricao',
                'nome',
                'endereco',
                'cep',
                'cidade',
                'bairro',
                'uf'
            ];

            foreach ($camposPagador as $campo) {
                if (!isset($boleto_data['pagador'][$campo])) {
                    throw new \Exception("Campo obrigatório 'pagador.{$campo}' não informado");
                }
            }
        }

        // Dados padrão com base nas configurações
        $default_data = [
            "numeroConvenio" => $bancoBrasil['convenio'],
            "numeroCarteira" => $bancoBrasil['numero_carteira'],
            "numeroVariacaoCarteira" => $bancoBrasil['numero_variacao_carteira'],
            "codigoModalidade" => 1,
            "dataEmissao" => Carbon::now()->format('d.m.Y'),
            "indicadorAceiteTituloVencido" => "N",
            "codigoAceite" => "A",
            "codigoTipoTitulo" => 2,
            "descricaoTipoTitulo" => "DM",
            "indicadorPermissaoRecebimentoParcial" => "N",
            "numeroTituloBeneficiario" => Str::random(10), // Identificador único
            "indicadorPix" => $bancoBrasil['pagamento_pix'] ? "S" : "N"
        ];

        // Formatação de valores específicos
        if (isset($boleto_data['valorOriginal'])) {
            // Garante que o formato está correto, com 2 casas decimais e separador de decimal como ponto
            $boleto_data['valorOriginal'] = number_format((float)$boleto_data['valorOriginal'], 2, '.', '');
        }

        if (isset($boleto_data['dataVencimento'])) {
            // Converte para o formato esperado pela API (dd.mm.yyyy)
            $data = Carbon::parse($boleto_data['dataVencimento']);
            $boleto_data['dataVencimento'] = $data->format('d.m.Y');
        }

        $tentativas = 1;
        $success = false;
        $resposta = null;
        $ultimoErro = null;

        while ($tentativas <= 3 && !$success) {
            try {
                // Gera um número único para cada tentativa
                $default_data['numeroTituloCliente'] = Helpers::generateNumeroTituloCliente($this->numeroConvenio);

                $resposta = $this->sendRequest('POST', 'boletos', [], array_merge($default_data, $boleto_data));
                $success = true;
            } catch (\Exception $e) {
                $ultimoErro = $e;

                // Verifica se é o erro específico de "Nosso Número" já incluído
                if (Str::isJson($e->getMessage())) {
                    $errorJson = json_decode($e->getMessage());
                    if (isset($errorJson->codigo) && $errorJson->codigo === "4874915") {
                        // Se for o erro específico, tentamos novamente com outro número
                        if ($tentativas === 3) {
                            throw new \Exception($errorJson->mensagem ?? 'Nosso Número já incluído anteriormente.');
                        }
                    } else {
                        // Se for outro tipo de erro JSON, lançamos imediatamente
                        throw $e;
                    }
                } else {
                    // Se não for erro JSON, lançamos imediatamente
                    throw $e;
                }
            }
            $tentativas++;
        }

        if (!$success && $ultimoErro) {
            throw $ultimoErro;
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

    /**
     * Obtém a imagem de um boleto em formato PDF.
     *
     * @param string $id ID do boleto.
     * @return array Resposta contendo os dados do PDF e informações adicionais.
     * @throws \Exception
     */
    public function obterPdfBoleto(string $id): array
    {
        if (empty($this->getNumeroConvenio())) {
            throw new \Exception("O parâmetro 'numeroConvenio' é obrigatório.");
        }

        $queryParams = [
            'numeroConvenio' => $this->getNumeroConvenio(),
            'formato' => 'pdf'
        ];

        try {
            // Autenticação e preparação da requisição
            $this->authenticate();
            $url = $this->base_api_url . 'boletos/' . $id;

            // Adiciona a chave de aplicação aos parâmetros de consulta
            $queryParams[$this->app_key] = $this->gw_app_key;

            $headers = $this->getAuthHeaders();

            $options = [
                'headers' => $headers,
                'query' => $queryParams,
            ];

            // Requisição direta para obter o PDF
            $response = $this->http_client->request('GET', $url, $options);

            // Verificar o Content-Type da resposta
            $contentType = $response->getHeader('Content-Type');
            $contentType = !empty($contentType) ? $contentType[0] : '';

            if (strpos($contentType, 'application/pdf') !== false) {
                // É um PDF, retorna os dados binários
                return [
                    'success' => true,
                    'content_type' => $contentType,
                    'data' => (string)$response->getBody(),
                    'filename' => "boleto_{$id}.pdf"
                ];
            } else if (strpos($contentType, 'application/json') !== false) {
                // É um JSON, provavelmente um erro
                $response_data = json_decode((string)$response->getBody(), true);
                return [
                    'success' => false,
                    'content_type' => $contentType,
                    'error' => $response_data['erros'][0]['textoMensagem'] ?? 'Erro ao obter o PDF do boleto',
                    'data' => $response_data
                ];
            } else {
                // Outro tipo de conteúdo
                return [
                    'success' => false,
                    'content_type' => $contentType,
                    'error' => 'Tipo de conteúdo não esperado',
                    'data' => (string)$response->getBody()
                ];
            }
        } catch (\Exception $e) {
            throw new \Exception("Erro ao obter PDF do boleto: " . $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Obtém informações do status de pagamento de um boleto.
     *
     * @param string $id ID do boleto.
     * @return object Resposta da API.
     * @throws \Exception
     */
    public function consultarStatusPagamento(string $id): object
    {
        if (empty($this->getNumeroConvenio())) {
            throw new \Exception("O parâmetro 'numeroConvenio' é obrigatório.");
        }

        $queryParams = [
            'numeroConvenio' => $this->getNumeroConvenio()
        ];

        return $this->sendRequest('GET', 'boletos/' . $id . '/pagamento', $queryParams);
    }
}
