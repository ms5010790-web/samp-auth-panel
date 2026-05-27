<?php
session_start();
require_once 'db.php';

// Login do administrador
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Usuário ou senha inválidos.";
        }
    } catch (Exception $e) {
        $error = "Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Login</title>
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
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.1), 0 8px 10px -6px rgba(59, 130, 246, 0.1);
            width: 100%;
            max-width: 440px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .header p {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 2px solid var(--border);
            font-size: 15px;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }
        
        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -1px rgba(37, 99, 235, 0.3);
        }
        
        .alert {
            background: #fef2f2;
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid #fee2e2;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="header">
            <h1>Antigravity Auth</h1>
            <p>Gerencie o acesso do seu script Moonloader</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Usuário Administrador</label>
                <input type="text" id="username" name="username" required placeholder="Digite o usuário">
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required placeholder="Digite a senha">
            </div>
            
            <button type="submit" name="login" class="btn-submit">Entrar no Painel</button>
        </form>
    </div>
</body>
</html>
