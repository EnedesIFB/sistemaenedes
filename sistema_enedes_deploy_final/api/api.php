<?php
// ================================================
// SISTEMA ENEDES - API BACKEND
// Arquivo: api.php
// ================================================

require_once 'config.php';

// Obter método e endpoint
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['endpoint'] ?? '';

// Roteamento principal
switch ($path) {
    case 'login':
        handleLogin();
        break;
    case 'users':
        handleUsers();
        break;
    case 'sections':
        handleSections();
        break;
    case 'collaborators':
        handleCollaborators();
        break;
    case 'actions':
        handleActions();
        break;
    case 'follow_ups':
        handleFollowUps();
        break;
    case 'notifications':
        handleNotifications();
        break;
    case 'dashboard':
        handleDashboard();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Endpoint não encontrado']);
}

// ================================================
// FUNÇÕES DE LOGIN E AUTENTICAÇÃO
// ================================================

function handleLogin() {
    global $pdo, $method;
    
    if ($method !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Método não permitido']);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $username = sanitizeInput($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Username e senha são obrigatórios']);
    }
    
    try {
        // Buscar usuário
        $stmt = $pdo->prepare("
            SELECT u.*, GROUP_CONCAT(s.name) as sections
            FROM users u
            LEFT JOIN user_sections us ON u.id = us.user_id
            LEFT JOIN sections s ON us.section_id = s.id
            WHERE u.username = ? AND u.is_active = 1
            GROUP BY u.id
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || $user['password'] !== $password) {
            jsonResponse(['success' => false, 'message' => 'Credenciais inválidas']);
        }
        
        // Preparar resposta
        $userData = [
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
        
        jsonResponse(['success' => true, 'user' => $userData]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

// ================================================
// FUNÇÕES DE USUÁRIOS
// ================================================

function handleUsers() {
    global $pdo, $method;
    
    switch ($method) {
        case 'GET':
            getUsersList();
            break;
        case 'POST':
            createUser();
            break;
        case 'PUT':
            updateUser();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido']);
    }
}

function getUsersList() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT u.*, GROUP_CONCAT(s.name) as sections
            FROM users u
            LEFT JOIN user_sections us ON u.id = us.user_id
            LEFT JOIN sections s ON us.section_id = s.id
            GROUP BY u.id
            ORDER BY u.name
        ");
        $users = $stmt->fetchAll();
        
        $result = [];
        foreach ($users as $user) {
            $result[$user['username']] = [
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'department' => $user['department'],
                'phone' => $user['phone'],
                'sections' => $user['sections'] ? explode(',', $user['sections']) : [],
                'isActive' => (bool)$user['is_active'],
                'needsPasswordChange' => (bool)$user['needs_password_change'],
                'createdAt' => $user['created_at']
            ];
        }
        
        jsonResponse(['success' => true, 'users' => $result]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao buscar usuários']);
    }
}

// ================================================
// FUNÇÕES DE SEÇÕES
// ================================================

function handleSections() {
    global $pdo, $method;
    
    if ($method !== 'GET') {
        jsonResponse(['success' => false, 'message' => 'Método não permitido']);
    }
    
    try {
        $stmt = $pdo->query("SELECT * FROM sections WHERE is_active = 1 ORDER BY name");
        $sections = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'sections' => $sections]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao buscar seções']);
    }
}

// ================================================
// FUNÇÕES DE COLABORADORES
// ================================================

function handleCollaborators() {
    global $pdo, $method;
    
    switch ($method) {
        case 'GET':
            getCollaboratorsList();
            break;
        case 'POST':
            createCollaborator();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido']);
    }
}

function getCollaboratorsList() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM collaborators ORDER BY name");
        $collaborators = $stmt->fetchAll();
        
        $result = [];
        foreach ($collaborators as $collaborator) {
            $result['colab_' . $collaborator['id']] = [
                'name' => $collaborator['name'],
                'email' => $collaborator['email'],
                'phone' => $collaborator['phone'],
                'department' => $collaborator['department'],
                'position' => $collaborator['position'],
                'skills' => json_decode($collaborator['skills'], true) ?? [],
                'isActive' => (bool)$collaborator['is_active'],
                'createdAt' => $collaborator['created_at']
            ];
        }
        
        jsonResponse(['success' => true, 'collaborators' => $result]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao buscar colaboradores']);
    }
}

