-- Schema for the Blog module's tables.
--
-- The runway ORM stores DateTime and bool properties as integers, and derives
-- snake_case column names from camelCase properties — the columns below match
-- what the models in BC\Modules\Blog\Model write.
--
-- The table name is prefixed with DB_PREFIX (bc_) — adjust if your prefix differs.

CREATE TABLE IF NOT EXISTS `bc_notes` (
    `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`            TEXT             NOT NULL,
    `content`          LONGTEXT         NOT NULL CHECK (json_valid(`content`)),
    `slug`             TEXT             NOT NULL,
    `published`        INT(1)           NOT NULL DEFAULT 0,
    `publish_date`     INT(11)          NOT NULL DEFAULT 0,
    `created_date`     INT(11)          NOT NULL DEFAULT 0,
    `meta_description` TEXT             NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`(128)),
    KEY `publish_date` (`publish_date`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- BC\Modules\Blog\Model\Post — optional subtitle, rendered as an H2 right under
-- the post's H1. The posts table itself predates this file, so the column comes
-- as an ALTER; run it by hand on every installation.
ALTER TABLE `bc_posts`
    ADD COLUMN `subtitle` TEXT NOT NULL AFTER `short_title`;
