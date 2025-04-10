<?php

namespace WandersonBarradas\BancoDoBrasil\Utils;

use Carbon\Carbon;

class FormatHelper
{
    /**
     * Formata um valor monetário para o padrão da API.
     *
     * @param float|string $valor
     * @return string
     */
    public static function formatarValor($valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    /**
     * Formata uma data para o padrão da API (DD.MM.YYYY).
     *
     * @param string $data
     * @return string
     */
    public static function formatarData(string $data): string
    {
        return Carbon::parse($data)->format('d.m.Y');
    }

    /**
     * Gera um número de título de cliente único.
     *
     * @param string $convenio
     * @return string
     */
    public static function gerarNumeroTituloCliente(string $convenio): string
    {
        $hash = self::gerarNumeroAleatorio(10);

        // Formata o número conforme padrão do BB
        return "000" . $convenio . $hash;
    }

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
     * Valida um CPF.
     *
     * @param string $cpf
     * @return bool
     */
    public static function validarCPF(string $cpf): bool
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Calcula o primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += $cpf[$i] * (10 - $i);
        }
        $resto = $soma % 11;
        $dv1 = ($resto < 2) ? 0 : 11 - $resto;

        // Verifica o primeiro dígito verificador
        if ($cpf[9] != $dv1) {
            return false;
        }

        // Calcula o segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += $cpf[$i] * (11 - $i);
        }
        $resto = $soma % 11;
        $dv2 = ($resto < 2) ? 0 : 11 - $resto;

        // Verifica o segundo dígito verificador
        return $cpf[10] == $dv2;
    }

    /**
     * Valida um CNPJ.
     *
     * @param string $cnpj
     * @return bool
     */
    public static function validarCNPJ(string $cnpj): bool
    {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Verifica se tem 14 dígitos
        if (strlen($cnpj) != 14) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Calcula o primeiro dígito verificador
        $soma = 0;
        $multiplicador = 5;

        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
        }

        $resto = $soma % 11;
        $dv1 = ($resto < 2) ? 0 : 11 - $resto;

        // Verifica o primeiro dígito verificador
        if ($cnpj[12] != $dv1) {
            return false;
        }

        // Calcula o segundo dígito verificador
        $soma = 0;
        $multiplicador = 6;

        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
        }

        $resto = $soma % 11;
        $dv2 = ($resto < 2) ? 0 : 11 - $resto;

        // Verifica o segundo dígito verificador
        return $cnpj[13] == $dv2;
    }

    /**
     * Valida um documento (CPF ou CNPJ).
     *
     * @param string $documento
     * @return bool
     */
    public static function validarDocumento(string $documento): bool
    {
        // Remove caracteres não numéricos
        $documento = preg_replace('/[^0-9]/', '', $documento);

        // Verifica o tipo de documento pelo tamanho
        if (strlen($documento) === 11) {
            return self::validarCPF($documento);
        } elseif (strlen($documento) === 14) {
            return self::validarCNPJ($documento);
        }

        return false;
    }

    /**
     * Formata um CEP para o padrão XXXXX-XXX.
     *
     * @param string $cep
     * @return string
     */
    public static function formatarCEP(string $cep): string
    {
        // Remove caracteres não numéricos
        $cep = preg_replace('/[^0-9]/', '', $cep);

        // Formata o CEP
        if (strlen($cep) === 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5);
        }

        return $cep;
    }

    /**
     * Formata um número de telefone.
     *
     * @param string $telefone
     * @return string
     */
    public static function formatarTelefone(string $telefone): string
    {
        // Remove caracteres não numéricos
        $telefone = preg_replace('/[^0-9]/', '', $telefone);

        // Verifica o tamanho do telefone
        $tamanho = strlen($telefone);

        if ($tamanho === 10) {
            // Telefone fixo: (XX) XXXX-XXXX
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
        } elseif ($tamanho === 11) {
            // Celular: (XX) XXXXX-XXXX
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
        }

        return $telefone;
    }
}
