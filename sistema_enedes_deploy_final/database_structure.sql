-- ================================================
-- SISTEMA ENEDES - ESTRUTURA DO BANCO DE DADOS
-- PostgreSQL (Neon Database)
-- ================================================

-- Extensões necessárias
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Tabela de usuários do sistema
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL DEFAULT '123456',
    role VARCHAR(50) NOT NULL DEFAULT 'department_coordinator',
    department VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT true,
    needs_password_change BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de seções/departamentos
CREATE TABLE IF NOT EXISTS sections (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de relacionamento usuário-seção
CREATE TABLE IF NOT EXISTS user_sections (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    section_id INTEGER REFERENCES sections(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, section_id)
);

-- Tabela de colaboradores
CREATE TABLE IF NOT EXISTS collaborators (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    position VARCHAR(100),
    skills JSONB DEFAULT '[]',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de ações/projetos
CREATE TABLE IF NOT EXISTS actions (
    id SERIAL PRIMARY KEY,
    section_id INTEGER REFERENCES sections(id),
    task VARCHAR(255) NOT NULL,
    description TEXT,
    responsible VARCHAR(100) NOT NULL,
    budget VARCHAR(50),
    deadline DATE NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(50) DEFAULT 'pendente',
    progress INTEGER DEFAULT 0,
    completed BOOLEAN DEFAULT false,
    validated BOOLEAN DEFAULT false,
    note TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de tarefas das ações
CREATE TABLE IF NOT EXISTS action_tasks (
    id SERIAL PRIMARY KEY,
    action_id INTEGER REFERENCES actions(id) ON DELETE CASCADE,
    description TEXT NOT NULL,
    completed BOOLEAN DEFAULT false,
    order_index INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de anexos das ações
CREATE TABLE IF NOT EXISTS action_attachments (
    id SERIAL PRIMARY KEY,
    action_id INTEGER REFERENCES actions(id) ON DELETE CASCADE,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER,
    mime_type VARCHAR(100),
    uploaded_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de follow-ups
CREATE TABLE IF NOT EXISTS follow_ups (
    id SERIAL PRIMARY KEY,
    action_id INTEGER REFERENCES actions(id) ON DELETE CASCADE,
    assigned_to INTEGER REFERENCES collaborators(id),
    created_by INTEGER REFERENCES users(id),
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(50) DEFAULT 'pending',
    start_date DATE,
    end_date DATE,
    next_steps TEXT,
    obstacles TEXT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT false,
    action_id INTEGER REFERENCES actions(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de logs de atividade
CREATE TABLE IF NOT EXISTS activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INTEGER,
    old_values JSONB,
    new_values JSONB,
    ip_address INET,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ================================================
-- INSERIR DADOS INICIAIS
-- ================================================

-- Inserir seções padrão
INSERT INTO sections (name, description) VALUES
('Cronograma de Execução', 'Gestão do cronograma geral de execução do projeto'),
('Metas ENEDES', 'Acompanhamento das metas e objetivos da ENEDES'),
('Lab Consumer', 'Laboratório de análise comportamental do consumidor'),
('Lab Varejo', 'Laboratório de varejo e experiência do cliente'),
('IFB Mais Empreendedor', 'Programa de empreendedorismo do IFB'),
('Rota Empreendedora', 'Rota empreendedora móvel pelo Distrito Federal'),
('Estúdio', 'Estúdio de produção audiovisual'),
('Sala Interativa', 'Sala com tecnologias interativas e VR'),
('Agência de Marketing', 'Agência de marketing e comunicação')
ON CONFLICT (name) DO NOTHING;

-- Inserir usuários padrão
INSERT INTO users (username, name, email, password, role, department, phone) VALUES
('coord_geral', 'Coordenador Geral', 'geral@enedes.ifb.edu.br', '123456', 'general_coordinator', 'Coordenação Geral', '(61) 99999-9999'),
('coord_projeto', 'Coordenador de Projeto', 'projeto@enedes.ifb.edu.br', '123456', 'project_coordinator', 'Coordenação de Projeto', '(61) 99999-9998'),
('coord_metas', 'Flavia Furtado', 'flavia@enedes.ifb.edu.br', '123456', 'department_coordinator', 'Metas ENEDES', '(61) 98888-8888'),
('coord_lab_consumer', 'Coordenador Lab Consumer', 'labconsumer@enedes.ifb.edu.br', '123456', 'department_coordinator', 'Lab Consumer', '(61) 97777-7777'),
('coord_rota', 'Coordenador Rota Empreendedora', 'rota@enedes.ifb.edu.br', '123456', 'department_coordinator', 'Rota Empreendedora', '(61) 96666-6666'),
('coord_ifb_mais', 'Mariana Rego', 'mariana.ifb@enedes.ifb.edu.br', '123456', 'department_coordinator', 'IFB Mais Empreendedor', '(61) 95555-5555'),
('coord_estudio', 'Coordenador do Estúdio', 'estudio@enedes.ifb.edu.br', '123456', 'department_coordinator', 'Estúdio', '(61) 94444-4444'),
('coord_lab_varejo', 'Coordenador Lab Varejo', 'labvarejo@enedes.ifb.edu.br', '123456', 'department_coordinator', 'Lab Varejo', '(61) 93333-3333'),
('coord_sala', 'CARLA E TSI', 'carla@enedes.ifb.edu.br', '123456', 'department_coordinator', 'Sala Interativa', '(61) 92222-2222'),
('coord_marketing', 'Marketing Team', 'marketing@enedes.ifb.edu.br', '123456', 'department_coordinator', 'Agência de Marketing', '(61) 91111-1111')
ON CONFLICT (username) DO NOTHING;

-- Associar coordenadores de departamento às suas seções
INSERT INTO user_sections (user_id, section_id)
SELECT u.id, s.id
FROM users u, sections s
WHERE (u.username = 'coord_metas' AND s.name = 'Metas ENEDES')
   OR (u.username = 'coord_lab_consumer' AND s.name = 'Lab Consumer')
   OR (u.username = 'coord_rota' AND s.name = 'Rota Empreendedora')
   OR (u.username = 'coord_ifb_mais' AND s.name = 'IFB Mais Empreendedor')
   OR (u.username = 'coord_estudio' AND s.name = 'Estúdio')
   OR (u.username = 'coord_lab_varejo' AND s.name = 'Lab Varejo')
   OR (u.username = 'coord_sala' AND s.name = 'Sala Interativa')
   OR (u.username = 'coord_marketing' AND s.name = 'Agência de Marketing')
ON CONFLICT (user_id, section_id) DO NOTHING;

-- Inserir colaboradores de exemplo
INSERT INTO collaborators (name, email, phone, department, position, skills) VALUES
('João Silva', 'joao.silva@enedes.ifb.edu.br', '(61) 95555-5555', 'Desenvolvimento', 'Desenvolvedor Sênior', '["React", "Node.js", "Python"]'),
('Maria Santos', 'maria.santos@enedes.ifb.edu.br', '(61) 94444-4444', 'Design', 'Designer UX/UI', '["Figma", "Adobe Creative", "Prototipagem"]'),
('Pedro Oliveira', 'pedro.oliveira@enedes.ifb.edu.br', '(61) 93333-3333', 'Marketing', 'Analista de Marketing', '["Marketing Digital", "Analytics", "SEO"]'),
('Ana Costa', 'ana.costa@enedes.ifb.edu.br', '(61) 92222-2222', 'Gestão', 'Analista de Projetos', '["Gestão de Projetos", "Scrum", "Excel"]'),
('Carlos Mendes', 'carlos.mendes@enedes.ifb.edu.br', '(61) 91111-1111', 'Tecnologia', 'Especialista em TI', '["Infraestrutura", "Redes", "Segurança"]')
ON CONFLICT (email) DO NOTHING;

-- Inserir algumas ações de exemplo
INSERT INTO actions (section_id, task, description, responsible, budget, deadline, priority, status, progress, note, created_by)
SELECT 
    s.id,
    'Estrutura Construtiva',
    'Construção da estrutura física dos espaços da ENEDES',
    'Equipe Obras',
    'R$ 380.000',
    '2025-10-31',
    'high',
    'alerta',
    45,
    'Aguardando aprovação final do projeto arquitetônico',
    1
FROM sections s WHERE s.name = 'Cronograma de Execução'
ON CONFLICT DO NOTHING;

INSERT INTO actions (section_id, task, description, responsible, budget, deadline, priority, status, progress, note, created_by)
SELECT 
    s.id,
    'Criar um currículo abrangente e atualizado',
    'Capacitação Educacional - Melhoria significativa na escolaridade dos empreendedores',
    'Flavia Furtado',
    'R$ 45.000',
    '2025-12-31',
    'medium',
    'alerta',
    45,
    'Revisão curricular em andamento',
    1
FROM sections s WHERE s.name = 'Metas ENEDES'
ON CONFLICT DO NOTHING;

INSERT INTO actions (section_id, task, description, responsible, budget, deadline, priority, status, progress, note, created_by)
SELECT 
    s.id,
    'Montagem completa do estúdio',
    'Montagem completa do estúdio de produção audiovisual',
    'Coordenador do Estúdio',
    'R$ 145.000',
    '2025-10-31',
    'low',
    'concluida',
    100,
    'Estúdio operacional e em funcionamento',
    1
FROM sections s WHERE s.name = 'Estúdio'
ON CONFLICT DO NOTHING;

-- ================================================
-- ÍNDICES PARA PERFORMANCE
-- ================================================

CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);
CREATE INDEX IF NOT EXISTS idx_actions_section ON actions(section_id);
CREATE INDEX IF NOT EXISTS idx_actions_status ON actions(status);
CREATE INDEX IF NOT EXISTS idx_actions_deadline ON actions(deadline);
CREATE INDEX IF NOT EXISTS idx_action_tasks_action ON action_tasks(action_id);
CREATE INDEX IF NOT EXISTS idx_follow_ups_action ON follow_ups(action_id);
CREATE INDEX IF NOT EXISTS idx_follow_ups_assigned ON follow_ups(assigned_to);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_read ON notifications(is_read);
CREATE INDEX IF NOT EXISTS idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON activity_logs(created_at);

-- ================================================
-- TRIGGERS PARA ATUALIZAÇÃO AUTOMÁTICA
-- ================================================

-- Função para atualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers para updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_collaborators_updated_at BEFORE UPDATE ON collaborators FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_actions_updated_at BEFORE UPDATE ON actions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_action_tasks_updated_at BEFORE UPDATE ON action_tasks FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_follow_ups_updated_at BEFORE UPDATE ON follow_ups FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ================================================
-- VIEWS ÚTEIS
-- ================================================

-- View de usuários com suas seções
CREATE OR REPLACE VIEW user_sections_view AS
SELECT 
    u.id,
    u.username,
    u.name,
    u.email,
    u.role,
    u.department,
    u.is_active,
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
WHERE u.is_active = true;

-- View de estatísticas das ações
CREATE OR REPLACE VIEW action_stats_view AS
SELECT 
    s.name as section_name,
    COUNT(*) as total_actions,
    COUNT(CASE WHEN a.completed = true THEN 1 END) as completed_actions,
    COUNT(CASE WHEN a.status = 'em_andamento' THEN 1 END) as in_progress_actions,
    COUNT(CASE WHEN a.status = 'alerta' THEN 1 END) as alert_actions,
    COUNT(CASE WHEN a.status = 'pendente' THEN 1 END) as pending_actions,
    ROUND(AVG(a.progress), 2) as avg_progress
FROM sections s
LEFT JOIN actions a ON s.id = a.section_id
WHERE s.is_active = true
GROUP BY s.id, s.name
ORDER BY s.name;

-- ================================================
-- COMENTÁRIOS FINAIS
-- ================================================

COMMENT ON TABLE users IS 'Usuários do sistema com diferentes níveis de acesso';
COMMENT ON TABLE sections IS 'Seções/departamentos do projeto ENEDES';
COMMENT ON TABLE actions IS 'Ações e projetos em andamento';
COMMENT ON TABLE collaborators IS 'Colaboradores que podem ser atribuídos a tarefas';
COMMENT ON TABLE follow_ups IS 'Sistema de acompanhamento e follow-up das ações';
COMMENT ON TABLE notifications IS 'Notificações do sistema para os usuários';
COMMENT ON TABLE activity_logs IS 'Log de todas as atividades realizadas no sistema';

-- Finalização
SELECT 'Estrutura do banco de dados criada com sucesso!' as status;

