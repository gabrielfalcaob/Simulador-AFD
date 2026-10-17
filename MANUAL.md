# Simulador de AFD

Trabalho 1 de Linguagens Formais e Autômatos.

Programa em PHP, executado pelo terminal, que recebe a definição de um Autômato Finito Determinístico e diz se cadeias digitadas pertencem ou não à linguagem dele.

**Integrantes:** _(nomes do grupo)_

## Requisitos

- PHP 7.4 ou superior, com o comando `php` disponível no terminal.
- Nenhuma biblioteca extra.

Para conferir:

```bash
php -v
```

## Como executar

Pelo console, digitando a definição do autômato:

```bash
php afd.php
```

Lendo a definição de um arquivo:

```bash
php afd.php exemplo_par0.txt
```

## O autômato

O simulador trabalha com `A = (Q, Σ, δ, q0, F)`:

| Componente | Significado | Como informar |
|---|---|---|
| Q | conjunto de estados | nomes separados por espaço (`q0 q1 q2`) |
| Σ | alfabeto | símbolos separados por espaço (`0 1`) |
| δ | função de transição | uma linha por transição: `origem símbolo destino` |
| q0 | estado inicial | um único estado de Q |
| F | estados de aceitação | nomes separados por espaço |

Observações:

- Os nomes dos estados podem ter mais de um caractere (`q0`, `par`, `S1`).
- Cada símbolo do alfabeto tem **exatamente 1 caractere**, porque a cadeia é lida caractere por caractere.
- O alfabeto pode ter qualquer tamanho, não ficou limitado a 2 símbolos.

## Entrada por arquivo

Formato:

```text
# comentário (linhas começando com # são ignoradas)
estados: q0 q1
alfabeto: 0 1
inicial: q0
finais: q0
transicoes:
q0 0 q1
q0 1 q0
q1 0 q0
q1 1 q1
```

Regras:

- Os quatro primeiros campos podem vir em qualquer ordem.
- A palavra `transicoes:` deve vir por último: tudo que estiver abaixo dela é lido como transição.
- Linhas em branco são ignoradas.

## Entrada pelo console

Sem argumentos, o programa pergunta cada componente:

```text
Estados (separados por espaco): q0 q1
Alfabeto (simbolos separados por espaco): 0 1
Estado inicial: q0
Estados de aceitacao: q0
Transicoes no formato: origem simbolo destino
(linha em branco para terminar)
> q0 0 q1
> q0 1 q0
> q1 0 q0
> q1 1 q1
>
```

A linha em branco encerra a lista de transições.

## Validação do autômato

Antes de aceitar a definição, o programa verifica se ela é mesmo um AFD. Se algo estiver errado, ele mostra todos os problemas encontrados e termina.

| Problema | Exemplo de mensagem |
|---|---|
| Não determinismo (duas transições diferentes para o mesmo par estado/símbolo) | `nao e deterministico: de q0 com '0' vai para q0 e tambem para q1` |
| Função δ incompleta (falta transição) | `falta a transicao de q1 com '1'` |
| Estado inicial fora de Q | `estado inicial 'q9' nao pertence a Q` |
| Estado de aceitação fora de Q | `estado de aceitacao 'q9' nao pertence a Q` |
| Transição com símbolo fora do alfabeto | `transicao (q0, 2): simbolo '2' fora do alfabeto` |
| Transição com estado inexistente | `transicao (q0, 0): destino 'q9' nao existe` |
| Estados ou símbolos repetidos, alfabeto ou Q vazios, símbolo com mais de 1 caractere | mensagem correspondente |

A função de transição de um AFD é total (δ: Q × Σ → Q), por isso o programa exige uma transição para cada combinação de estado e símbolo. Para representar "estado de erro", declare um estado extra (um poço) e aponte para ele.

## Testando cadeias

Com o autômato válido, o programa mostra a tabela de transição e abre um prompt:

```text
cadeia>
```

- Digite a cadeia e tecle Enter.
- Digite `-` para testar a **cadeia vazia** (ε).
- Digite `sair` para encerrar (Ctrl+D também encerra).

Para cada cadeia, o programa imprime o caminho percorrido e o veredito.

**Critério de aceitação:** a cadeia é aceita se, depois de ler **todos** os símbolos a partir de q0, o autômato estiver em um estado de F. Se a leitura terminar fora de F, a cadeia é rejeitada. Se aparecer um símbolo que não pertence a Σ, a cadeia também é rejeitada (a leitura para nesse ponto).

## Exemplos de uso

### Exemplo 1: quantidade par de zeros

Linguagem `L = { w ∈ {0,1}* | w tem uma quantidade par de 0s }`, definida em `exemplo_par0.txt`.

```bash
php afd.php exemplo_par0.txt
```

Saída inicial:

```text
AFD valido.

Tabela de transicao:
            0       1
->*q0       q1      q0
q1          q0      q1
(-> inicial, * aceitacao)
```

Cadeias aceitas:

```text
cadeia> -
  q0
  ACEITA - terminou em q0 (estado de aceitacao)
cadeia> 1
  q0 -1-> q0
  ACEITA - terminou em q0 (estado de aceitacao)
cadeia> 0110
  q0 -0-> q1 -1-> q1 -1-> q1 -0-> q0
  ACEITA - terminou em q0 (estado de aceitacao)
```

Cadeias rejeitadas:

```text
cadeia> 0
  q0 -0-> q1
  REJEITADA - terminou em q1 (nao e estado de aceitacao)
cadeia> 0111
  q0 -0-> q1 -1-> q1 -1-> q1 -1-> q1
  REJEITADA - terminou em q1 (nao e estado de aceitacao)
```

Símbolo fora do alfabeto:

```text
cadeia> 102
  q0 -1-> q0 -0-> q1
  REJEITADA - simbolo '2' nao pertence ao alfabeto
```

### Exemplo 2: autômato não determinístico (rejeitado)

Arquivo `exemplo_nao_deterministico.txt`, onde `q0` tem duas transições com o símbolo `0`:

```bash
php afd.php exemplo_nao_deterministico.txt
```

```text
Automato REJEITADO, nao e um AFD valido:
  - nao e deterministico: de q0 com '0' vai para q0 e tambem para q1
```

O programa termina sem abrir o prompt de cadeias.

### Exemplo 3: pelo console, cadeias terminadas em `01`

```text
Estados (separados por espaco): a b c
Alfabeto (simbolos separados por espaco): 0 1
Estado inicial: a
Estados de aceitacao: c
Transicoes no formato: origem simbolo destino
(linha em branco para terminar)
> a 0 b
> a 1 a
> b 0 b
> b 1 c
> c 0 b
> c 1 a
>
```

Testes:

| Cadeia | Resultado |
|---|---|
| `01` | aceita |
| `1101` | aceita |
| `010` | rejeitada |
| `-` (vazia) | rejeitada |

## Estrutura do código

| Função | O que faz |
|---|---|
| `lerDoConsole()` | monta a definição perguntando ao usuário |
| `lerDoArquivo()` | monta a definição a partir de um arquivo |
| `validar()` | confere se é um AFD válido e monta a tabela δ |
| `simular()` | percorre a cadeia a partir de q0 e diz se termina em F |
| `mostrarTabela()` | imprime a tabela de transição |
