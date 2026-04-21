<?php
session_start();

$host = 'mysql';
$user = 'meu_usuario';
$pass = 'minha_senha';
$db   = 'meu_banco';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Erro: ' . $conn->connect_error);
}

require_once __DIR__ . '/includes/ensure_alimentos_table.php';
garantir_tabela_alimentos($conn);

require_once __DIR__ . '/validators/alimento.php';

/** Método HTTP real (aceita POST + _method=PUT|DELETE para HTML) */
function http_method(): string
{
    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($m === 'POST' && isset($_POST['_method'])) {
        $override = strtoupper(trim((string) $_POST['_method']));
        if (in_array($override, ['PUT', 'DELETE', 'PATCH'], true)) {
            return $override;
        }
    }

    return $m;
}

/** Segmentos do caminho, ex.: /alimentos/3/editar → ['alimentos','3','editar'] */
function path_segments(): array
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = trim($path, '/');

    return $path === '' ? [] : explode('/', $path);
}

$mensagem = isset($_SESSION['flash']) ? (string) $_SESSION['flash'] : '';
unset($_SESSION['flash']);
$formDraft = isset($_SESSION['form_draft']) && is_array($_SESSION['form_draft']) ? $_SESSION['form_draft'] : null;
unset($_SESSION['form_draft']);

$method = http_method();
$segments = path_segments();
$edit = null;

$usuarioLogado = isset($_SESSION['usuario_nome']) ? trim((string) $_SESSION['usuario_nome']) : '';

// ---------- Rotas públicas (sem sessão obrigatória) ----------

if ($segments === ['entrar'] && $method === 'GET') {
    if ($usuarioLogado !== '') {
        header('Location: /', true, 302);
        exit;
    }
    require __DIR__ . '/views/login.php';
    exit;
}

if ($segments === ['entrar'] && $method === 'POST') {
    $nomeLogin = trim((string) ($_POST['nome'] ?? ''));
    if ($nomeLogin === '') {
        $_SESSION['flash'] = 'Indique o seu nome para continuar.';
        header('Location: /entrar', true, 303);
        exit;
    }
    $_SESSION['usuario_nome'] = $nomeLogin;
    header('Location: /', true, 303);
    exit;
}

if ($segments === ['sair'] && $method === 'GET') {
    unset($_SESSION['usuario_nome']);
    session_regenerate_id(true);
    header('Location: /entrar', true, 302);
    exit;
}

if ($usuarioLogado === '') {
    header('Location: /entrar', true, 302);
    exit;
}

// ---------- Rotas REST: alimentos ----------

if ($method === 'POST' && $segments === ['alimentos']) {
    $validado = validar_entrada_alimento($_POST, null);
    if ($validado['ok'] !== true) {
        $_SESSION['flash'] = '❌ ' . implode(' ', $validado['errors']);
        $_SESSION['form_draft'] = $validado['draft'];
        header('Location: /', true, 303);
        exit;
    }
    $d = $validado['data'];
    $cadastrado_por = $usuarioLogado;
    $nome = $d['nome'];
    $distribuidora = $d['distribuidora'];
    $data_recebimento = $d['data_recebimento'];
    $data_fabricacao = $d['data_fabricacao'];
    $data_validade = $d['data_validade'];
    $perecivel = (int) $d['perecivel'];
    $preco_custo = (float) $d['preco_custo'];

    $stmt = $conn->prepare(
        'INSERT INTO alimentos (cadastrado_por, nome, distribuidora, data_recebimento, data_fabricacao, data_validade, perecivel, preco_custo)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'ssssssid',
        $cadastrado_por,
        $nome,
        $distribuidora,
        $data_recebimento,
        $data_fabricacao,
        $data_validade,
        $perecivel,
        $preco_custo
    );
    if ($stmt->execute()) {
        $_SESSION['flash'] = '✅ Alimento cadastrado com sucesso!';
    } else {
        $_SESSION['flash'] = '❌ Erro ao cadastrar!';
    }
    $stmt->close();
    header('Location: /', true, 303);
    exit;
}

