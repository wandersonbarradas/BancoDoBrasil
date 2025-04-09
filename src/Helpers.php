<?php

namespace SeuVendor\BancoDoBrasil;

use Illuminate\Support\Str;

class Helpers
{
    /**
     * Obtém as configurações do convênio
     * 
     * @param string $convenio
     * @return array
     */
    public static function getConvenioSettings(string $convenio): array
    {
        // Implemente a lógica para obter as configurações do convênio
        // Esta função deve ser adaptada ao seu sistema

        // Exemplo:
        if ($convenio === 'banco_brasil') {
            return [
                'id_client' => env('BB_CLIENT_ID', ''),
                'chave_secreta' => env('BB_CLIENT_SECRET', ''),
                'id_desenvolvedor' => env('BB_APP_KEY', ''),
                'convenio' => env('BB_CONVENIO', ''),
                'numero_carteira' => env('BB_NUMERO_CARTEIRA', ''),
                'numero_variacao_carteira' => env('BB_NUMERO_VARIACAO_CARTEIRA', ''),
                'pagamento_pix' => env('BB_PAGAMENTO_PIX', false),
            ];
        }

        return [];
    }

    /**
     * Gera o número do título do cliente
     * @param string $convenio
     * @return string
     */
    public static function generateNumeroTituloCliente(string $convenio)
    {
        $hash = Helpers::gerarNumeroDe10Digitos();
        return "000" . $convenio . $hash;
    }

    public static function gerarNumeroDe10Digitos()
    {
        $numero = '';
        for ($i = 0; $i < 10; $i++) {
            $numero .= mt_rand(0, 9); // Gera um dígito aleatório de 0 a 9
        }
        return $numero;
    }
}
