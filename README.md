# Integração com API de Cobrança do Banco do Brasil

Este pacote fornece uma integração simplificada com a API de Cobrança do Banco do Brasil, permitindo a criação, consulta, alteração e baixa de boletos registrados.

## Instalação

Você pode instalar o pacote via composer:

```bash
composer require wandersonbarradas/banco-do-brasil
```

## Configuração

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --provider="WandersonBarradas\BancoDoBrasil\BancoDoBrasilServiceProvider"
```

Adicione as seguintes variáveis ao seu .env:

## Uso Básico

```php
use WandersonBarradas\BancoDoBrasil\BancoDoBrasilAPI;

// Inicializar a API (ambiente sandbox por padrão)
$bbApi = new BancoDoBrasilAPI();

// Autenticar
$bbApi->authenticate();

// Listar boletos
$bbApi->setIndicadorSituacao('A');
$bbApi->setAgenciaBeneficiario(1234);
$bbApi->setContaBeneficiario(123456);
$boletos = $bbApi->listarBoletos();

// Criar um boleto
$boletoDados = [
    'numeroTituloBeneficiario' => '123ABC',
    'dataEmissao' => date('d.m.Y'),
    'dataVencimento' => date('d.m.Y', strtotime('+30 days')),
    'valorOriginal' => 100.00,
    // ... mais campos ...
];
$novoBoleto = $bbApi->criarBoleto($boletoDados);

// Obter boleto específico
$boleto = $bbApi->obterBoleto('00031285570000000205');

// Baixar/cancelar boleto
$bbApi->baixarBoleto('00031285570000000205');
```

## Licença

MIT