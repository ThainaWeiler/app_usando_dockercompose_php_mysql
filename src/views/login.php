<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Entrar — Gestão de alimentos</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>

<h1>🥗 Gestão de alimentos</h1>

<div class="container container-narrow">

<div class="card">

<h2>Identificação</h2>

<p class="hint">Indica o teu nome para associar aos cadastos de alimentos (sessão simples, sem palavra-passe).</p>

<?php if ($mensagem !== ''): ?>
    <div class="msg"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form class="form-login" method="post" action="/entrar">
    <label class="field">
        <span>O teu nome</span>
        <input type="text" name="nome" required maxlength="120" autocomplete="name" autofocus>
    </label>
    <button type="submit">Entrar</button>
</form>

</div>

</div>

</body>
</html>
