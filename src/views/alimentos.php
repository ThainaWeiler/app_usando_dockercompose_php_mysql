<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gestão de alimentos</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>

<header class="top-bar">
    <p class="user-badge">A registar como: <strong><?= htmlspecialchars($usuarioLogado, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <a class="logout" href="/sair">Sair</a>
</header>

<h1>🥗 Gestão de alimentos</h1>

<div class="container">

<div class="card">

<h2><?= $modoEditar ? 'Editar alimento' : 'Cadastrar alimento' ?></h2>

<p class="hint">O nome abaixo é o utilizador em sessão; os dados do alimento são guardados em conjunto com este registo.</p>

<div class="readonly-user">
    <span class="label">Utilizador (sessão)</span>
    <span class="value"><?= htmlspecialchars($usuarioLogado, ENT_QUOTES, 'UTF-8') ?></span>
</div>

<?php if ($mensagem !== ''): ?>
    <?php $msgErro = str_starts_with($mensagem, '❌'); ?>
    <div class="msg<?= $msgErro ? ' msg-erro' : '' ?>"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form class="form-alimento" method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($modoEditar): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <label class="field">
        <span>Nome do alimento</span>
        <input type="text" name="nome" required maxlength="200" inputmode="text" autocomplete="off"
            value="<?= htmlspecialchars((string) ($camposForm['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </label>

    <label class="field">
        <span>Distribuidora</span>
        <input type="text" name="distribuidora" required maxlength="200" inputmode="text" autocomplete="organization"
            value="<?= htmlspecialchars((string) ($camposForm['distribuidora'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </label>

    <label class="field">
        <span>Data de recebimento</span>
        <input type="date" name="data_recebimento" required
            min="<?= htmlspecialchars($dataRecebimentoMin, ENT_QUOTES, 'UTF-8') ?>"
            value="<?= htmlspecialchars((string) ($camposForm['data_recebimento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <small class="hint">Não pode ser anterior a <strong>hoje menos 2 dias</strong> (exceção para registar receções após o fim de semana).</small>
    </label>

    <label class="field">
        <span>Data de fabricação</span>
        <input type="date" name="data_fabricacao" required
            value="<?= htmlspecialchars((string) ($camposForm['data_fabricacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <small class="hint">Tem de ser no <strong>ano atual</strong> ou, no máximo, até <strong>6 meses</strong> antes de hoje (e não pode ser futura).</small>
    </label>

    <label class="field">
        <span>Data de validade</span>
        <input type="date" name="data_validade" required
            value="<?= htmlspecialchars((string) ($camposForm['data_validade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </label>

    <label class="field checkbox-field">
        <input type="checkbox" name="perecivel" value="1"
            <?= !empty($camposForm['perecivel']) ? 'checked' : '' ?>>
        <span>Perecível</span>
    </label>

    <label class="field">
        <span>Preço de custo (R$)</span>
        <input type="text" name="preco_custo" required inputmode="decimal" pattern="\d+([.,]\d{1,2})?"
            title="Valor em reais: número positivo, até 2 casas decimais (ex.: 12,50)"
            value="<?= htmlspecialchars(isset($camposForm['preco_custo']) ? (string) $camposForm['preco_custo'] : '', ENT_QUOTES, 'UTF-8') ?>">
        <small class="hint">Moeda: <strong>real brasileiro (BRL)</strong>; use vírgula ou ponto nas casas decimais.</small>
    </label>

    <button type="submit"><?= $modoEditar ? 'Atualizar' : 'Cadastrar' ?></button>
</form>

<?php if ($modoEditar): ?>
    <p class="back-link"><a href="/">← Voltar ao cadastro</a></p>
<?php endif; ?>

</div>

<div class="card">

<h2>📋 Alimentos registados</h2>

<div class="table-wrap">
<table class="table-alimentos">
<thead>
<tr>
    <th>ID</th>
    <th>Registado por</th>
    <th>Alimento</th>
    <th>Distribuidora</th>
    <th>Receb.</th>
    <th>Fabric.</th>
    <th>Valid.</th>
    <th>Perecível</th>
    <th>Custo (R$)</th>
    <th></th>
</tr>
</thead>
<tbody>
<?php while ($row = $result->fetch_assoc()): ?>
<?php $custo = (float) $row['preco_custo']; ?>
<tr>
    <td><?= (int) $row['id'] ?></td>
    <td><?= htmlspecialchars($row['cadastrado_por'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($row['distribuidora'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($row['data_recebimento'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($row['data_fabricacao'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars($row['data_validade'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= !empty($row['perecivel']) ? 'Sim' : 'Não' ?></td>
    <td>R$ <?= number_format($custo, 2, ',', '.') ?></td>
    <td class="actions">
        <a class="edit" href="/alimentos/<?= (int) $row['id'] ?>/editar" title="Editar">✏️</a>
        <form class="delete-form" method="post" action="/alimentos/<?= (int) $row['id'] ?>"
              onsubmit="return confirm('Remover este alimento?');">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="link delete" title="Remover">🗑️</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

</div>

</div>

</body>
</html>
