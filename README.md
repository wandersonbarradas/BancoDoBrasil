# Integração com API de Cobrança do Banco do Brasil

Este pacote fornece uma integração simplificada com a API de Cobrança do Banco do Brasil (BB), permitindo a criação, consulta, alteração e baixa de boletos registrados. Também inclui funcionalidade para geração de PDFs dos boletos através da biblioteca `eduardokum/laravel-boleto`.

## Requisitos

- PHP 8.0 ou superior
- Laravel 8.0 ou superior (para uso das Facades)

## Instalação

Você pode instalar o pacote via composer:

No seu arquivo composer.json adicione o seguinte conteúdo:

```json
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/wandersonbarradas/BancoDoBrasil.git"
        }
    ],
```
No terminal execulte:

```bash
composer require wandersonbarradas/banco-do-brasil:dev-master
```

## Configuração

### Laravel

Se estiver usando Laravel, publique o arquivo de configuração:

```bash
php artisan vendor:publish --provider="WandersonBarradas\BancoDoBrasil\BancoDoBrasilServiceProvider"
```

### Configuração Manual

Se não estiver usando Laravel, você precisará criar manualmente um arquivo de configuração seguindo a estrutura abaixo:

```php
return [
    'client_id' => env('BB_CLIENT_ID'),
    'client_secret' => env('BB_CLIENT_SECRET'),
    'developer_key' => env('BB_DEVELOPER_KEY'),
    'app_key' => env('BB_APP_KEY', 'gw-dev-app-key'),
    'sandbox' => env('BB_SANDBOX', true),
    'agencia' => env('BB_AGENCIA'),
    'conta' => env('BB_CONTA'),
    'convenio' => env('BB_CONVENIO'),
    'carteira' => env('BB_CARTEIRA', 17),
    'variacao_carteira' => env('BB_VARIACAO_CARTEIRA', 35),
    'cobranca_pagamento_pix' => env('BB_COBRANCA_PIX', false),

    // Configurações para geração de PDF do boleto
    'boleto_pdf' => [
        'logo_path' => env('BB_LOGO_PATH', null),
        'instrucoes_padrao' => [
            'Pagar até a data de vencimento',
            'Não receber após o vencimento',
        ],
        'multa' => env('BB_BOLETO_MULTA', 2.00),
        'juros' => env('BB_BOLETO_JUROS', 1.00),
        'juros_apos' => env('BB_BOLETO_JUROS_APOS', 0),
        'dias_protesto' => env('BB_BOLETO_DIAS_PROTESTO', 0),
    ],

    // Configurações de timeout e retry
    'timeout' => env('BB_TIMEOUT', 30),
    'connect_timeout' => env('BB_CONNECT_TIMEOUT', 5),
    'max_retries' => env('BB_MAX_RETRIES', 3),
    'retry_delay' => env('BB_RETRY_DELAY', 500),
    'debug' => env('BB_DEBUG', false),
];
```

### Variáveis de Ambiente

Adicione as seguintes variáveis ao seu arquivo `.env`:

BB_CLIENT_ID=seu_client_id
BB_CLIENT_SECRET=seu_client_secret
BB_DEVELOPER_KEY=sua_developer_key
BB_APP_KEY=gw-dev-app-key
BB_SANDBOX=true
BB_AGENCIA=1234
BB_CONTA=123456
BB_CONVENIO=seu_numero_convenio
BB_CARTEIRA=17
BB_VARIACAO_CARTEIRA=35
BB_COBRANCA_PIX=true
BB_DEBUG=false


## Uso Básico

### Forma 1: Usando Facades (Laravel)

```php
use WandersonBarradas\BancoDoBrasil\Facades\BancoDoBrasil;

// Criar boleto
$resultado = BancoDoBrasil::criarBoleto([
    'numeroTituloBeneficiario' => 'REF123',
    'dataVencimento' => '30.12.2023',
    'valorOriginal' => 150.00,
    // outros campos...
]);

// Consultar boleto
$boleto = BancoDoBrasil::obterBoleto('00031285570000000123');

// Verificar pagamento
$pagamento = BancoDoBrasil::consultarPagamento('00031285570000000123');
if ($pagamento['pago']) {
    echo "Boleto pago!";
}

// Gerar PDF
$pdf = BancoDoBrasil::gerarPdfBoleto(
    '00031285570000000123',
    $beneficiario, 
    $pagador
);
```

### Forma 2: Usando Classes de Serviço Diretamente

```php
use WandersonBarradas\BancoDoBrasil\Services\AuthService;
use WandersonBarradas\BancoDoBrasil\Services\BoletoService;

// Configuração
$config = [
    'client_id' => 'seu_client_id',
    'client_secret' => 'seu_client_secret',
    'developer_key' => 'sua_developer_key',
    'sandbox' => true,
    // outros campos de configuração...
];

// Criar serviços
$authService = new AuthService(
    $config['client_id'],
    $config['client_secret'],
    $config['developer_key'],
    $config['sandbox'] ? 'sandbox' : 'production'
);

$boletoService = new BoletoService($authService, $config);

// Criar boleto
$resultado = $boletoService->criarBoleto([
    'numeroTituloBeneficiario' => 'REF123',
    'dataVencimento' => '30.12.2023',
    'valorOriginal' => 150.00,
    // outros campos...
]);

// Consultar pagamento
$pagamento = $boletoService->consultarPagamento('00031285570000000123');
```

## Métodos Disponíveis

### Criar Boleto

```php
// Usando Facade
$resultado = BancoDoBrasil::criarBoleto($dados);

// Usando serviço diretamente
$resultado = $boletoService->criarBoleto($dados);
```

### Listar Boletos

