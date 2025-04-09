<?php

namespace SeuVendor\BancoDoBrasil\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;
use SeuVendor\BancoDoBrasil\Exceptions\BBApiException;
use SeuVendor\BancoDoBrasil\Exceptions\BBValidationException;
use SeuVendor\BancoDoBrasil\Utils\BoletoValidator;
use SeuVendor\BancoDoBrasil\Utils\FormatHelper;
use SeuVendor\BancoDoBrasil\Utils\LogHelper;

class BoletoService
{
    private AuthService $authService;
    private array $config;
    private Client $httpClient;
    private BoletoValidator $validator;

    /**
     * Construtor do serviço de boletos.
     * 
     * @param AuthService $authService
     * @param array $config
     */
    public function __construct(AuthService $authService, array $config)
    {
        $this->authService = $authService;
        $this->config = $config;

        $this->httpClient = new Client([
            'timeout' => $config['timeout'] ?? 15,
            'connect_timeout' => $config['connect_timeout'] ?? 5,
        ]);

        $this->validator = new BoletoValidator();
    }

    /**
     * Envia uma requisição para a API do BB.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $queryParams
     * @param array $body
     * @return array
     * @throws BBApiException
     */
    private function sendRequest(
        string $method,
        string $endpoint,
        array $queryParams = [],
        array $body = []
    ): array {
        $url = $this->authService->getBaseApiUrl() . $endpoint;

        // Adiciona a chave do desenvolvedor aos parâmetros de consulta
        $queryParams[$this->authService->getAppKeyParam()] = $this->authService->getDeveloperKey();

        $options = [
            'headers' => $this->authService->getAuthHeaders(),
            'query' => $queryParams,
            'http_errors' => true,
        ];

        if (!empty($body)) {
            $options['json'] = $body;
        }

        // Registra a requisição se debug estiver ativado
        if ($this->config['debug'] ?? false) {
            LogHelper::logRequest($method, $url, $options);
        }

        $maxRetries = $this->config['max_retries'] ?? 3;
        $retryDelay = $this->config['retry_delay'] ?? 500; // milissegundos

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                $response = $this->httpClient->request($method, $url, $options);

                $contentType = $response->getHeaderLine('Content-Type');

                // Verifica se a resposta é um PDF
                if (strpos($contentType, 'application/pdf') !== false) {
                    return [
                        'success' => true,
                        'content_type' => $contentType,
                        'data' => (string)$response->getBody(),
                        'is_binary' => true,
                    ];
                }

                // Caso contrário, assume JSON
                $responseData = json_decode((string)$response->getBody(), true);

                // Log da resposta se debug estiver ativado
                if ($this->config['debug'] ?? false) {
                    LogHelper::logResponse($responseData);
                }

                return $responseData;
            } catch (ConnectException $e) {
                $lastException = $e;

                // Log do erro se debug estiver ativado
                if ($this->config['debug'] ?? false) {
                    LogHelper::logError('Erro de conexão', $e);
                }

                // Aguarda antes de tentar novamente
                usleep($retryDelay * 1000);
            } catch (RequestException $e) {
                $lastException = $e;

                // Log do erro se debug estiver ativado
                if ($this->config['debug'] ?? false) {
                    LogHelper::logError('Erro de requisição', $e);
                }

                // Verifica se é o erro de "Nosso Número já incluído"
                if ($e->hasResponse()) {
                    $statusCode = $e->getResponse()->getStatusCode();
                    $responseBody = (string)$e->getResponse()->getBody();
                    $errorData = json_decode($responseBody, true);

                    // Se for o erro específico de "Nosso Número já incluído", permite tentativa
                    if ($this->isNossoNumeroError($errorData)) {
                        usleep($retryDelay * 1000);
                        $attempt++;
                        continue;
                    }

                    // Para outros erros, lança a exceção imediatamente
                    throw $this->createApiException($e, $statusCode, $errorData);
                }

                // Se não tem resposta, lança a exceção
                throw new BBApiException(
                    "Erro na requisição: " . $e->getMessage(),
                    $e->getCode() ?: 500,
                    $e
                );
            }

