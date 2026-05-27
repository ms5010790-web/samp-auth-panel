<?php
// Configuração do Banco de Dados (usando SQLite por ser grátis, leve e não exigir servidor MySQL externo)
define('DB_FILE', __DIR__ . '/database.sqlite');

function getDB() {
    $dbUrl = getenv('DATABASE_URL');
    if ($dbUrl) {
        // Conexão PostgreSQL (Produção no Render)
        $url = parse_url($dbUrl);
        $host = $url["host"];
        $port = $url["port"] ?? 5432;
        $user = $url["user"];
        $pass = $url["pass"];
        $dbname = ltrim($url["path"], '/');
        
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $db = new PDO($dsn, $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cria tabela compatível com PostgreSQL
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            hwid_lock INT DEFAULT 1,
            hwid VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        // Conexão SQLite (Fallback local de testes)
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            hwid_lock INTEGER DEFAULT 1,
            hwid TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Criar usuário administrador padrão se não houver usuários
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (username, password, expires_at, hwid_lock) VALUES ('admin', '$hash', '2099-12-31 23:59:59', 0)");
    }
    
    return $db;
}
?>
