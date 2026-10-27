<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Games;

use BC\Model\Media;
use BC\Modules\Games\Model\Game;
use BC\Modules\Games\Model\GameMaterial;
use BC\Modules\Games\Model\GameMaterialFile;
use BC\Tests\Support\SqliteSchema;
use DateTime;
use DateTimeZone;

/**
 * The tables of the Games module and the games of the tests. No files: GameMaterialFile and Media rows are never
 * removed in the tests (removing deletes their files under static/).
 */
final class GamesFixture {
    public static function createTables(): void {
        SqliteSchema::createModelTables(Game::class, GameMaterial::class, GameMaterialFile::class, Media::class);
    }

    public static function createGame(string $slug, string $title = ''): Game {
        $game = new Game()->setTitle($title ?: "Game $slug")->setSlug($slug);
        $game->persist();

        return $game;
    }

    public static function createMaterial(
        Game $game,
        string $slug,
        string $type = GameMaterial::TYPE_FILE,
        string $url = ''
    ): GameMaterial {
        $material = new GameMaterial()
            ->setGame($game)
            ->setTitle("Material $slug")
            ->setShortTitle("Short $slug")
            ->setSlug($slug)
            ->setType($type)
            ->setUrl($url)
            ->setDateAdded(new DateTime('2025-01-02 03:04:05', new DateTimeZone('UTC')));
        $material->persist();

        return $material;
    }
}
