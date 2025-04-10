<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ambiente de execução
    |--------------------------------------------------------------------------
    |
    | Especifique qual ambiente você está utilizando: 'sandbox' ou 'production'
    |
    */
    'environment' => env('BB_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Credenciais de Acesso
    |--------------------------------------------------------------------------
    |
    | Informações necessárias para autenticação com a API
    |
    */
    'client_id' => env('BB_CLIENT_ID', ''),
    'client_secret' => env('BB_CLIENT_SECRET', ''),
    'developer_key' => env('BB_DEVELOPER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Informações do Convênio e Conta
    |--------------------------------------------------------------------------
    |
    | Dados da conta e convênio para geração de boletos
    |
    */
    'convenio' => env('BB_CONVENIO', ''),
    'carteira' => env('BB_CARTEIRA', '17'),
    'variacao_carteira' => env('BB_VARIACAO_CARTEIRA', '35'),
    'agencia' => env('BB_AGENCIA', ''),
    'conta' => env('BB_CONTA', ''),

    /*
    |--------------------------------------------------------------------------
    | Opções de Pagamento
    |--------------------------------------------------------------------------
    |
    | Configurações adicionais para os boletos gerados
    |
    */
    'cobranca_pagamento_pix' => env('BB_PAGAMENTO_PIX', true),

    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema
    |--------------------------------------------------------------------------
    |
    | Configurações para o funcionamento do pacote
    |
    */
    'cache_method' => env('BB_CACHE_METHOD', 'file'), // 'file', 'redis'
    'debug' => env('BB_API_DEBUG', false),
    'timeout' => env('BB_API_TIMEOUT', 15),
    'connect_timeout' => env('BB_API_CONNECT_TIMEOUT', 5),
    'max_retries' => env('BB_API_MAX_RETRIES', 3),
    'retry_delay' => env('BB_API_RETRY_DELAY', 500), // milissegundos

    /*
    |--------------------------------------------------------------------------
    | Configurações para geração de PDF do boleto
    |--------------------------------------------------------------------------
    |
    | Configurações usadas pela biblioteca laravel-boleto para geração de PDFs
    |
    */
    'boleto_pdf' => [
        'logo_path' => env('BB_BOLETO_LOGO_PATH', null), // Caminho para o logo do beneficiário no boleto
        'multa' => env('BB_BOLETO_MULTA', 0), // Percentual de multa
        'juros' => env('BB_BOLETO_JUROS', 0), // Percentual de juros por dia
        'juros_apos' => env('BB_BOLETO_JUROS_APOS', 0), // Dias após vencimento para cobrança de juros
        'dias_protesto' => env('BB_BOLETO_DIAS_PROTESTO', 0), // Dias para protesto
        'instrucoes_padrao' => [
            'Pagar até a data do vencimento',
            'Após o vencimento, entrar em contato com o beneficiário'
        ]
    ],
];