function createCollaborator() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = sanitizeInput($input['name'] ?? '');
    $email = sanitizeInput($input['email'] ?? '');
    $phone = sanitizeInput($input['phone'] ?? '');
    $department = sanitizeInput($input['department'] ?? '');
    $position = sanitizeInput($input['position'] ?? '');
    $skills = $input['skills'] ?? [];
    $isActive = $input['isActive'] ?? true;
    
    if (empty($name) || empty($email)) {
        jsonResponse(['success' => false, 'message' => 'Nome e email são obrigatórios']);
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO collaborators (name, email, phone, department, position, skills, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name, $email, $phone, $department, $position,
            json_encode($skills), $isActive ? 1 : 0
        ]);
        
        jsonResponse(['success' => true, 'message' => 'Colaborador criado com sucesso']);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao criar colaborador']);
    }
}

// ================================================
// FUNÇÕES DE AÇÕES
// ================================================

function handleActions() {
    global $pdo, $method;
    
    switch ($method) {
        case 'GET':
            getActionsList();
            break;
        case 'POST':
            createAction();
            break;
        case 'PUT':
            updateAction();
            break;
        case 'DELETE':
            deleteAction();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido']);
    }
}

function getActionsList() {
    global $pdo;
    
    try {
        // Buscar ações com suas tarefas
        $stmt = $pdo->query("
            SELECT a.*, s.name as section_name
            FROM actions a
            JOIN sections s ON a.section_id = s.id
            ORDER BY s.name, a.deadline
        ");
        $actions = $stmt->fetchAll();
        
        // Buscar tarefas de cada ação
        $actionsWithTasks = [];
        foreach ($actions as $action) {
            $tasksStmt = $pdo->prepare("
                SELECT * FROM action_tasks 
                WHERE action_id = ? 
                ORDER BY order_index
            ");
            $tasksStmt->execute([$action['id']]);
            $tasks = $tasksStmt->fetchAll();
            
            // Buscar anexos
            $attachmentsStmt = $pdo->prepare("
                SELECT original_name FROM action_attachments 
                WHERE action_id = ?
            ");
            $attachmentsStmt->execute([$action['id']]);
            $attachments = array_column($attachmentsStmt->fetchAll(), 'original_name');
            
            $actionData = [
                'id' => $action['id'],
                'task' => $action['task'],
                'description' => $action['description'],
                'responsible' => $action['responsible'],
                'budget' => $action['budget'],
                'deadline' => $action['deadline'],
                'priority' => $action['priority'],
                'status' => $action['status'],
                'progress' => $action['progress'],
                'completed' => (bool)$action['completed'],
                'validated' => (bool)$action['validated'],
                'note' => $action['note'],
                'createdDate' => $action['created_at'],
                'tasks' => array_map(function($task) {
                    return [
                        'id' => $task['id'],
                        'description' => $task['description'],
                        'completed' => (bool)$task['completed']
                    ];
                }, $tasks),
                'attachments' => $attachments
            ];
            
            if (!isset($actionsWithTasks[$action['section_name']])) {
                $actionsWithTasks[$action['section_name']] = [];
            }
            $actionsWithTasks[$action['section_name']][] = $actionData;
        }
        
        jsonResponse(['success' => true, 'actions' => $actionsWithTasks]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao buscar ações']);
    }
}

function createAction() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sectionName = sanitizeInput($input['section'] ?? '');
    $task = sanitizeInput($input['task'] ?? '');
    $responsible = sanitizeInput($input['responsible'] ?? '');
    $deadline = $input['deadline'] ?? '';
    $budget = sanitizeInput($input['budget'] ?? '');
    $description = sanitizeInput($input['description'] ?? '');
    $note = sanitizeInput($input['note'] ?? '');
    $priority = $input['priority'] ?? 'medium';
    $tasks = $input['tasks'] ?? [];
    
    if (empty($task) || empty($responsible) || empty($deadline)) {
        jsonResponse(['success' => false, 'message' => 'Campos obrigatórios não preenchidos']);
    }
    
    try {
        $pdo->beginTransaction();
        
        // Buscar ID da seção
        $stmt = $pdo->prepare("SELECT id FROM sections WHERE name = ?");
        $stmt->execute([$sectionName]);
        $section = $stmt->fetch();
        
        if (!$section) {
            throw new Exception('Seção não encontrada');
        }
        
        // Calcular progresso baseado nas tarefas
        $progress = 0;
        if (!empty($tasks)) {
            $completedTasks = array_filter($tasks, function($task) {
                return $task['completed'] ?? false;
            });
            $progress = round((count($completedTasks) / count($tasks)) * 100);
        }
        
        // Determinar status baseado no progresso
        $status = 'pendente';
        if ($progress >= 100) {
            $status = 'concluida';
        } elseif ($progress >= 70) {
            $status = 'em_andamento';
        } elseif ($progress >= 31) {
            $status = 'alerta';
        }
        
        // Inserir ação
        $stmt = $pdo->prepare("
            INSERT INTO actions (section_id, task, description, responsible, budget, deadline, priority, status, progress, note, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $section['id'], $task, $description, $responsible, $budget, $deadline, $priority, $status, $progress, $note, 1
        ]);
        
        $actionId = $pdo->lastInsertId();
        
        // Inserir tarefas
        foreach ($tasks as $index => $taskData) {
            $stmt = $pdo->prepare("
                INSERT INTO action_tasks (action_id, description, completed, order_index)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $actionId,
                sanitizeInput($taskData['description'] ?? ''),
                $taskData['completed'] ? 1 : 0,
                $index
            ]);
        }
        
        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Ação criada com sucesso']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Erro ao criar ação']);
    }
}