if ($method === 'PUT' && count($segments) === 2 && $segments[0] === 'alimentos' && ctype_digit($segments[1])) {
    $id = (int) $segments[1];
    $recebimentoAnterior = null;
    $stmtAnt = $conn->prepare('SELECT data_recebimento FROM alimentos WHERE id = ?');
    if ($stmtAnt) {
        $stmtAnt->bind_param('i', $id);
        $stmtAnt->execute();
        $rowAnt = $stmtAnt->get_result()->fetch_assoc();
        $stmtAnt->close();
        if (is_array($rowAnt) && isset($rowAnt['data_recebimento'])) {
            $recebimentoAnterior = (string) $rowAnt['data_recebimento'];
        }
    }
    $validado = validar_entrada_alimento($_POST, $recebimentoAnterior);
    if ($validado['ok'] !== true) {
        $mensagem = '❌ ' . implode(' ', $validado['errors']);
        $edit = array_merge(['id' => $id], $validado['draft']);
    } else {
        $d = $validado['data'];
        $cadastrado_por = $usuarioLogado;
        $nome = $d['nome'];
        $distribuidora = $d['distribuidora'];
        $data_recebimento = $d['data_recebimento'];
        $data_fabricacao = $d['data_fabricacao'];
        $data_validade = $d['data_validade'];
        $perecivel = (int) $d['perecivel'];
        $preco_custo = (float) $d['preco_custo'];

        $stmt = $conn->prepare(
            'UPDATE alimentos SET cadastrado_por = ?, nome = ?, distribuidora = ?, data_recebimento = ?, data_fabricacao = ?, data_validade = ?, perecivel = ?, preco_custo = ? WHERE id = ?'
        );
        $stmt->bind_param(
            'ssssssidi',
            $cadastrado_por,
            $nome,
            $distribuidora,
            $data_recebimento,
            $data_fabricacao,
            $data_validade,
            $perecivel,
            $preco_custo,
            $id
        );
        if ($stmt->execute()) {
            $mensagem = '✏️ Alimento atualizado com sucesso!';
            $stmt->close();
            $stmt = $conn->prepare('SELECT * FROM alimentos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $edit = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $mensagem = '❌ Erro ao atualizar!';
            $stmt->close();
            $stmt = $conn->prepare('SELECT * FROM alimentos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $edit = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}

if ($method === 'DELETE' && count($segments) === 2 && $segments[0] === 'alimentos' && ctype_digit($segments[1])) {
    $id = (int) $segments[1];
    $stmt = $conn->prepare('DELETE FROM alimentos WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['flash'] = '🗑️ Alimento removido!';
    } else {
        $_SESSION['flash'] = '❌ Erro ao remover!';
    }
    $stmt->close();
    header('Location: /', true, 303);
    exit;
}

// ---------- Página: listagem + formulário (GET) ----------

if ($method === 'GET') {
    if ($segments === [] || $segments === ['alimentos']) {
        // ok
    } elseif (
        count($segments) === 2
        && $segments[0] === 'alimentos'
        && ctype_digit($segments[1])
    ) {
        header('Location: /alimentos/' . (int) $segments[1] . '/editar', true, 302);
        exit;
    } elseif (
        count($segments) === 3
        && $segments[0] === 'alimentos'
        && ctype_digit($segments[1])
        && $segments[2] === 'editar'
    ) {
        $id = (int) $segments[1];
        $stmt = $conn->prepare('SELECT * FROM alimentos WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $edit = $res->fetch_assoc();
        $stmt->close();
        if (!$edit) {
            http_response_code(404);
            echo 'Alimento não encontrado.';
            exit;
        }
    } else {
        http_response_code(404);
        echo 'Rota não encontrada.';
        exit;
    }
} elseif (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    http_response_code(405);
    header('Allow: GET, POST, PUT, DELETE');
    echo 'Método não permitido.';
    exit;
}

$result = $conn->query('SELECT * FROM alimentos ORDER BY id DESC');

$formAction = isset($edit['id'])
    ? '/alimentos/' . (int) $edit['id']
    : '/alimentos';

$modoEditar = is_array($edit) && array_key_exists('id', $edit);
$camposForm = $modoEditar ? $edit : ($formDraft ?? []);

$hojePagina = new DateTimeImmutable('today');
$limiteRecebimentoUi = $hojePagina->sub(new DateInterval('P2D'));
if ($modoEditar && !empty($camposForm['data_recebimento'])) {
    $prevStr = trim((string) $camposForm['data_recebimento']);
    $prevDt = DateTimeImmutable::createFromFormat('!Y-m-d', $prevStr);
    if ($prevDt !== false && $prevDt->format('Y-m-d') === $prevStr && $prevDt < $limiteRecebimentoUi) {
        $limiteRecebimentoUi = $prevDt;
    }
}
$dataRecebimentoMin = $limiteRecebimentoUi->format('Y-m-d');

require __DIR__ . '/views/alimentos.php';
