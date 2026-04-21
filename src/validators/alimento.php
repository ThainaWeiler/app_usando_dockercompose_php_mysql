<?php

declare(strict_types=1);

/**
 * Validação estrita no servidor: tipos e formatos corretos por campo.
 * Não grava na base de dados se houver qualquer inconsistência.
 *
 * @param array<string, mixed> $post
 * @param string|null $dataRecebimentoAnterior AAAA-MM-DD já guardada (edição); alarga o limite mínimo para não invalidar registos antigos.
 * @return array{ok: true, data: array<string, mixed>}|array{ok: false, errors: list<string>, draft: array<string, mixed>}
 */
function validar_entrada_alimento(array $post, ?string $dataRecebimentoAnterior = null): array
{
    $draft = [
        'nome' => trim((string) ($post['nome'] ?? '')),
        'distribuidora' => trim((string) ($post['distribuidora'] ?? '')),
        'data_recebimento' => trim((string) ($post['data_recebimento'] ?? '')),
        'data_fabricacao' => trim((string) ($post['data_fabricacao'] ?? '')),
        'data_validade' => trim((string) ($post['data_validade'] ?? '')),
        'perecivel' => isset($post['perecivel']) ? 1 : 0,
        'preco_custo' => trim((string) ($post['preco_custo'] ?? '')),
    ];

    $errors = [];

    $nome = $draft['nome'];
    if ($nome === '') {
        $errors[] = 'O nome do alimento é obrigatório.';
    } elseif (mb_strlen($nome) > 200) {
        $errors[] = 'O nome do alimento não pode exceder 200 caracteres.';
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $nome) === 1) {
        $errors[] = 'No campo «nome do alimento» não pode constar apenas uma data; use os campos de data.';
    } elseif (preg_match('/\p{L}/u', $nome) !== 1) {
        $errors[] = 'O nome do alimento tem de incluir letras (não pode ser só números ou símbolos).';
    }

    $dist = $draft['distribuidora'];
    if ($dist === '') {
        $errors[] = 'A distribuidora é obrigatória.';
    } elseif (mb_strlen($dist) > 200) {
        $errors[] = 'O nome da distribuidora não pode exceder 200 caracteres.';
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dist) === 1) {
        $errors[] = 'No campo «distribuidora» não pode constar apenas uma data.';
    } elseif (preg_match('/\p{L}/u', $dist) !== 1) {
        $errors[] = 'A distribuidora tem de incluir letras (não pode ser só números ou símbolos).';
    }

    $dr = validar_data_ymd($draft['data_recebimento']);
    $df = validar_data_ymd($draft['data_fabricacao']);
    $dv = validar_data_ymd($draft['data_validade']);

    if ($dr === null) {
        $errors[] = 'Data de recebimento inválida ou em formato incorreto (obrigatório: AAAA-MM-DD, calendário real).';
    }
    if ($dr !== null) {
        $drDt = DateTimeImmutable::createFromFormat('!Y-m-d', $dr);
        if ($drDt === false) {
            $errors[] = 'Data de recebimento inválida.';
        } else {
            $hoje = new DateTimeImmutable('today');
            $limiteMin = $hoje->sub(new DateInterval('P2D'));
            $ant = $dataRecebimentoAnterior !== null && $dataRecebimentoAnterior !== ''
                ? validar_data_ymd($dataRecebimentoAnterior)
                : null;
            if ($ant !== null) {
                $antDt = DateTimeImmutable::createFromFormat('!Y-m-d', $ant);
                if ($antDt !== false && $antDt < $limiteMin) {
                    $limiteMin = $antDt;
                }
            }
            if ($drDt < $limiteMin) {
                $errors[] = 'A data de recebimento não pode ser anterior a '
                    . $limiteMin->format('Y-m-d')
                    . ' (são admitidos, no máximo, 2 dias antes de hoje por causa do fim de semana).';
            }
        }
    }
    if ($df === null) {
        $errors[] = 'Data de fabricação inválida ou em formato incorreto (obrigatório: AAAA-MM-DD, calendário real).';
    }
    if ($dv === null) {
        $errors[] = 'Data de validade inválida ou em formato incorreto (obrigatório: AAAA-MM-DD, calendário real).';
    }

    if ($df !== null) {
        $dfDt = DateTimeImmutable::createFromFormat('!Y-m-d', $df);
        if ($dfDt === false) {
            $errors[] = 'Data de fabricação inválida.';
        } else {
            $hoje = new DateTimeImmutable('today');
            if ($dfDt > $hoje) {
                $errors[] = 'A data de fabricação não pode ser futura.';
            }
            $anoAtual = (int) $hoje->format('Y');
            $anoFabrico = (int) $dfDt->format('Y');
            $limiteInferior = $hoje->sub(new DateInterval('P6M'));
            $noAnoAtual = $anoFabrico === $anoAtual;
            $naoMaisAntigaQueSeisMeses = $dfDt >= $limiteInferior;
            if (!$noAnoAtual && !$naoMaisAntigaQueSeisMeses) {
                $errors[] = 'A data de fabricação tem de ser no ano atual (' . $anoAtual
                    . ') ou, em alternativa, não anterior a 6 meses antes da data de hoje (mínimo permitido: '
                    . $limiteInferior->format('Y-m-d') . ').';
            }
        }
    }

    if ($dr !== null && $df !== null && $dv !== null) {
        if ($dv < $df) {
            $errors[] = 'A data de validade não pode ser anterior à data de fabricação.';
        }
    }

    $pc = parse_decimal_reais($draft['preco_custo']);
    if ($pc === null) {
        $errors[] = 'Preço de custo inválido: indique o valor em reais (R$), só número (ex.: 12,50 ou 12.50), até duas casas decimais, não negativo.';
    }

    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors, 'draft' => $draft];
    }

    return [
        'ok' => true,
        'data' => [
            'nome' => $nome,
            'distribuidora' => $dist,
            'data_recebimento' => $dr,
            'data_fabricacao' => $df,
            'data_validade' => $dv,
            'perecivel' => (int) $draft['perecivel'],
            'preco_custo' => $pc,
        ],
    ];
}

/** Data estrita AAAA-MM-DD (inclui validação de calendário). */
function validar_data_ymd(string $valor): ?string
{
    $v = trim($valor);
    if ($v === '') {
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
    if ($d === false || $d->format('Y-m-d') !== $v) {
        return null;
    }

    return $v;
}

/**
 * Valor monetário em reais (entrada livre: vírgula ou ponto como separador decimal).
 * Aceita "12", "12,5", "12.50"; rejeita texto, notação científica, infinitos.
 * Máximo 2 casas decimais; >= 0.
 */
function parse_decimal_reais(string $raw): ?float
{
    $t = str_replace(' ', '', trim($raw));
    if ($t === '') {
        return null;
    }
    $t = str_replace(',', '.', $t);
    if (preg_match('/^\d+(\.\d{1,2})?$/', $t) !== 1) {
        return null;
    }
    if (!is_numeric($t)) {
        return null;
    }
    $f = round((float) $t, 2);
    if ($f < 0 || !is_finite($f)) {
        return null;
    }

    return $f;
}
