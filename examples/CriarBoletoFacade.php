<?php

require __DIR__ . '/../vendor/autoload.php';

use WandersonBarradas\BancoDoBrasil\Facades\BancoDoBrasil;
use WandersonBarradas\BancoDoBrasil\Exceptions\BBApiException;
use WandersonBarradas\BancoDoBrasil\Exceptions\BBValidationException;

// Este exemplo assume que você está usando Laravel
// Ou que configurou manualmente a Facade

try {
    // Exemplo 1: Criar um boleto
    echo "Criando boleto...\n";

    // Dados do boleto
    $boletoDados = [
        'valorOriginal' => 100.00,
        'dataVencimento' => now()->addDays(5)->format('Y-m-d'),
        'pagador' => [
            'tipoInscricao' => 1, // 1 = CPF
            'numeroInscricao' => '12345678909', // CPF de Exemplo
            'nome' => 'Cliente de Exemplo',
            'endereco' => 'Rua de Exemplo, 123',
            'cep' => '12345678',
            'cidade' => 'São Paulo',
            'bairro' => 'Centro',
            'uf' => 'SP',
            'telefone' => '11999999999'
        ],
        'descricaoTituloCliente' => 'Exemplo de integração - Fatura #123'
    ];

    $resultado = BancoDoBrasil::criarBoleto($boletoDados);
    echo "Boleto criado com sucesso!\n";
    echo "Número: " . $resultado['numero'] . "\n";
    echo "Linha digitável: " . $resultado['linhaDigitavel'] . "\n";
    echo "Código de barras: " . $resultado['codigoBarraNumerico'] . "\n";

    $numeroBoletoCriado = $resultado['numero'];

    // Exemplo 2: Consultar o boleto criado
    echo "\nConsultando boleto...\n";
    $boletoDados = BancoDoBrasil::obterBoleto($numeroBoletoCriado);
    echo "Boleto encontrado: " . $boletoDados['numeroTituloCedenteCobranca'] . "\n";
    echo "Valor: R$ " . $boletoDados['valorOriginalTituloCobranca'] . "\n";

    // Exemplo 3: Verificar pagamento
    echo "\nVerificando pagamento...\n";
    $pagamento = BancoDoBrasil::consultarPagamento($numeroBoletoCriado);
    echo "Status: " . $pagamento['status'] . "\n";
    echo "Pago: " . ($pagamento['pago'] ? 'Sim' : 'Não') . "\n";
    echo "Estado: " . $pagamento['descricao_estado'] . "\n";

    // Exemplo 4: Gerar PDF do boleto
    echo "\nGerando PDF do boleto...\n";

    // Dados do beneficiário
    $beneficiario = [
        'nome' => 'Empresa Exemplo LTDA',
        'documento' => '12345678000190',
        'endereco' => 'Rua Corporativa, 1000, Sala 123',
        'cep' => '01001001',
        'cidade' => 'São Paulo',
        'uf' => 'SP',
    ];

    // Dados do pagador
    $pagador = [
        'nome' => 'Cliente Exemplo',
        'documento' => '12345678909',
        'endereco' => 'Rua Teste, 123',
        'cep' => '01001001',
        'cidade' => 'São Paulo',
        'uf' => 'SP',
    ];

    // Salvar o PDF em um arquivo
    $pdf = BancoDoBrasil::gerarPdfBoleto(
        $numeroBoletoCriado,
        $beneficiario,
        $pagador,
        false,
        __DIR__ . "/boleto_{$numeroBoletoCriado}.pdf"
    );

    echo "PDF do boleto gerado com sucesso: " . $pdf['path'] . "\n";

    // Exemplo 5: Baixar o boleto (cancelar)
    echo "\nBaixando o boleto...\n";
    $baixa = BancoDoBrasil::baixarBoleto($numeroBoletoCriado);
    echo "Boleto baixado com sucesso!\n";
} catch (BBApiException $e) {
    echo "Erro na API do BB: " . $e->getMessage() . "\n";
    echo "Código HTTP: " . $e->getCode() . "\n";

    // Obter detalhes adicionais dos erros
    $erros = $e->getErrors();
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "Código: " . ($erro['codigo'] ?? 'N/A') . "\n";
            echo "Mensagem: " . ($erro['mensagem'] ?? $erro['textoMensagem'] ?? 'N/A') . "\n";
        }
    }
} catch (BBValidationException $e) {
    echo "Erro de validação: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Erro genérico: " . $e->getMessage() . "\n";
}
