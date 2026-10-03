-- Schema changes for the Books module's tables.
--
-- The runway ORM derives snake_case column names from camelCase properties —
-- the columns below match what the models in BC\Modules\Books\Model write.
--
-- The table name is prefixed with DB_PREFIX (bc_) — adjust if your prefix differs.

-- BC\Modules\Books\Model\Book — <id> and <version> of the FB2 document-info.
-- fb2_id is a UUID generated once (on book creation or the first FB2 build) and
-- never changed afterwards. The books table itself predates this file, so the
-- columns come as an ALTER; run it by hand on every installation.
ALTER TABLE `bc_books`
    ADD COLUMN `fb2_id`      VARCHAR(36) NOT NULL DEFAULT ''    AFTER `fb2_genre`,
    ADD COLUMN `fb2_version` VARCHAR(16) NOT NULL DEFAULT '1.0' AFTER `fb2_id`;
