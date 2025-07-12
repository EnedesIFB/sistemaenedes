<?php
// ================================================
// TESTE DE CONEXÃO COM BANCO DE DADOS
// Arquivo: test_connection.php
// ================================================

// Headers para permitir acesso via browser
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Configurações de conexão com PostgreSQL (Neon)
$host = 'ep-white-thunder-a5ixkfxe.us-east-2.aws.neon.tech';
$dbname = 'neondb';
$username = 'neondb_owner';
$password = 'npg_QOQqhGPNJKqOdGGQhCOQJKqOdGGQhCOQ'; // Substitua pela senha real
$port = '5432';

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_type' => 'Database Connection Test',
    'status' => 'unknown',
    'message' => '',
    'details' => []
];

try {
    // Teste 1: Verificar extensão PDO
    if (!extension_loaded('pdo')) {
        throw new Exception('Extensão PDO não está carregada');
    }
    $result['details']['pdo_loaded'] = true;
    
    // Teste 2: Verificar driver PostgreSQL
    if (!extension_loaded('pdo_pgsql')) {
        throw new Exception('Driver PDO PostgreSQL não está disponível');
    }
    $result['details']['pdo_pgsql_loaded'] = true;
    
    // Teste 3: Tentar conexão
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    $result['details']['connection_successful'] = true;
    
    // Teste 4: Verificar se as tabelas existem
    $tables = ['users', 'sections', 'actions', 'collaborators'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            )
        ");
        $stmt->execute([$table]);
        $exists = $stmt->fetchColumn();
        $existingTables[$table] = (bool)$exists;
    }
    
    $result['details']['tables'] = $existingTables;
    
    // Teste 5: Contar registros nas tabelas principais
    $counts = [];
    foreach ($tables as $table) {
        if ($existingTables[$table]) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $counts[$table] = (int)$stmt->fetchColumn();
        } else {
            $counts[$table] = 'table_not_exists';
        }
    }
    
    $result['details']['record_counts'] = $counts;
    
    // Teste 6: Verificar usuários de exemplo
    if ($existingTables['users']) {
        $stmt = $pdo->query("SELECT username, name, role FROM users WHERE is_active = true LIMIT 5");
        $sampleUsers = $stmt->fetchAll();
        $result['details']['sample_users'] = $sampleUsers;
    }
    
    // Teste 7: Verificar seções
    if ($existingTables['sections']) {
        $stmt = $pdo->query("SELECT name FROM sections WHERE is_active = true");
        $sections = array_column($stmt->fetchAll(), 'name');
        $result['details']['sections'] = $sections;
    }
    
    // Teste 8: Verificar timezone
    $stmt = $pdo->query("SELECT current_setting('timezone') as timezone");
    $timezone = $stmt->fetchColumn();
    $result['details']['database_timezone'] = $timezone;
    
    // Teste 9: Verificar versão do PostgreSQL
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    $result['details']['postgresql_version'] = $version;
    
    $result['status'] = 'success';
    $result['message'] = 'Conexão com banco de dados estabelecida com sucesso!';
    
} catch (PDOException $e) {
    $result['status'] = 'error';
    $result['message'] = 'Erro de conexão PDO: ' . $e->getMessage();
    $result['details']['error_code'] = $e->getCode();
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['message'] = 'Erro geral: ' . $e->getMessage();
}

// Adicionar informações do ambiente
$result['environment'] = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

// Retornar resultado
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
?>

