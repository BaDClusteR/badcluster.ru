-- Schema for core (non-module) tables.
--
-- Module tables live in modules/<Module>/schema.sql; this file is the same idea
-- for the site core. Applied by hand — there are no migrations in this project.
--
-- Runway's ORM stores DateTime properties as unix timestamps and bool as int,
-- and derives snake_case column names from camelCase properties. Table names are
-- prefixed with DB_PREFIX (bc_ by default) — adjust if your prefix differs.

-- BC\Model\StaticPage — standalone content pages served from the site root
-- (/about, /history, …) by BC\Controller\StaticPage.
CREATE TABLE IF NOT EXISTS `bc_static_pages` (
    `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`            TEXT             NOT NULL,
    -- Optional stand-in for `title` in the SEO title, when the real one is too long.
    `short_title`      TEXT             NOT NULL,
    `content`          LONGTEXT         NOT NULL CHECK (json_valid(`content`)),
    `slug`             TEXT             NOT NULL,
    `published`        INT(1)           NOT NULL DEFAULT 0,
    -- 0 puts a robots noindex tag on the page and keeps it out of sitemap.xml.
    `indexable`        INT(1)           NOT NULL DEFAULT 1,
    -- Whether the content container gets the `text-block` class. Some pages
    -- bring their own layout and break under it.
    `text_block`       INT(1)           NOT NULL DEFAULT 1,
    `publish_date`     INT(11)                   DEFAULT NULL, -- unix timestamp, optional
    `created_date`     INT(11)          NOT NULL DEFAULT 0,    -- unix timestamp, set once on creation
    `meta_description` TEXT             NOT NULL,
    -- Optional "back" link above the content; rendered only when both are set.
    `back_link_text`   TEXT             NOT NULL,
    `back_link_url`    TEXT             NOT NULL,
    PRIMARY KEY (`id`),
    -- TEXT needs a prefix length; 128 chars is well past any sane slug.
    UNIQUE KEY `slug` (`slug`(128))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- BC\Model\LogRecord — журнал предупреждений и ошибок для админки (пишет
-- BC\Logger\AlertingLogger). Повторы одной записи увеличивают `occurrences`.
-- LogRecorder::ensureSchema() создаёт таблицу сам, применять руками не обязательно.
CREATE TABLE IF NOT EXISTS `bc_log_records` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level`         VARCHAR(16)  NOT NULL DEFAULT '', -- WARNING, ERROR, CRITICAL, EMERGENCY
    `message`       TEXT         NOT NULL,
    `context`       MEDIUMTEXT   NOT NULL,            -- JSON, уже замаскированный
    `trace`         TEXT         NOT NULL,
    `url`           TEXT         NOT NULL,            -- «GET /path» или «CLI: команда»
    `ip`            VARCHAR(64)  NOT NULL DEFAULT '',
    `request_id`    VARCHAR(64)  NOT NULL DEFAULT '',
    `fingerprint`   CHAR(40)     NOT NULL DEFAULT '', -- sha1 уровня и текста
    `occurrences`   INT UNSIGNED NOT NULL DEFAULT 1,
    `first_seen_at` INT UNSIGNED NOT NULL DEFAULT 0,  -- unix timestamp
    `last_seen_at`  INT UNSIGNED NOT NULL DEFAULT 0,  -- unix timestamp
    PRIMARY KEY (`id`),
    KEY `idx_fingerprint` (`fingerprint`),
    KEY `idx_last_seen_at` (`last_seen_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Авторизация API platform 2 (см. README пакета bad_cluster/runway-api-platform).
-- options и tokens остались от API platform 1: их колонки те же (даты — unix
-- timestamp), но в tokens.token теперь лежит sha256 токена (64 символа), так что
-- старые сессии недействительны. Логин и хеш пароля (Argon2id) — в options,
-- задаются командой `php console auth:set-credentials`; AUTH_SALT, AUTH_HASH,
-- API_CRYPTO_KEY и API_CRYPTO_KEY_LENGTH из .env.local больше не нужны.
CREATE TABLE IF NOT EXISTS `bc_options` (
    `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`  VARCHAR(255) NOT NULL,
    `value` TEXT         NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `bc_tokens` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token`       VARCHAR(64)  NOT NULL,           -- sha256 токена сессии
    `generated`   INT UNSIGNED NOT NULL DEFAULT 0, -- unix timestamp
    `valid_until` INT UNSIGNED NOT NULL DEFAULT 0, -- unix timestamp
    `last_active` INT UNSIGNED NOT NULL DEFAULT 0, -- unix timestamp
    `ip`          VARCHAR(64)  NOT NULL DEFAULT '',
    `user_agent`  TEXT         NOT NULL,
    `server_info` TEXT         NOT NULL,
    `expired`     INT(1)       NOT NULL DEFAULT 0,
    `active`      INT(1)       NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_token` (`token`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Неудачные логины: 5 с одного IP и 50 всего за 15 минут, дальше 429.
-- Записи старше суток удаляются при следующей неудачной попытке.
CREATE TABLE IF NOT EXISTS `bc_login_attempts` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip`        VARCHAR(64)  NOT NULL,
    `attempted` INT UNSIGNED NOT NULL, -- unix timestamp
    PRIMARY KEY (`id`),
    KEY `idx_attempted` (`attempted`),
    KEY `idx_ip_attempted` (`ip`, `attempted`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
