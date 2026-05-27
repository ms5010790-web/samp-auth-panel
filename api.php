<?php
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metodo nao permitido']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$hwid = $_POST['hwid'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario e senha obrigatorios']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario nao encontrado']);
        exit;
    }
    
    // Validar senha
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Senha incorreta']);
        exit;
    }
    
    // Validar expiração
    $expires = new DateTime($user['expires_at']);
    $now = new DateTime();
    if ($now > $expires) {
        echo json_encode(['status' => 'error', 'message' => 'Sua licenca expirou em ' . $expires->format('d/m/Y H:i:s')]);
        exit;
    }
    
    // Validar HWID
    if ($user['hwid_lock'] == 1) {
        if (empty($user['hwid'])) {
            // Primeiro login: registra o HWID
            $update = $db->prepare("UPDATE users SET hwid = :hwid WHERE id = :id");
            $update->execute([':hwid' => $hwid, ':id' => $user['id']]);
        } else {
            // Logins subsequentes: compara o HWID recebido com o salvo
            if ($user['hwid'] !== $hwid) {
                echo json_encode(['status' => 'error', 'message' => 'Acesso negado: Este login esta associado a outro dispositivo']);
                exit;
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Autenticado com sucesso',
        'expires_at' => $user['expires_at']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>
