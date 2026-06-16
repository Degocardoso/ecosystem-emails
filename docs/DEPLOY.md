# Deploy e Configuração de Ambiente

## Recomendação geral

Aponte o servidor web para a **raiz do projeto**. Assim as URLs ficam limpas e
os caminhos internos funcionam:

```
/                                  -> login / painel
/gerador-de-emails-master/         -> Gerador (perfil criador/admin)
/dynamics-email-report/public/     -> Relatórios (perfil leitor/admin)
```

Se preferir manter a pasta dentro de `htdocs` (ex.: XAMPP), ela responderá em
`http://localhost/ecosystem-emails/` — basta ajustar `APP_URL` no `.env`.

---

## Apache (XAMPP ou Linux)

As proteções já vêm em arquivos `.htaccess` (cabeçalhos de segurança, bloqueio
de listagem e de arquivos sensíveis como `.env`, `.sql`, `.md`). Requisitos:

- `AllowOverride All` no diretório do projeto (para os `.htaccess` valerem);
- módulos `mod_headers` e `mod_rewrite` habilitados.

Exemplo de *virtual host* apontando para a raiz:

```apache
<VirtualHost *:80>
    ServerName emails.fecap.local
    DocumentRoot "/var/www/ecosystem-emails"

    <Directory "/var/www/ecosystem-emails">
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
</VirtualHost>
```

---

## Nginx + PHP-FPM

O Nginx **não lê `.htaccess`** — replique as proteções no `server`:

```nginx
server {
    listen 80;
    server_name emails.fecap.local;
    root /var/www/ecosystem-emails;
    index index.php;

    # Cabeçalhos de segurança
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Bloqueia arquivos e diretórios sensíveis
    location ~ /\.(env|git) { deny all; }
    location ~* \.(sql|md|lock|dist|sh)$ { deny all; }
    location ~ ^/(auth|database|partials|scripts)/ { deny all; }
    location ~ ^/dynamics-email-report/(src|config|logs|storage|tests|vendor)/ { deny all; }

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

> Há também um `dynamics-email-report/nginx.conf.example` (do projeto original)
> caso você queira servir **apenas** o módulo de relatórios isoladamente.

---

## HTTPS (fortemente recomendado em produção)

Os cookies de sessão já são marcados como `Secure` automaticamente sob HTTPS.
Com HTTPS ativo, habilite o HSTS (linha comentada no `.htaccess` raiz / bloco
`add_header` do Nginx). Use Let's Encrypt (`certbot`) ou o certificado da
instituição.

---

## Permissões de arquivos

| Caminho | Permissão | Observação |
|---------|-----------|------------|
| Projeto em geral | `644` arquivos / `755` pastas | leitura para o servidor |
| `dynamics-email-report/logs/` | `775` | gravável pelo servidor |
| `dynamics-email-report/storage/` | `775` | sessões e cache em arquivo |
| `.env` | `640` (ou `600`) | **somente** dono/servidor leem |

```bash
chmod 640 .env
chown -R www-data:www-data dynamics-email-report/logs dynamics-email-report/storage
```

---

## Checklist de produção

- [ ] `APP_ENV=production` e `APP_DEBUG=false` no `.env`
- [ ] `.env` preenchido e com permissão restrita (`640`/`600`)
- [ ] Banco criado e usuário de banco **dedicado** (não usar `root`)
- [ ] Senha do `admin@fecap.br` **trocada**
- [ ] `composer install` executado em `dynamics-email-report/`
- [ ] HTTPS ativo (e HSTS habilitado)
- [ ] **URL do Power Automate regenerada** (a antiga foi exposta — ver SEGURANCA.md)
- [ ] Backup do banco configurado
- [ ] Acesso ao phpMyAdmin restrito (IP/rede interna)
