<?php

namespace WandersonBarradas\BancoDoBrasil\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;
use WandersonBarradas\BancoDoBrasil\Exceptions\BBApiException;
use WandersonBarradas\BancoDoBrasil\Exceptions\BBValidationException;
use WandersonBarradas\BancoDoBrasil\Utils\BoletoValidator;
use WandersonBarradas\BancoDoBrasil\Utils\FormatHelper;
use WandersonBarradas\BancoDoBrasil\Utils\LogHelper;

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
                if (isset($errors[0]['textoMensagem']) && $errors[0]['textoMensagem'] !== '') {
                    $message = $errors[0]['textoMensagem'];
                }
            } elseif (isset($errorData['errors']) && is_array($errorData['errors'])) {
                $errors = $errorData['errors'];
                if (isset($errors[0]['message']) && $errors[0]['message'] !== '') {
                    $message = $errors[0]['message'];
                }
            } elseif (isset($errorData['message']) && $errorData['message'] !== '') {
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
        $dadosPadrao['numeroTituloBeneficiario'] = $dados['numeroTituloBeneficiario'] ?? Str::upper(Str::random(15));

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

    /**
     * Gera um PDF do boleto utilizando a biblioteca laravel-boleto.
     * 
     * Este método obtém os dados do boleto via API do Banco do Brasil
     * e utiliza a biblioteca eduardokum/laravel-boleto para gerar
     * um arquivo PDF com o layout completo do boleto.
     *
     * @param string $id ID do boleto no Banco do Brasil
     * @param array $beneficiario Dados do beneficiário (cedente)
     * @param array $pagador Dados do pagador (sacado)
     * @param bool $mostrar Se true, exibe o boleto no navegador. Se false, força o download
     * @param string|null $caminho Caminho onde o boleto será salvo. Se null, retorna o conteúdo binário
     * @return array Dados do PDF do boleto
     * @throws BBApiException|BBValidationException
     */
    public function gerarPdfBoleto(
        string $id,
        array $beneficiario,
        array $pagador,
        bool $mostrar = true,
        string $caminho = null
    ): array {
        try {
            // Passo 1: Obter dados do boleto na API do BB
            $dadosBoleto = $this->obterBoleto($id);

            if (!isset($dadosBoleto['valorOriginal']) || !isset($dadosBoleto['linhaDigitavel'])) {
                throw new BBValidationException("Dados insuficientes para geração do boleto PDF");
            }

            // Passo 2: Configurar os dados do beneficiário e pagador
            $beneficiarioPessoa = new \Eduardokum\LaravelBoleto\Pessoa([
                'nome'      => $beneficiario['nome'] ?? 'Nome do Beneficiário',
                'documento' => $beneficiario['documento'] ?? '',
                'endereco'  => $beneficiario['endereco'] ?? '',
                'cep'       => $beneficiario['cep'] ?? '',
                'cidade'    => $beneficiario['cidade'] ?? '',
                'uf'        => $beneficiario['uf'] ?? '',
            ]);

            $pagadorPessoa = new \Eduardokum\LaravelBoleto\Pessoa([
                'nome'      => $pagador['nome'] ?? 'Nome do Pagador',
                'documento' => $pagador['documento'] ?? '',
                'endereco'  => $pagador['endereco'] ?? '',
                'cep'       => $pagador['cep'] ?? '',
                'cidade'    => $pagador['cidade'] ?? '',
                'uf'        => $pagador['uf'] ?? '',
            ]);

            // Converte a data no formato DD.MM.YYYY para um objeto Carbon
            $dataVencimento = Carbon::createFromFormat('d.m.Y', $dadosBoleto['dataVencimento']);

            // Configura o boleto do Banco do Brasil
            $boleto = new \Eduardokum\LaravelBoleto\Boleto\Banco\Bb([
                'logo'                   => $this->config['boleto_pdf']['logo_path'] ?? null,
                'dataVencimento'         => $dataVencimento,
                'valor'                  => $dadosBoleto['valorOriginal'],
                'numeroDocumento'        => $dadosBoleto['numeroTituloCliente'] ?? '',
                'nossoNumero'            => $dadosBoleto['numeroTituloCliente'] ?? '',
                'carteira'               => $dadosBoleto['numeroCarteira'] ?? $this->config['carteira'],
                'convenio'               => $dadosBoleto['numeroConvenio'] ?? $this->config['convenio'],
                'agencia'                => $dadosBoleto['agenciaBeneficiario'] ?? '',
                'conta'                  => $dadosBoleto['contaBeneficiario'] ?? '',
                'multa'                  => $this->config['boleto_pdf']['multa'] ?? 0,
                'juros'                  => $this->config['boleto_pdf']['juros'] ?? 0,
                'jurosApos'              => $this->config['boleto_pdf']['juros_apos'] ?? 0,
                'diasProtesto'           => $this->config['boleto_pdf']['dias_protesto'] ?? 0,
                'aceite'                 => $dadosBoleto['codigoAceite'] ?? 'N',
                'especieDoc'             => $dadosBoleto['codigoTipoTitulo'] ?? 'DM',
                'beneficiario'           => $beneficiarioPessoa,
                'pagador'                => $pagadorPessoa,
                'linhaDigitavel'         => $dadosBoleto['linhaDigitavel'] ?? '',
                'codigoBarras'           => $dadosBoleto['codigoBarraNumerico'] ?? '',
                'descricaoDemonstrativo' => [$dadosBoleto['mensagemBeneficiario'] ?? 'Boleto gerado via API do Banco do Brasil'],
                'instrucoes'             => $this->config['boleto_pdf']['instrucoes_padrao'] ?? ['Pagar até a data do vencimento'],
            ]);

            // Passo 3: Gerar o PDF
            if (isset($dadosBoleto['indicadorPix']) && $dadosBoleto['indicadorPix'] === 'S' && !empty($dadosBoleto['qrCode'])) {
                // Adicionar QR Code PIX se disponível
                $boleto->setPixQrCode($dadosBoleto['qrCode']);
            }

            // Cria o renderizador de PDF
            $pdf = new \Eduardokum\LaravelBoleto\Boleto\Render\Pdf();
            $pdf->addBoleto($boleto);

            // Determina o que fazer com o PDF
            if ($caminho) {
                // Salva o PDF em um arquivo
                $pdf->gerarBoleto($mostrar ? 'I' : 'D', $caminho);

                return [
                    'success' => true,
                    'message' => 'PDF do boleto gerado com sucesso',
                    'path' => $caminho
                ];
            } else {
                // Retorna o conteúdo do PDF
                $content = $pdf->gerarBoleto($mostrar ? 'I' : 'S');

                return [
                    'success' => true,
                    'content_type' => 'application/pdf',
                    'data' => $content,
                    'is_binary' => true,
                    'filename' => "boleto_bb_{$id}.pdf"
                ];
            }
        } catch (\Exception $e) {
            if ($e instanceof BBApiException || $e instanceof BBValidationException) {
                throw $e;
            }

            throw new BBApiException(
                "Erro ao gerar PDF do boleto: " . $e->getMessage(),
                500,
                $e
            );
        }
    }
}
