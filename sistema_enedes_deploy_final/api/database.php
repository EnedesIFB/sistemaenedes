<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$host = $_ENV['PGHOST'] ?? getenv('PGHOST') ?? 'localhost';
$port = $_ENV['PGPORT'] ?? getenv('PGPORT') ?? '5432';
$dbname = $_ENV['PGDATABASE'] ?? getenv('PGDATABASE') ?? 'sistemaenedes';
$username = $_ENV['PGUSER'] ?? getenv('PGUSER') ?? 'postgres';
$password = $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD') ?? '';

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 30
    ]);
    
    $pdo->exec("SET timezone = 'America/Sao_Paulo'");
    
    if (isset($_GET['test'])) {
        $stmt = $pdo->query("SELECT version() as version, current_timestamp as time");
        $result = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conexão estabelecida com sucesso!',
            'database' => $dbname,
            'version' => $result['version'],
            'time' => $result['time']
        ]);
        exit;
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com o banco de dados',
        'error' => $e->getMessage()
    ]);
    exit;
}

function getConnection() {
    global $pdo;
    return $pdo;
}
?>