// ================================================
// FUNÇÕES DE FOLLOW-UPS
// ================================================

function handleFollowUps() {
    global $pdo, $method;
    
    if ($method === 'POST') {
        createFollowUp();
    } else {
        jsonResponse(['success' => false, 'message' => 'Método não permitido']);
    }
}

function createFollowUp() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $actionId = $input['actionId'] ?? 0;
    $assignedTo = $input['assignedTo'] ?? 0;
    $description = sanitizeInput($input['description'] ?? '');
    $endDate = $input['endDate'] ?? '';
    $priority = $input['priority'] ?? 'medium';
    
    if (empty($actionId) || empty($assignedTo) || empty($description) || empty($endDate)) {
        jsonResponse(['success' => false, 'message' => 'Campos obrigatórios não preenchidos']);
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO follow_ups (action_id, assigned_to, created_by, title, description, priority, end_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $actionId, $assignedTo, 1, 'Follow-up', $description, $priority, $endDate
        ]);
        
        jsonResponse(['success' => true, 'message' => 'Follow-up criado com sucesso']);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao criar follow-up']);
    }
}

// ================================================
// FUNÇÕES DE NOTIFICAÇÕES
// ================================================

function handleNotifications() {
    global $pdo, $method;
    
    if ($method !== 'GET') {
        jsonResponse(['success' => false, 'message' => 'Método não permitido']);
    }
    
    try {
        $stmt = $pdo->query("
            SELECT * FROM notifications 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $notifications = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'notifications' => $notifications]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao buscar notificações']);
    }
}

// ================================================
// FUNÇÕES DE DASHBOARD
// ================================================

function handleDashboard() {
    global $pdo, $method;
    
    if ($method !== 'GET') {
        jsonResponse(['success' => false, 'message' => 'Método não permitido']);
    }
    
    try {
        // Estatísticas gerais
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN completed = 1 THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as in_progress,
                COUNT(CASE WHEN status = 'alerta' THEN 1 END) as alert
            FROM actions
        ");
        $stats = $stmt->fetch();
        
        jsonResponse(['success' => true, 'stats' => $stats]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao buscar estatísticas']);
    }
}

// ================================================
// FUNÇÕES AUXILIARES
// ================================================

function logActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $action, $tableName, $recordId, $oldValues, $newValues, $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log silencioso
    }
}

?>


