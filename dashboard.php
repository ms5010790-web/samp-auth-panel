<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';
$db = getDB();
$message = '';
$error = '';

// Adicionar / Editar Usuário
if (isset($_POST['save_user'])) {
    $id = $_POST['id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $expires_at = $_POST['expires_at'] ?? '';
    $hwid_lock = isset($_POST['hwid_lock']) ? 1 : 0;
    
    if (empty($username) || empty($expires_at)) {
        $error = "Todos os campos obrigatórios devem ser preenchidos.";
    } else {
        try {
            if (empty($id)) {
                // Novo usuário
                if (empty($password)) {
                    $error = "A senha é obrigatória para novos usuários.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (username, password, expires_at, hwid_lock) VALUES (:username, :password, :expires_at, :hwid_lock)");
                    $stmt->execute([
                        ':username' => $username,
                        ':password' => $hash,
                        ':expires_at' => $expires_at,
                        ':hwid_lock' => $hwid_lock
                    ]);
                    $message = "Usuário criado com sucesso!";
                }
            } else {
                // Editar usuário
                if (!empty($password)) {
                    // Atualiza com nova senha
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET username = :username, password = :password, expires_at = :expires_at, hwid_lock = :hwid_lock WHERE id = :id");
                    $stmt->execute([
                        ':username' => $username,
                        ':password' => $hash,
                        ':expires_at' => $expires_at,
                        ':hwid_lock' => $hwid_lock,
                        ':id' => $id
                    ]);
                } else {
                    // Atualiza sem alterar senha
                    $stmt = $db->prepare("UPDATE users SET username = :username, expires_at = :expires_at, hwid_lock = :hwid_lock WHERE id = :id");
                    $stmt->execute([
                        ':username' => $username,
                        ':expires_at' => $expires_at,
                        ':hwid_lock' => $hwid_lock,
                        ':id' => $id
                    ]);
                }
                $message = "Usuário atualizado com sucesso!";
            }
        } catch (Exception $e) {
            $error = "Erro ao salvar usuário: " . $e->getMessage();
        }
    }
}

// Excluir Usuário
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND username != 'admin'");
        $stmt->execute([':id' => $id]);
        $message = "Usuário excluído com sucesso!";
    } catch (Exception $e) {
        $error = "Erro ao excluir usuário: " . $e->getMessage();
    }
}

// Resetar HWID
if (isset($_GET['reset_hwid'])) {
    $id = $_GET['reset_hwid'];
    try {
        $stmt = $db->prepare("UPDATE users SET hwid = NULL WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $message = "HWID resetado com sucesso! O usuário poderá conectar a partir de um novo dispositivo.";
    } catch (Exception $e) {
        $error = "Erro ao resetar HWID: " . $e->getMessage();
    }
}

// Listar todos os usuários
$users = [];
try {
    $stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erro ao carregar usuários: " . $e->getMessage();
}

// Obter dados do usuário para edição
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    foreach ($users as $u) {
        if ($u['id'] == $edit_id) {
            $edit_user = $u;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #60a5fa;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }
        
        body {
            background: #f1f5f9;
            color: var(--text);
            min-height: 100vh;
        }
        
        .navbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h2 {
            font-weight: 800;
            color: var(--primary);
        }
        
        .navbar a {
            text-decoration: none;
            color: var(--danger);
            font-weight: 600;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        @media (max-width: 900px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border);
        }
        
        .card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text);
            border-bottom: 2px solid #eff6ff;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 2px solid var(--border);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            border-color: var(--primary);
        }
        
        .form-group-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .form-group-checkbox input {
            width: auto;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background: #ecfdf5;
            color: var(--success);
            border-color: #d1fae5;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: var(--danger);
            border-color: #fee2e2;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        
        th {
            font-weight: 600;
            color: var(--text-light);
            background: #f8fafc;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .actions-cell {
            display: flex;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Theus Admin</h2>
        <div>
            <span>Olá, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></span>
            <a href="logout.php" style="margin-left: 20px;">Sair</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Form de Criação/Edição -->
        <div class="card">
            <h3><?= $edit_user ? 'Editar Usuário' : 'Novo Usuário' ?></h3>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="id" value="<?= $edit_user['id'] ?? '' ?>">
                
                <div class="form-group">
                    <label>Usuário</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($edit_user['username'] ?? '') ?>" placeholder="Ex: player_samp">
                </div>
                
                <div class="form-group">
                    <label>Senha <?= $edit_user ? '(Deixe em branco para não alterar)' : '' ?></label>
                    <input type="password" name="password" <?= $edit_user ? '' : 'required' ?> placeholder="Senha de acesso">
                </div>
                
                <div class="form-group">
                    <label>Data de Validade</label>
                    <input type="datetime-local" name="expires_at" required value="<?= isset($edit_user) ? date('Y-m-d\TH:i', strtotime($edit_user['expires_at'])) : date('Y-m-d\TH:i', strtotime('+30 days')) ?>">
                </div>
                
                <div class="form-group form-group-checkbox">
                    <input type="checkbox" id="hwid_lock" name="hwid_lock" <?= (!isset($edit_user) || $edit_user['hwid_lock'] == 1) ? 'checked' : '' ?>>
                    <label for="hwid_lock">Bloquear por dispositivo (HWID Lock)</label>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="save_user" class="btn btn-primary"><?= $edit_user ? 'Salvar Alterações' : 'Criar Conta' ?></button>
                    <?php if ($edit_user): ?>
                        <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Tabela de Contas -->
        <div class="card">
            <h3>Usuários Cadastrados</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Status / Expiração</th>
                            <th>Tipo Dispositivo</th>
                            <th>HWID Registrado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php 
                                $expired = new DateTime($u['expires_at']) < new DateTime();
                                $status_badge = $expired ? '<span class="badge badge-danger">Expirado</span>' : '<span class="badge badge-success">Ativo</span>';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                <td>
                                    <?= $status_badge ?><br>
                                    <small style="color: var(--text-light);"><?= date('d/m/Y H:i', strtotime($u['expires_at'])) ?></small>
                                </td>
                                <td>
                                    <?= $u['hwid_lock'] == 1 ? '<span class="badge badge-info">HWID Lock</span>' : '<span class="badge badge-success">Trial (Livre)</span>' ?>
                                </td>
                                <td>
                                    <small style="font-family: monospace; color: var(--text-light);">
                                        <?= !empty($u['hwid']) ? substr($u['hwid'], 0, 18) . '...' : '<i>Nenhum dispositivo conectado</i>' ?>
                                    </small>
                                </td>
                                <td class="actions-cell">
                                    <a href="dashboard.php?edit=<?= $u['id'] ?>" class="btn btn-secondary" style="padding: 6px 10px; font-size: 12px;">Editar</a>
                                    
                                    <?php if ($u['hwid_lock'] == 1 && !empty($u['hwid'])): ?>
                                        <a href="dashboard.php?reset_hwid=<?= $u['id'] ?>" class="btn btn-warning" style="padding: 6px 10px; font-size: 12px;">Reset HWID</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($u['username'] !== 'admin'): ?>
                                        <a href="dashboard.php?delete=<?= $u['id'] ?>" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;" onclick="return confirm('Deseja excluir este usuário?')">Excluir</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
