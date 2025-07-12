<?php
// ================================================
// SISTEMA ENEDES - AUTENTICAÇÃO
// Arquivo: auth.php
// ================================================

require_once 'config.php';

// Obter método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Roteamento de ações
switch ($action) {
    case 'login':
        handleLogin($input);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'change_password':
        handleChangePassword($input);
        break;
    case 'check_session':
        handleCheckSession();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não encontrada']);
}

// ================================================
// FUNÇÃO DE LOGIN
// ================================================

function handleLogin($input) {
    global $pdo;
    
    $username = sanitizeInput($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Usuário e senha são obrigatórios']);
    }
    
    try {
        // Buscar usuário no banco
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   COALESCE(
                       CASE 
                           WHEN u.role IN ('general_coordinator', 'project_coordinator') THEN 
                               (SELECT array_to_string(array_agg(name), ',') FROM sections WHERE is_active = true)
                           ELSE 
                               (SELECT array_to_string(array_agg(s.name), ',') 
                                FROM user_sections us 
                                JOIN sections s ON us.section_id = s.id 
                                WHERE us.user_id = u.id AND s.is_active = true)
                       END, ''
                   ) as sections
            FROM users u
            WHERE u.username = ? AND u.is_active = true
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'Usuário não encontrado ou inativo']);
        }
        
        // Verificar senha (para demo, usando senha simples)
        if ($password !== '123456' && $password !== $user['password']) {
            jsonResponse(['success' => false, 'message' => 'Senha incorreta']);
        }
        
        // Preparar dados do usuário
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'department' => $user['department'],
            'phone' => $user['phone'],
            'sections' => $user['sections'] ? explode(',', $user['sections']) : [],
            'isActive' => (bool)$user['is_active'],
            'needsPasswordChange' => (bool)$user['needs_password_change']
        ];
        
        // Log do login
        logActivity($user['id'], 'LOGIN', null, null, null, json_encode(['username' => $username]));
        
        // Resposta de sucesso
        jsonResponse([
            'success' => true, 
            'message' => 'Login realizado com sucesso',
            'user' => $userData
        ]);
        
    } catch (Exception $e) {
        error_log("Erro no login: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

// ================================================
// FUNÇÃO DE LOGOUT
// ================================================

function handleLogout() {
    // Em uma implementação real, invalidaria a sessão/token
    jsonResponse(['success' => true, 'message' => 'Logout realizado com sucesso']);
}

// ================================================
// FUNÇÃO DE MUDANÇA DE SENHA
// ================================================

function handleChangePassword($input) {
    global $pdo;
    
    $username = sanitizeInput($input['username'] ?? '');
    $currentPassword = $input['currentPassword'] ?? '';
    $newPassword = $input['newPassword'] ?? '';
    
    if (empty($username) || empty($currentPassword) || empty($newPassword)) {
        jsonResponse(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
    }
    
    if (strlen($newPassword) < 6) {
        jsonResponse(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres']);
    }
    
    try {
        // Verificar senha atual
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = true");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || $currentPassword !== $user['password']) {
            jsonResponse(['success' => false, 'message' => 'Senha atual incorreta']);
        }
        
        // Atualizar senha
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, needs_password_change = false, updated_at = NOW()
            WHERE username = ?
        ");
        $stmt->execute([$newPassword, $username]);
        
        // Log da mudança de senha
        logActivity($user['id'], 'PASSWORD_CHANGE', 'users', $user['id']);
        
        jsonResponse(['success' => true, 'message' => 'Senha alterada com sucesso']);
        
    } catch (Exception $e) {
        error_log("Erro na mudança de senha: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

// ================================================
// FUNÇÃO DE VERIFICAÇÃO DE SESSÃO
// ================================================

function handleCheckSession() {
    // Em uma implementação real, verificaria token JWT ou sessão
    jsonResponse(['success' => true, 'authenticated' => false]);
}

// ================================================
// DADOS INICIAIS PARA DEMO
// ================================================

function initializeDemoUsers() {
    global $pdo;
    
    $demoUsers = [
        [
            'username' => 'coord_geral',
            'name' => 'Coordenador Geral',
            'email' => 'geral@enedes.ifb.edu.br',
            'role' => 'general_coordinator',
            'department' => 'Coordenação Geral',
            'phone' => '(61) 99999-9999'
        ],
        [
            'username' => 'coord_projeto',
            'name' => 'Coordenador de Projeto',
            'email' => 'projeto@enedes.ifb.edu.br',
            'role' => 'project_coordinator',
            'department' => 'Coordenação de Projeto',
            'phone' => '(61) 99999-9998'
        ],
        [
            'username' => 'coord_metas',
            'name' => 'Flavia Furtado',
            'email' => 'flavia@enedes.ifb.edu.br',
            'role' => 'department_coordinator',
            'department' => 'Metas ENEDES',
            'phone' => '(61) 98888-8888'
        ],
        [
            'username' => 'coord_lab_consumer',
            'name' => 'Coordenador Lab Consumer',
            'email' => 'labconsumer@enedes.ifb.edu.br',
            'role' => 'department_coordinator',
            'department' => 'Lab Consumer',
            'phone' => '(61) 97777-7777'
        ],
        [
            'username' => 'coord_rota',
            'name' => 'Coordenador Rota Empreendedora',
            'email' => 'rota@enedes.ifb.edu.br',
            'role' => 'department_coordinator',
            'department' => 'Rota Empreendedora',
            'phone' => '(61) 96666-6666'
        ],
        [
            'username' => 'coord_ifb_mais',
            'name' => 'Mariana Rego',
            'email' => 'mariana.ifb@enedes.ifb.edu.br',
            'role' => 'department_coordinator',
            'department' => 'IFB Mais Empreendedor',
            'phone' => '(61) 95555-5555'
        ],
        [
            'username' => 'coord_estudio',
            'name' => 'Coordenador do Estúdio',
            'email' => 'estudio@enedes.ifb.edu.br',
            'role' => 'department_coordinator',
            'department' => 'Estúdio',
            'phone' => '(61) 94444-4444'
        ],
        [
            'username' => 'coord_lab_varejo',
            'name' => 'Coordenador Lab Varejo',
            'email' => 'labvarejo@enedes.ifb.edu.br',
            'role' => 'department_coordinator',
            'department' => 'Lab Varejo',
            'phone' => '(61) 93333-3333'
        ],
        [
            'username' => 'coord_sala',
            'name' => 'CARLA E TSI',
            'email' => 'carla@enedes.ifb.edu.br',
            'role' => 'department_coordinator',
            'department' => 'Sala Interativa',
            'phone' => '(61) 92222-2222'
        ],
        [
            'username' => 'coord_marketing',
            'name' => 'Marketing Team',
            'email' => 'marketing@enedes.ifb.edu.br',
            'role' => 'department_coordinator',
            'department' => 'Agência de Marketing',
            'phone' => '(61) 91111-1111'
        ]
    ];
    
    try {
        foreach ($demoUsers as $user) {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, name, email, password, role, department, phone, is_active, needs_password_change, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, true, false, NOW())
                ON CONFLICT (username) DO NOTHING
            ");
            $stmt->execute([
                $user['username'],
                $user['name'],
                $user['email'],
                '123456',
                $user['role'],
                $user['department'],
                $user['phone']
            ]);
        }
    } catch (Exception $e) {
        error_log("Erro ao inicializar usuários demo: " . $e->getMessage());
    }
}

// Inicializar usuários demo se necessário
if (isset($_GET['init_demo']) && $_GET['init_demo'] === 'true') {
    initializeDemoUsers();
    jsonResponse(['success' => true, 'message' => 'Usuários demo inicializados']);
}

?>

