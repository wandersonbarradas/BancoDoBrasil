<?php

use SeuVendor\BancoDoBrasil\Facades\BancoDoBrasil;
use SeuVendor\BancoDoBrasil\Exceptions\BBApiException;
use SeuVendor\BancoDoBrasil\Exceptions\BBValidationException;

try {
    // Dados do boleto
    $boleto = [
        'valorOriginal' => 100.00,
        'dataVencimento' => '2023-12-31',
        'pagador' => [
            'tipoInscricao' => 1, // 1 = CPF, 2 = CNPJ
            'numeroInscricao' => '12345678909',
            'nome' => 'João da Silva',
            'endereco' => 'Rua das Flores, 123',
            'cep' => '12345678',
            'cidade' => 'São Paulo',
            'bairro' => 'Centro',
            'uf' => 'SP',
            'telefone' => '11999999999'
        ],
        'descricaoTituloCliente' => 'Pagamento referente à fatura #12345'
    ];
    
    // Cria o boleto
    $resultado = BancoDoBrasil::criarBoleto($boleto);
    
    // Exibe o resultado
    echo "Boleto criado com sucesso!\n";
    echo "Número do título: " . $resultado['numeroTituloCliente'] . "\n";
    echo "Código de barras: " . $resultado['codigoBarraNumerico'] . "\n";
    echo "Linha digitável: " . $resultado['linhaDigitavel'] . "\n";
    
    // Obtém o PDF do boleto
    $pdf = BancoDoBrasil::obterPdfBoleto($resultado['numero']);
    
    if ($pdf['success']) {
        // Salva o PDF em disco
        file_put_contents('boleto.pdf', $pdf['data']);
        echo "PDF do boleto salvo como 'boleto.pdf'\n";
    }
    
} catch (BBValidationException $e) {
    echo "Erro de validação: " . $e->getMessage() . "\n";
} catch (BBApiException $e) {
    echo "Erro na API do BB: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    
    if ($e->getErrors()) {
        echo "Detalhes do erro:\n";
        print_r($e->getErrors());
    }
} catch (Exception $e) {
    echo "Erro desconhecido: " . $e->getMessage() . "\n";
} 