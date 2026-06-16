-- ===========================================================================
--  Ecossistema de E-mails FECAP — Estrutura do banco de dados (MySQL 5.7+/8.0)
--  -------------------------------------------------------------------------
--  Importe este arquivo no phpMyAdmin (aba "Importar") OU via linha de comando:
--      mysql -u root -p < database/schema.sql
--  Em seguida importe database/seed.sql para criar perfis, permissões e o
--  usuário administrador inicial.
-- ===========================================================================

CREATE DATABASE IF NOT EXISTS `ecosystem_emails`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ecosystem_emails`;

-- ---------------------------------------------------------------------------
-- Perfis (papéis) de acesso
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id`         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(20)  NOT NULL,
  `nome`       VARCHAR(50)  NOT NULL,
  `descricao`  VARCHAR(255) NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Permissões granulares
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`      VARCHAR(50)  NOT NULL,
  `descricao` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Relação N:N entre perfis e permissões
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id`       TINYINT UNSIGNED  NOT NULL,
  `permission_id` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Usuários
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nome`         VARCHAR(120)     NOT NULL,
  `email`        VARCHAR(190)     NOT NULL,
  `senha_hash`   VARCHAR(255)     NOT NULL,            -- bcrypt (nunca senha em texto puro)
  `role_id`      TINYINT UNSIGNED NOT NULL,
  `ativo`        TINYINT(1)       NOT NULL DEFAULT 1,
  `ultimo_login` DATETIME         NULL,
  `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Tentativas de login (auditoria + proteção contra força bruta)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(190)    NULL,
  `ip`         VARCHAR(45)     NOT NULL,
  `sucesso`    TINYINT(1)      NOT NULL DEFAULT 0,
  `user_agent` VARCHAR(255)    NULL,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_ip_time`    (`ip`, `created_at`),
  KEY `idx_attempts_email_time` (`email`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
