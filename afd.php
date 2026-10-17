<?php
/*
 * Simulador de AFD - Linguagens Formais e Automatos
 *
 * Uso:
 *   php afd.php                -> le a definicao pelo console
 *   php afd.php arquivo.txt    -> le a definicao de um arquivo
 */

// ---------- leitura ----------

function perguntar($texto)
{
    echo $texto;
    $linha = fgets(STDIN);
    if ($linha === false) {
        // fim da entrada (Ctrl+D ou arquivo redirecionado acabou)
        echo "\n";
        exit(0);
    }
    return trim($linha);
}

function separar($texto)
{
    $texto = trim($texto);
    if ($texto === '') {
        return array();
    }
    return preg_split('/\s+/', $texto);
}

function lerDoConsole()
{
    $def = array();
    $def['estados']  = separar(perguntar("Estados (separados por espaco): "));
    $def['alfabeto'] = separar(perguntar("Alfabeto (simbolos separados por espaco): "));
    $def['inicial']  = perguntar("Estado inicial: ");
    $def['finais']   = separar(perguntar("Estados de aceitacao: "));

    echo "Transicoes no formato: origem simbolo destino\n";
    echo "(linha em branco para terminar)\n";
    $def['trans'] = array();
    while (true) {
        $linha = perguntar("> ");
        if ($linha === '') {
            break;
        }
        $p = separar($linha);
        if (count($p) != 3) {
            echo "  linha ignorada, use: origem simbolo destino\n";
            continue;
        }
        $def['trans'][] = $p;
    }
    return $def;
}

function lerDoArquivo($caminho)
{
    if (!is_readable($caminho)) {
        fwrite(STDERR, "Nao consegui abrir o arquivo: $caminho\n");
        exit(1);
    }

    $def = array('estados' => array(), 'alfabeto' => array(), 'inicial' => '',
                 'finais' => array(), 'trans' => array());
    $naParteDeTransicoes = false;

    foreach (file($caminho) as $n => $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#') {
            continue;
        }

        if ($naParteDeTransicoes) {
            $p = separar($linha);
            if (count($p) != 3) {
                fwrite(STDERR, "Linha " . ($n + 1) . ": transicao mal formada\n");
                exit(1);
            }
            $def['trans'][] = $p;
            continue;
        }

        $partes = explode(':', $linha, 2);
        $chave = strtolower(trim($partes[0]));
        $valor = isset($partes[1]) ? $partes[1] : '';

        switch ($chave) {
            case 'estados':    $def['estados']  = separar($valor); break;
            case 'alfabeto':   $def['alfabeto'] = separar($valor); break;
            case 'inicial':    $def['inicial']  = trim($valor);    break;
            case 'finais':     $def['finais']   = separar($valor); break;
            case 'transicoes': $naParteDeTransicoes = true;        break;
            default:
                fwrite(STDERR, "Linha " . ($n + 1) . ": campo desconhecido '$chave'\n");
                exit(1);
        }
    }
    return $def;
}

// ---------- validacao ----------

