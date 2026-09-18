<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Provider;

use BC\Modules\Blog\Model\Note;
use Runway\Exception\Exception;
use Runway\Logger\ILogger;

readonly class NotesProvider implements INotesProvider {
    public function __construct(
        private ILogger $logger
    ) {
    }

    public function getNotes(bool $onlyPublished): iterable {
        try {
            $qb = Note::getQueryBuilder();

            if ($onlyPublished) {
                $qb->andWhere('published = :published')
                   ->setVariable('published', true);
            }

            return $qb->orderBy('publish_date', 'DESC')->iterateEntities();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('[%s] Cannot get notes: %s', __METHOD__, $e->getMessage()),
                [
                    'onlyPublished' => $onlyPublished,
                    'errCode'       => $e->getCode(),
                    'errMessage'    => $e->getMessage(),
                ]
            );

            return [];
        }
    }
}
