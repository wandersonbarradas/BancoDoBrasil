<?php

namespace WandersonBarradas\BancoDoBrasil\Utils;

use WandersonBarradas\BancoDoBrasil\Exceptions\BBValidationException;

class BoletoValidator
{
    /**
     * Valida os dados de um boleto antes de criar.
     *
     * @param array $dados
     * @throws BBValidationException
     */
    public function validarDadosBoleto(array $dados): void
    {
        // Campos obrigatórios
        $camposObrigatorios = [
            'valorOriginal',
            'dataVencimento',
            'pagador'
        ];

        foreach ($camposObrigatorios as $campo) {
            if (!isset($dados[$campo])) {
                throw new BBValidationException("Campo obrigatório '{$campo}' não informado");
            }
        }

        // Valida o valor do boleto
        if (isset($dados['valorOriginal'])) {
            $valor = (float) $dados['valorOriginal'];
            if ($valor <= 0) {
                throw new BBValidationException("O valor do boleto deve ser maior que zero");
            }
        }

        // Valida a data de vencimento
        if (isset($dados['dataVencimento'])) {
            try {
                $dataVencimento = \Carbon\Carbon::parse($dados['dataVencimento']);
                $hoje = \Carbon\Carbon::today();

                if ($dataVencimento->lt($hoje)) {
                    throw new BBValidationException("A data de vencimento não pode ser anterior à data atual");
                }
            } catch (\Exception $e) {
                throw new BBValidationException("Data de vencimento inválida: " . $e->getMessage());
            }
        }

        // Validações do pagador
        if (isset($dados['pagador'])) {
            $this->validarPagador($dados['pagador']);
        }
    }

    /**
     * Valida os dados do pagador.
     *
     * @param array $pagador
     * @throws BBValidationException
     */
    private function validarPagador(array $pagador): void
    {
        // Campos obrigatórios do pagador
        $camposPagador = [
            'tipoInscricao',
            'numeroInscricao',
            'nome',
            'endereco',
            'cep',
            'cidade',
            'bairro',
            'uf'
        ];

        foreach ($camposPagador as $campo) {
            if (!isset($pagador[$campo]) || empty($pagador[$campo])) {
                throw new BBValidationException("Campo obrigatório 'pagador.{$campo}' não informado ou vazio");
            }
        }

        // Valida o tipo de inscrição (1 = CPF, 2 = CNPJ)
        if (isset($pagador['tipoInscricao'])) {
            $tipoInscricao = $pagador['tipoInscricao'];
            if ($tipoInscricao != 1 && $tipoInscricao != 2) {
                throw new BBValidationException("O tipo de inscrição deve ser 1 (CPF) ou 2 (CNPJ)");
            }

            // Valida o número de inscrição
            if (isset($pagador['numeroInscricao'])) {
                $numeroInscricao = preg_replace('/[^0-9]/', '', $pagador['numeroInscricao']);

                if ($tipoInscricao == 1 && !FormatHelper::validarCPF($numeroInscricao)) {
                    throw new BBValidationException("CPF do pagador inválido");
                } elseif ($tipoInscricao == 2 && !FormatHelper::validarCNPJ($numeroInscricao)) {
                    throw new BBValidationException("CNPJ do pagador inválido");
                }
            }
        }

        // Valida CEP
        if (isset($pagador['cep'])) {
            $cep = preg_replace('/[^0-9]/', '', $pagador['cep']);
            if (strlen($cep) != 8) {
                throw new BBValidationException("CEP inválido");
            }
        }

        // Valida UF
        if (isset($pagador['uf'])) {
            $ufs = [
                'AC',
                'AL',
                'AP',
                'AM',
                'BA',
                'CE',
                'DF',
                'ES',
                'GO',
                'MA',
                'MT',
                'MS',
                'MG',
                'PA',
                'PB',
                'PR',
                'PE',
                'PI',
                'RJ',
                'RN',
                'RS',
                'RO',
                'RR',
                'SC',
                'SP',
                'SE',
                'TO'
            ];

            if (!in_array(strtoupper($pagador['uf']), $ufs)) {
                throw new BBValidationException("UF inválida");
            }
        }
    }
}
