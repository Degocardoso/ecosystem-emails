# Banco de Dados — Usuários e Permissões

O controle de acesso usa **MySQL** (gerenciável pelo phpMyAdmin). São cinco
tabelas, criadas pelo arquivo [`database/schema.sql`](../database/schema.sql) e
populadas por [`database/seed.sql`](../database/seed.sql).

## Modelo (RBAC — Role Based Access Control)

```
users (usuários)                 roles (perfis)            permissions (permissões)
┌────────────────┐ N        1 ┌──────────────┐ 1     N ┌──────────────────────┐
│ id             ├────────────┤ id           ├──┐      │ id                    │
│ nome           │   role_id  │ slug         │  │      │ slug                  │
│ email (único)  │            │ nome         │  │      │ descricao             │
│ senha_hash     │            │ descricao    │  │      └──────────┬───────────┘
│ role_id (FK)   │            └──────────────┘  │                 │
│ ativo          │                              │   role_permissions (N:N)
│ ultimo_login   │                              └──►┌────────────────────────┐
│ created_at     │                                  │ role_id (FK)           │
│ updated_at     │                                  │ permission_id (FK)     │
└────────────────┘                                  └────────────────────────┘

login_attempts (auditoria + anti força bruta): id, email, ip, sucesso, user_agent, created_at
```

## Tabelas

### `roles` — perfis de acesso
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | TINYINT UNSIGNED PK | — |
| `slug` | VARCHAR(20) único | `admin`, `criador`, `leitor` |
| `nome` | VARCHAR(50) | Nome exibido |
| `descricao` | VARCHAR(255) | — |

### `permissions` — permissões granulares
| `slug` | Significado |
|--------|-------------|
| `gerador.acesso` | Usar o gerador de e-mails |
| `relatorios.acesso` | Acessar os relatórios do Dynamics |
| `usuarios.gerenciar` | Criar/editar/remover usuários |

### `role_permissions` — quais permissões cada perfil tem
| Perfil | Permissões |
|--------|------------|
| **admin** | `gerador.acesso`, `relatorios.acesso`, `usuarios.gerenciar` |
| **criador** | `gerador.acesso` |
| **leitor** | `relatorios.acesso` |

> Além disso, o código trata `admin` como "curinga": um administrador tem acesso
> a tudo mesmo que uma permissão nova seja adicionada no futuro (ver `Auth::can()`).

### `users` — usuários
| Coluna | Observações de segurança |
|--------|--------------------------|
| `email` | Único; usado como login |
| `senha_hash` | **bcrypt (cost 12)** via `password_hash()` — nunca a senha em texto puro |
| `ativo` | `0` bloqueia o login sem apagar o usuário |
| `ultimo_login` | Atualizado a cada login bem-sucedido |

### `login_attempts` — auditoria de login
Registra cada tentativa (com IP e user-agent). É a base da proteção contra
força bruta: após `LOGIN_MAX_ATTEMPTS` falhas dentro de
`LOGIN_LOCKOUT_MINUTES`, o login é bloqueado temporariamente para aquele
e-mail/IP.

## Como criar o banco

### Pelo phpMyAdmin
1. Abra o phpMyAdmin.
2. Aba **Importar** → selecione `database/schema.sql` → **Executar**.
   (Ele cria o banco `ecosystem_emails` e as tabelas.)
3. Aba **Importar** → selecione `database/seed.sql` → **Executar**.
   (Cria perfis, permissões e o administrador inicial.)

### Pela linha de comando
```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

## Usuário de banco dedicado (recomendado)

Não use o `root` na aplicação. Crie um usuário com privilégios só no banco do
sistema:

```sql
CREATE USER 'ecosystem_app'@'localhost' IDENTIFIED BY 'uma-senha-bem-forte';
GRANT SELECT, INSERT, UPDATE, DELETE ON ecosystem_emails.* TO 'ecosystem_app'@'localhost';
FLUSH PRIVILEGES;
```

Depois preencha no `.env`:
```
DB_USER=ecosystem_app
DB_PASS=uma-senha-bem-forte
```

## Login inicial

| E-mail | Senha temporária | Perfil |
|--------|------------------|--------|
| `admin@fecap.br` | `Admin@Fecap2026` | Administrador |

**Troque a senha no primeiro acesso** (menu *Gerenciar Usuários → Editar*).
Para repor o hash manualmente, gere um novo com:

```bash
php scripts/gerar-hash.php "NovaSenhaForte123"
# copie o resultado para a coluna senha_hash em users (via phpMyAdmin)
```
