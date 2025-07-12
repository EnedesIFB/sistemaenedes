# Sistema ENEDES - Guia de Deploy

## 📋 Visão Geral

Sistema completo de gestão de projetos e ações da ENEDES (Escola de Negócios e Desenvolvimento Social do Instituto Federal de Brasília).

## 🚀 Deploy no Vercel

### 1. Preparação do Repositório Git

```bash
# Criar novo repositório no GitHub
# Fazer upload dos arquivos ou usar git

git init
git add .
git commit -m "Sistema ENEDES - Deploy inicial"
git branch -M main
git remote add origin https://github.com/SEU_USUARIO/sistema-enedes.git
git push -u origin main
```

### 2. Deploy no Vercel

1. Acesse [vercel.com](https://vercel.com)
2. Conecte sua conta GitHub
3. Clique em "New Project"
4. Selecione o repositório `sistema-enedes`
5. Configure as variáveis de ambiente (ver seção abaixo)
6. Clique em "Deploy"

### 3. Configuração do Banco de Dados Neon

1. Acesse [neon.tech](https://neon.tech)
2. Crie uma nova conta ou faça login
3. Crie um novo projeto PostgreSQL
4. Copie a string de conexão
5. Execute o arquivo `database_structure.sql` no console do Neon

#### Executar SQL no Neon:
1. No painel do Neon, vá em "SQL Editor"
2. Cole o conteúdo do arquivo `database_structure.sql`
3. Execute o script
4. Verifique se as tabelas foram criadas

### 4. Variáveis de Ambiente no Vercel

No painel do Vercel, vá em Settings > Environment Variables e adicione:

```
DB_HOST=ep-white-thunder-a5ixkfxe.us-east-2.aws.neon.tech
DB_NAME=neondb
DB_USER=neondb_owner
DB_PASSWORD=SUA_SENHA_REAL_DO_NEON
DB_PORT=5432
```

**⚠️ IMPORTANTE:** Substitua `SUA_SENHA_REAL_DO_NEON` pela senha real do seu banco Neon.

## 📁 Estrutura do Projeto

```
sistema-enedes/
├── login.html              # Página de login
├── dashboard.html           # Dashboard principal
├── vercel.json             # Configuração do Vercel
├── database_structure.sql  # Estrutura do banco PostgreSQL
├── api/
│   ├── config.php          # Configuração do banco
│   ├── auth.php            # Autenticação
│   └── api/
│       ├── actions.php     # API de ações (futuro)
│       └── auth.php        # API de auth (futuro)
└── README.md               # Este arquivo
```

## 🔧 Configuração Local (Desenvolvimento)

### Pré-requisitos
- PHP 8.0+
- PostgreSQL ou acesso ao Neon
- Servidor web (Apache/Nginx) ou PHP built-in server

### Configuração Local

1. **Clone o repositório:**
```bash
git clone https://github.com/SEU_USUARIO/sistema-enedes.git
cd sistema-enedes
```

2. **Configure o banco de dados:**
   - Edite `api/config.php` com suas credenciais
   - Execute `database_structure.sql` no seu banco

3. **Inicie o servidor local:**
```bash
php -S localhost:8000
```

4. **Acesse o sistema:**
   - Abra `http://localhost:8000/login.html`

## 👥 Usuários Padrão

O sistema vem com usuários pré-configurados:

| Usuário | Senha | Perfil |
|---------|-------|--------|
| coord_geral | 123456 | Coordenador Geral |
| coord_projeto | 123456 | Coordenador de Projeto |
| coord_metas | 123456 | Flavia Furtado (Metas) |
| coord_lab_consumer | 123456 | Lab Consumer |
| coord_rota | 123456 | Rota Empreendedora |
| coord_ifb_mais | 123456 | Mariana Rego (IFB Mais) |
| coord_estudio | 123456 | Estúdio |
| coord_lab_varejo | 123456 | Lab Varejo |
| coord_sala | 123456 | CARLA E TSI (Sala Interativa) |
| coord_marketing | 123456 | Marketing Team |

## 🔐 Níveis de Acesso

### Coordenador Geral
- Acesso total a todas as seções
- Pode gerenciar usuários e colaboradores
- Visualiza todas as ações e relatórios

### Coordenador de Projeto
- Acesso total a todas as seções
- Pode gerenciar usuários e colaboradores
- Visualiza todas as ações e relatórios

### Coordenador de Departamento
- Acesso apenas às seções atribuídas
- Pode gerenciar ações de sua seção
- Visualiza relatórios de sua seção

## 📊 Seções do Sistema

1. **Cronograma de Execução** - Gestão do cronograma geral
2. **Metas ENEDES** - Acompanhamento de metas e objetivos
3. **Lab Consumer** - Laboratório de análise comportamental
4. **Lab Varejo** - Laboratório de varejo
5. **IFB Mais Empreendedor** - Programa de empreendedorismo
6. **Rota Empreendedora** - Rota móvel pelo DF
7. **Estúdio** - Produção audiovisual
8. **Sala Interativa** - Tecnologias interativas e VR
9. **Agência de Marketing** - Marketing e comunicação

## 🛠️ Funcionalidades Implementadas

### ✅ Concluído
- [x] Sistema de login com diferentes perfis
- [x] Dashboard responsivo
- [x] Estrutura do banco de dados
- [x] Configuração para deploy no Vercel
- [x] Autenticação básica via API PHP
- [x] Interface de usuário moderna e responsiva

### 🚧 Em Desenvolvimento
- [ ] CRUD completo de ações
- [ ] Sistema de follow-ups
- [ ] Gestão de colaboradores
- [ ] Notificações em tempo real
- [ ] Relatórios e exportação
- [ ] Upload de arquivos
- [ ] Sistema de comentários

## 🔧 Solução de Problemas

### Erro de Conexão com Banco
1. Verifique as credenciais no `api/config.php`
2. Confirme se o banco Neon está ativo
3. Teste a conexão diretamente no console do Neon

### Erro 500 no Vercel
1. Verifique os logs no painel do Vercel
2. Confirme se as variáveis de ambiente estão configuradas
3. Verifique se o `vercel.json` está correto

### Página não carrega
1. Verifique se o arquivo existe
2. Confirme as rotas no `vercel.json`
3. Teste localmente primeiro

## 📞 Suporte

Para suporte técnico ou dúvidas:
- Verifique os logs do Vercel
- Teste localmente para isolar problemas
- Confirme se o banco de dados está funcionando

## 🔄 Atualizações

Para atualizar o sistema:
1. Faça as alterações localmente
2. Teste em ambiente local
3. Commit e push para o GitHub
4. O Vercel fará deploy automático

## 📝 Notas Importantes

- **Senhas padrão:** Todos os usuários têm senha `123456` por padrão
- **Banco de dados:** Use PostgreSQL (Neon recomendado)
- **CORS:** Configurado para aceitar requisições de qualquer origem
- **Arquivos:** Sistema preparado para upload futuro
- **Logs:** Todas as ações são registradas na tabela `activity_logs`

## 🎯 Próximos Passos

1. **Deploy inicial:** Siga este guia para fazer o primeiro deploy
2. **Teste básico:** Faça login e navegue pelo dashboard
3. **Configuração:** Ajuste usuários e seções conforme necessário
4. **Desenvolvimento:** Implemente funcionalidades adicionais conforme demanda

---

**Sistema ENEDES v1.0** - Instituto Federal de Brasília  
Desenvolvido para gestão completa de projetos e ações da ENEDES.

