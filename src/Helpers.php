<?php

namespace SeuVendor\BancoDoBrasil;

use Illuminate\Support\Str;
use Carbon\Carbon;

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
        // Geramos um número aleatório único com data e hora para evitar colisões
        $timestamp = Carbon::now()->format('YmdHis');
        $random = mt_rand(10000, 99999);
        $hash = substr($timestamp . $random, 0, 10);

        // Formata conforme padrão do BB
        return "000" . $convenio . $hash;
    }

    /**
     * Gera um número aleatório com a quantidade especificada de dígitos
     * 
     * @param int $digitos Quantidade de dígitos desejada
     * @return string Número gerado
     */
    public static function gerarNumeroAleatorio(int $digitos = 10): string
    {
        // Garante que o primeiro dígito não seja zero
        $primeiro = mt_rand(1, 9);

        // Gera os demais dígitos
        $resto = '';
        for ($i = 1; $i < $digitos; $i++) {
            $resto .= mt_rand(0, 9);
        }

        return $primeiro . $resto;
    }

    /**
     * Formata um valor para o padrão da API do BB
     * 
     * @param float $valor Valor a ser formatado
     * @return string Valor formatado
     */
    public static function formatarValor(float $valor): string
    {
        return number_format($valor, 2, '.', '');
    }

    /**
     * Formata uma data para o padrão da API do BB (DD.MM.YYYY)
     * 
     * @param string $data Data no formato Y-m-d ou outro compatível com Carbon
     * @return string Data formatada
     */
    public static function formatarData(string $data): string
    {
        return Carbon::parse($data)->format('d.m.Y');
    }

    /**
     * Valida um CPF ou CNPJ
     * 
     * @param string $documento CPF ou CNPJ
     * @return bool
     */
    public static function validarDocumento(string $documento): bool
    {
        // Remove caracteres não numéricos
        $documento = preg_replace('/[^0-9]/', '', $documento);

        // Valida CPF (11 dígitos)
        if (strlen($documento) === 11) {
            return self::validarCPF($documento);
        }

        // Valida CNPJ (14 dígitos)
        if (strlen($documento) === 14) {
            return self::validarCNPJ($documento);
        }

        return false;
    }

    /**
     * Valida um CPF
     * 
     * @param string $cpf
     * @return bool
     */
    private static function validarCPF(string $cpf): bool
    {
        // Implementação da validação de CPF
        // ...

        return true; // Implementar a lógica real de validação
    }

    /**
     * Valida um CNPJ
     * 
     * @param string $cnpj
     * @return bool
     */
    private static function validarCNPJ(string $cnpj): bool
    {
        // Implementação da validação de CNPJ
        // ...

        return true; // Implementar a lógica real de validação
    }
}
