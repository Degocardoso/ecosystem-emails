# Autenticação e Controle de Acesso

## Fluxo de login

1. O usuário acessa qualquer URL protegida. Se não estiver logado, o `guard.php`
   o redireciona para `login.php?next=<destino>`.
2. Em `login.php`, o formulário envia `email`, `senha` e o **token CSRF**.
3. `Auth::attempt()`:
   - verifica se o e-mail/IP não está **bloqueado por excesso de tentativas**;
   - busca o usuário e valida a senha com `password_verify()` (bcrypt);
   - sempre executa o `password_verify` (mesmo sem usuário) para evitar
     **enumeração de usuários por tempo de resposta**;
   - recusa usuários `ativo = 0`;
   - em caso de sucesso, regenera o ID de sessão (anti *session fixation*),
     carrega o perfil e as permissões na sessão e registra o `ultimo_login`.
4. O usuário é levado ao `dashboard.php` (ou ao `next`, validado para impedir
   *open redirect*).

## Verificações disponíveis (em `auth/guard.php`)

```php
require_once __DIR__ . '/../auth/guard.php';

require_login();                       // exige apenas estar autenticado
require_permission('gerador.acesso');  // exige uma permissão específica
require_role(['admin']);               // exige um perfil específico
```

- Em **páginas HTML**, a falta de login redireciona para o login; a falta de
  permissão mostra uma página **403**.
- Em **endpoints AJAX** (ex.: `enviarParaDynamics.php`, `buscar-emails-ajax.php`),
  não há redirecionamento: eles respondem **JSON** com status `401`/`403`.

## Como o perfil controla o que aparece

No painel (`dashboard.php`), cada card só é exibido se o usuário tiver a
permissão correspondente:

```php
if (Auth::can('gerador.acesso'))     { /* mostra card do Gerador */ }
if (Auth::can('relatorios.acesso'))  { /* mostra card dos Relatórios */ }
if (Auth::can('usuarios.gerenciar')) { /* mostra card de Usuários */ }
```

A verificação **não é apenas visual**: cada módulo repete a checagem no
servidor (`require_permission(...)`), de modo que digitar a URL direta também é
bloqueado.

| Perfil | Gerador | Relatórios | Usuários |
|--------|:------:|:----------:|:--------:|
| Administrador | ✅ | ✅ | ✅ |
| Criador | ✅ | ❌ | ❌ |
| Leitor | ❌ | ✅ | ❌ |

## Sessão

Configurada em `auth/bootstrap.php` **antes** de iniciar:

- `httponly` (JS não lê o cookie), `samesite=Lax` (mitiga CSRF), `secure`
  automático quando em HTTPS;
- `session.use_strict_mode` e `session.use_only_cookies` ativos;
- expiração por inatividade conforme `SESSION_LIFETIME` (minutos no `.env`);
- `session_regenerate_id(true)` no login e limpeza completa no logout.

## Proteção CSRF

`auth/Csrf.php` gera um token por sessão. Os formulários incluem
`<?= Csrf::field() ?>` e os endpoints AJAX enviam o cabeçalho `X-CSRF-Token`.
No servidor, `Csrf::requireValidToken()` (ou `Csrf::validate()`) compara com
`hash_equals()` (resistente a *timing attack*). Requisições POST sem token
válido recebem **403**.

## Gerenciamento de usuários (perfil admin)

Em *Gerenciar Usuários* o administrador pode criar, editar (inclusive trocar
a senha e o perfil) e remover usuários. Regras de proteção embutidas:

- senha mínima de 8 caracteres, com letras e números;
- e-mail único;
- não é possível **remover a si mesmo**;
- não é possível **remover ou rebaixar o último administrador ativo**.
