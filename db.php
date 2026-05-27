<?php
// Configuração do Banco de Dados (usando SQLite por ser grátis, leve e não exigir servidor MySQL externo)
define('DB_FILE', __DIR__ . '/database.sqlite');

function getDB() {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Criar tabelas se não existirem
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        expires_at DATETIME NOT NULL,
        hwid_lock INTEGER DEFAULT 1, -- 1 = Sim (Bloquear ao primeiro dispositivo), 0 = Não (Trial/Livre)
        hwid TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Criar usuário administrador padrão se não houver usuários
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        // Admin padrão: admin / admin123
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (username, password, expires_at, hwid_lock) VALUES ('admin', '$hash', '2099-12-31 23:59:59', 0)");
    }
    
    return $db;
}
?>
