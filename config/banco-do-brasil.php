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
    | Informações do Convênio
    |--------------------------------------------------------------------------
    |
    | Dados do convênio para geração de boletos
    |
    */
    'convenio' => env('BB_CONVENIO', ''),
    'carteira' => env('BB_CARTEIRA', '17'),
    'variacao_carteira' => env('BB_VARIACAO_CARTEIRA', '35'),

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
];