            $attempt++;
        }

        // Se chegou aqui, todas as tentativas falharam
        if ($lastException) {
            if ($lastException instanceof RequestException && $lastException->hasResponse()) {
                $statusCode = $lastException->getResponse()->getStatusCode();
                $responseBody = (string)$lastException->getResponse()->getBody();
                $errorData = json_decode($responseBody, true);

                throw $this->createApiException($lastException, $statusCode, $errorData);
            }

            throw new BBApiException(
                "Excedido o número máximo de tentativas. Último erro: " . $lastException->getMessage(),
                $lastException->getCode() ?: 500,
                $lastException
            );
        }

        throw new BBApiException("Erro desconhecido após {$maxRetries} tentativas");
    }

    /**
     * Verifica se o erro é relacionado a "Nosso Número já incluído".
     *
     * @param array|null $errorData
     * @return bool
     */
    private function isNossoNumeroError(?array $errorData): bool
    {
        if (!$errorData || !isset($errorData['erros']) || !is_array($errorData['erros'])) {
            return false;
        }

        foreach ($errorData['erros'] as $error) {
            if (isset($error['codigo']) && $error['codigo'] === '4874915') {
                return true;
            }
        }

        return false;
    }

    /**
     * Cria uma exceção de API baseada na resposta de erro.
     *
     * @param \Throwable $exception
     * @param int $statusCode
     * @param array|null $errorData
     * @return BBApiException
     */
    private function createApiException(\Throwable $exception, int $statusCode, ?array $errorData): BBApiException
    {
        $errors = [];
        $message = "Erro na API do BB";

        if ($errorData) {
            if (isset($errorData['erros']) && is_array($errorData['erros'])) {
                $errors = $errorData['erros'];
                $message = $errors[0]['textoMensagem'] ?? $message;
            } elseif (isset($errorData['errors']) && is_array($errorData['errors'])) {
                $errors = $errorData['errors'];
                $message = $errors[0]['message'] ?? $message;
            } elseif (isset($errorData['message'])) {
                $message = $errorData['message'];
            }
        }

        return new BBApiException($message, $statusCode, $exception, $errors);
    }

    /**
     * Cria um boleto.
     *
     * @param array $dados
     * @return array
     * @throws BBValidationException|BBApiException
     */
    public function criarBoleto(array $dados): array
    {
        // Validação dos dados do boleto
        $this->validator->validarDadosBoleto($dados);

        // Dados padrão do boleto
        $dadosPadrao = [
            "numeroConvenio" => $this->config['convenio'],
            "numeroCarteira" => $this->config['carteira'],
            "numeroVariacaoCarteira" => $this->config['variacao_carteira'],
            "codigoModalidade" => 1,
            "dataEmissao" => Carbon::now()->format('d.m.Y'),
            "indicadorAceiteTituloVencido" => "N",
            "codigoAceite" => "A",
            "codigoTipoTitulo" => 2,
            "descricaoTipoTitulo" => "DM",
            "indicadorPermissaoRecebimentoParcial" => "N",
            "indicadorPix" => $this->config['cobranca_pagamento_pix'] ? "S" : "N"
        ];

        // Formata valores específicos
        if (isset($dados['valorOriginal'])) {
            $dados['valorOriginal'] = FormatHelper::formatarValor($dados['valorOriginal']);
        }

        if (isset($dados['dataVencimento'])) {
            $dados['dataVencimento'] = FormatHelper::formatarData($dados['dataVencimento']);
        }

        // Gera um número único para o boleto
        $dadosPadrao['numeroTituloCliente'] = FormatHelper::gerarNumeroTituloCliente($this->config['convenio']);
        $dadosPadrao['numeroTituloBeneficiario'] = $dados['numeroTituloBeneficiario'] ?? Str::random(10);

        // Remove campos opcionais não informados
        $dadosCompletos = array_merge($dadosPadrao, $dados);

        try {
            // Envia a requisição para criar o boleto
            $resposta = $this->sendRequest('POST', 'boletos', [], $dadosCompletos);

            return $resposta;
        } catch (BBApiException $e) {
            // Verifica se é o erro específico de "Nosso Número já incluído"
            if ($e->getCode() === 409 && $this->isNossoNumeroError($e->getErrors())) {
                // Este erro já é tratado no método sendRequest com retentativas automáticas
                throw new BBApiException(
                    "Não foi possível gerar um número único para o boleto após várias tentativas",
                    409,
                    $e,
                    $e->getErrors()
                );
            }

            throw $e;
        }
    }

    /**
     * Lista boletos registrados.
     *
     * @param array $filtros
     * @return array
     * @throws BBValidationException|BBApiException
     */
    public function listarBoletos(array $filtros): array
    {
        // Validação dos filtros obrigatórios
        if (!isset($filtros['indicadorSituacao'])) {
            throw new BBValidationException("O parâmetro 'indicadorSituacao' é obrigatório.");
        }

        if (!isset($filtros['agenciaBeneficiario'])) {
            throw new BBValidationException("O parâmetro 'agenciaBeneficiario' é obrigatório.");
        }

        if (!isset($filtros['contaBeneficiario'])) {
            throw new BBValidationException("O parâmetro 'contaBeneficiario' é obrigatório.");
        }

        return $this->sendRequest('GET', 'boletos', $filtros);
    }

    /**
     * Obtém detalhes de um boleto específico.
     *
     * @param string $id
     * @return array
     * @throws BBValidationException|BBApiException
     */
    public function obterBoleto(string $id): array
    {
        $queryParams = [
            'numeroConvenio' => $this->config['convenio']
        ];

        return $this->sendRequest('GET', "boletos/{$id}", $queryParams);
    }

    /**
     * Obtém o PDF de um boleto.
     *
     * @param string $id
     * @return array
     * @throws BBApiException
     */
    public function obterPdfBoleto(string $id): array
    {
        $queryParams = [
            'numeroConvenio' => $this->config['convenio'],
            'formato' => 'pdf'
        ];

        $resposta = $this->sendRequest('GET', "boletos/{$id}", $queryParams);

        // A resposta já está formatada corretamente pelo método sendRequest
        return $resposta;
    }

    /**
     * Baixa (cancela) um boleto.
     *
     * @param string $id
     * @return array
     * @throws BBApiException
     */
    public function baixarBoleto(string $id): array
    {
        $dados = [
            'numeroConvenio' => $this->config['convenio']
        ];

        return $this->sendRequest('POST', "boletos/{$id}/baixar", [], $dados);
    }

    /**
     * Altera um boleto existente.
     *
     * @param string $id
     * @param array $dados
     * @return array
     * @throws BBApiException
     */
    public function alterarBoleto(string $id, array $dados): array
    {
        // Formata valores específicos
        if (isset($dados['valorOriginal'])) {
            $dados['valorOriginal'] = FormatHelper::formatarValor($dados['valorOriginal']);
        }

        if (isset($dados['dataVencimento'])) {
            $dados['dataVencimento'] = FormatHelper::formatarData($dados['dataVencimento']);
        }

        return $this->sendRequest('PATCH', "boletos/{$id}", [], $dados);
    }

    /**
     * Consulta o status de pagamento de um boleto.
     *
     * @param string $id
     * @return array
     * @throws BBApiException
     */
    public function consultarPagamento(string $id): array
    {
        $queryParams = [
            'numeroConvenio' => $this->config['convenio']
        ];

        return $this->sendRequest('GET', "boletos/{$id}/pagamento", $queryParams);
    }
}