// Devolve array(erros, delta). Se erros estiver vazio o AFD e valido.
function validar($def)
{
    $erros = array();
    $delta = array();

    $estados = $def['estados'];
    $alfabeto = $def['alfabeto'];

    if (count($estados) == 0) {
        $erros[] = "nenhum estado informado";
    }
    if (count($estados) != count(array_unique($estados))) {
        $erros[] = "ha estados repetidos";
    }

    if (count($alfabeto) == 0) {
        $erros[] = "alfabeto vazio";
    }
    if (count($alfabeto) != count(array_unique($alfabeto))) {
        $erros[] = "ha simbolos repetidos no alfabeto";
    }
    foreach ($alfabeto as $s) {
        if (strlen($s) != 1) {
            $erros[] = "simbolo '$s' invalido: cada simbolo deve ter 1 caractere";
        }
    }

    if (!in_array($def['inicial'], $estados, true)) {
        $erros[] = "estado inicial '" . $def['inicial'] . "' nao pertence a Q";
    }
    foreach ($def['finais'] as $f) {
        if (!in_array($f, $estados, true)) {
            $erros[] = "estado de aceitacao '$f' nao pertence a Q";
        }
    }

    foreach ($def['trans'] as $t) {
        list($o, $s, $d) = $t;

        if (!in_array($o, $estados, true)) {
            $erros[] = "transicao ($o, $s): origem '$o' nao existe";
            continue;
        }
        if (!in_array($s, $alfabeto, true)) {
            $erros[] = "transicao ($o, $s): simbolo '$s' fora do alfabeto";
            continue;
        }
        if (!in_array($d, $estados, true)) {
            $erros[] = "transicao ($o, $s): destino '$d' nao existe";
            continue;
        }

        if (isset($delta[$o][$s]) && $delta[$o][$s] !== $d) {
            $erros[] = "nao e deterministico: de $o com '$s' vai para "
                     . $delta[$o][$s] . " e tambem para $d";
        } else {
            $delta[$o][$s] = $d;
        }
    }

    // a funcao de transicao de um AFD precisa ser total
    foreach ($estados as $q) {
        foreach ($alfabeto as $s) {
            if (!isset($delta[$q][$s])) {
                $erros[] = "falta a transicao de $q com '$s'";
            }
        }
    }

    return array($erros, $delta);
}

// ---------- simulacao ----------

function simular($def, $delta, $cadeia)
{
    $atual = $def['inicial'];
    $caminho = $atual;

    $simbolos = $cadeia === '' ? array() : str_split($cadeia);

    foreach ($simbolos as $s) {
        if (!in_array($s, $def['alfabeto'], true)) {
            return array(false, $caminho, "simbolo '$s' nao pertence ao alfabeto");
        }
        $atual = $delta[$atual][$s];
        $caminho .= " -$s-> $atual";
    }

    $aceita = in_array($atual, $def['finais'], true);
    $motivo = $aceita ? "terminou em $atual (estado de aceitacao)"
                      : "terminou em $atual (nao e estado de aceitacao)";
    return array($aceita, $caminho, $motivo);
}

function mostrarTabela($def, $delta)
{
    echo "\nTabela de transicao:\n";
    echo str_pad('', 12);
    foreach ($def['alfabeto'] as $s) {
        echo str_pad($s, 8);
    }
    echo "\n";
    foreach ($def['estados'] as $q) {
        $marca = '';
        if ($q === $def['inicial']) {
            $marca .= '->';
        }
        if (in_array($q, $def['finais'], true)) {
            $marca .= '*';
        }
        echo str_pad($marca . $q, 12);
        foreach ($def['alfabeto'] as $s) {
            echo str_pad($delta[$q][$s], 8);
        }
        echo "\n";
    }
    echo "(-> inicial, * aceitacao)\n\n";
}

// ---------- programa principal ----------

$def = isset($argv[1]) ? lerDoArquivo($argv[1]) : lerDoConsole();

list($erros, $delta) = validar($def);

if (count($erros) > 0) {
    echo "\nAutomato REJEITADO, nao e um AFD valido:\n";
    foreach ($erros as $e) {
        echo "  - $e\n";
    }
    exit(1);
}

echo "\nAFD valido.\n";
mostrarTabela($def, $delta);

echo "Digite uma cadeia para testar.\n";
echo "Use '-' para a cadeia vazia e 'sair' para encerrar.\n";

while (true) {
    $entrada = perguntar("cadeia> ");
    if ($entrada === 'sair') {
        break;
    }
    $cadeia = ($entrada === '-') ? '' : $entrada;

    list($aceita, $caminho, $motivo) = simular($def, $delta, $cadeia);

    echo "  " . $caminho . "\n";
    echo "  " . ($aceita ? "ACEITA" : "REJEITADA") . " - $motivo\n";
}
