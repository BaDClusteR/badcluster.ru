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
