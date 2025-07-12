<?php
// ================================================
// SISTEMA ENEDES - CONFIGURAÇÃO DE BANCO DE DADOS
// Arquivo: config.php
// ================================================

// Configurações de conexão com PostgreSQL (Neon)
$host = 'ep-royal-dust-aekknepj-pooler.c-2.us-east-2.aws.neon.tech';
$dbname = 'neondb';
$username = 'neondb_owner';
$password = 'npg_7ME3tXIfwone'; // Substitua pela sua senha real do Neon
$port = '5432';

// Configurações adicionais
$charset = 'utf8';

try {
    // Criar conexão PDO para PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Definir timezone
    $pdo->exec("SET timezone = 'America/Sao_Paulo'");
    
} catch (PDOException $e) {
    // Log do erro (em produção, não exibir detalhes)
    error_log("Erro de conexão: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Erro de conexão com o banco de dados'
    ]));
}

// Função para resposta JSON
function jsonResponse($data) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Função para sanitizar entrada
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Função para validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para gerar hash de senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Função para verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Headers CORS (para desenvolvimento)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200 );
    exit;
}

// Configurações do sistema
define('SYSTEM_NAME', 'Sistema ENEDES');
define('SYSTEM_VERSION', '1.0.0');
define('DEFAULT_PASSWORD', '123456');
define('UPLOAD_PATH', './uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Criar diretórios necessários se não existirem
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(UPLOAD_PATH . 'actions/')) {
    mkdir(UPLOAD_PATH . 'actions/', 0755, true);
}
if (!file_exists(UPLOAD_PATH . 'follow_ups/')) {
    mkdir(UPLOAD_PATH . 'follow_ups/', 0755, true);
}

// Função para log de atividades
function logActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId, $action, $tableName, $recordId, $oldValues, $newValues, $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log silencioso
        error_log("Erro ao registrar atividade: " . $e->getMessage());
    }
}

// Função para obter dados do usuário logado (simulado)
function getCurrentUser() {
    // Em uma implementação real, isso viria de uma sessão ou token JWT
    return [
        'id' => 1,
        'username' => 'coord_geral',
        'name' => 'Coordenador Geral',
        'role' => 'general_coordinator'
    ];
}

?>