```php
// Usando Facade
$boletos = BancoDoBrasil::listarBoletos([
    'indicadorSituacao' => 'A', // A=Aberto, B=Baixado
    'dataInicio' => '01.01.2023',
    'dataFim' => '31.12.2023',
]);

// Usando serviço diretamente
$boletos = $boletoService->listarBoletos($filtros);
```

### Obter Boleto Específico

```php
// Usando Facade
$boleto = BancoDoBrasil::obterBoleto('00031285570000000123');

// Usando serviço diretamente
$boleto = $boletoService->obterBoleto('00031285570000000123');
```

### Consultar Pagamento

```php
// Usando Facade
$pagamento = BancoDoBrasil::consultarPagamento('00031285570000000123');

// Usando serviço diretamente
$pagamento = $boletoService->consultarPagamento('00031285570000000123');
```

### Baixar Boleto (Cancelar)

```php
// Usando Facade
$resultado = BancoDoBrasil::baixarBoleto('00031285570000000123');

// Usando serviço diretamente
$resultado = $boletoService->baixarBoleto('00031285570000000123');
```

### Alterar Boleto

```php
// Usando Facade
$resultado = BancoDoBrasil::alterarBoleto('00031285570000000123', [
    'dataVencimento' => '31.12.2023',
    'valorOriginal' => 200.00,
]);

// Usando serviço diretamente
$resultado = $boletoService->alterarBoleto('00031285570000000123', $alteracoes);
```

### Gerar PDF do Boleto

```php
// Usando Facade
$pdf = BancoDoBrasil::gerarPdfBoleto(
    '00031285570000000123',
    $beneficiario,
    $pagador,
    true, // mostrar no navegador
    null  // não salvar em arquivo
);

// Usando serviço diretamente
$pdf = $boletoService->gerarPdfBoleto(
    '00031285570000000123',
    $beneficiario,
    $pagador,
    false, // não mostrar no navegador
    'caminho/do/arquivo.pdf' // salvar neste caminho
);
```

## Status de Pagamento

O método `consultarPagamento` retorna um array com as seguintes informações:

```php
[
    'status' => 'PAGO', // ou 'NAO_PAGO'
    'pago' => true, // ou false (booleano)
    'codigo_estado' => 6, // código numérico do estado
    'descricao_estado' => 'LIQUIDADO', // descrição textual do estado
    'codigo_tipo_liquidacao' => 4, // código do tipo de liquidação
    'tipo_liquidacao' => 'PIX', // descrição do tipo de liquidação
    'data_pagamento' => '11.12.2023', // data de pagamento (quando disponível)
    'valor_pago' => 150.00, // valor efetivamente pago
    'valor_credito_cedente' => 149.50, // valor creditado ao cedente
    'dados_completos' => [...] // dados completos do boleto
]
```

### Códigos de Estado do Boleto

| Código | Descrição |
|--------|-----------|
| 1 | NORMAL |
| 2 | MOVIMENTO CARTORIO |
| 3 | EM CARTORIO |
| 4 | TITULO COM OCORRENCIA DE CARTORIO |
| 5 | PROTESTADO ELETRONICO |
| 6 | LIQUIDADO |
| 7 | BAIXADO |
| 8 | TITULO COM PENDENCIA DE CARTORIO |
| 9 | TITULO PROTESTADO MANUAL |
| 10 | TITULO BAIXADO/PAGO EM CARTORIO |
| 11 | TITULO LIQUIDADO/PROTESTADO |
| 12 | TITULO LIQUID/PGCRTO |
| 13 | TITULO PROTESTADO AGUARDANDO BAIXA |
| 14 | TITULO EM LIQUIDACAO |
| 15 | TITULO AGENDADO |
| 16 | TITULO CREDITADO |
| 17 | PAGO EM CHEQUE - AGUARD.LIQUIDACAO |
| 18 | PAGO PARCIALMENTE (considerado como não pago) |
| 19 | PAGO PARCIALMENTE CREDITADO (considerado como não pago) |
| 21 | TITULO AGENDADO COMPE |
| 80 | EM PROCESSAMENTO (ESTADO TRANSITÓRIO) |

### Códigos de Tipo de Liquidação

| Código | Descrição |
|--------|-----------|
| 1 | CAIXA |
| 2 | VIA COMPE |
| 3 | EM CARTORIO |
| 4 | PIX |
| 5 | TITULO EM LIQUIDACAO - ORIGEM AGE |
| 6 | TITULO EM LIQUIDACAO - PGT |
| 7 | BANCO POSTAL |
| 8 | TITULO LIQUIDADO VIA COMPE/STR |

## Tratamento de Erros

O pacote lança exceções específicas que você pode capturar para tratar erros:

```php
use WandersonBarradas\BancoDoBrasil\Exceptions\BBApiException;
use WandersonBarradas\BancoDoBrasil\Exceptions\BBValidationException;

try {
    $boleto = BancoDoBrasil::obterBoleto('00031285570000000123');
} catch (BBApiException $e) {
    echo "Erro na API do BB: " . $e->getMessage();
    echo "Código HTTP: " . $e->getCode();
    
    // Obter detalhes adicionais dos erros
    $erros = $e->getErrors();
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "Código: " . ($erro['codigo'] ?? 'N/A');
            echo "Mensagem: " . ($erro['mensagem'] ?? $erro['textoMensagem'] ?? 'N/A');
        }
    }
} catch (BBValidationException $e) {
    echo "Erro de validação: " . $e->getMessage();
} catch (\Exception $e) {
    echo "Erro genérico: " . $e->getMessage();
}
```

## Licença

Este pacote é open-source e está disponível sob a [licença MIT](LICENSE.md).
