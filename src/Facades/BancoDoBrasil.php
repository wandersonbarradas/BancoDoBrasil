<?php

namespace WandersonBarradas\BancoDoBrasil\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array criarBoleto(array $dados)
 * @method static array listarBoletos(array $filtros)
 * @method static array obterBoleto(string $id)
 * @method static array gerarPdfBoleto(string $id, array $beneficiario, array $pagador, bool $mostrar, string|null $caminho)
 * @method static array baixarBoleto(string $id)
 * @method static array alterarBoleto(string $id, array $dados)
 * @method static array consultarPagamento(string $id)
 * 
 * @see \WandersonBarradas\BancoDoBrasil\Services\BoletoService
 */
class BancoDoBrasil extends Facade
{
    /**
     * Retorna o nome do componente registrado.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'bb-boleto';
    }
}
