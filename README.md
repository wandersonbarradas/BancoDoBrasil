# Integração com API de Cobrança do Banco do Brasil

Este pacote fornece uma integração fácil com a API de Cobrança do Banco do Brasil (BB).

## Instalação

```bash
composer require seu-vendor/banco-do-brasil
```

## Uso Básico

```php
use SeuVendor\BancoDoBrasil\BancoDoBrasilAPI;

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

## Configuração

Configure as seguintes variáveis de ambiente: 

## Licença

MIT