<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Model;

use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * One record per backup attempt (successful or not).
 *
 * Note: the runway ORM stores DateTime and bool as integers, so `created_at` is a unix
 * timestamp column and `success` is 0/1 (see schema.sql).
 *
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method DateTime getCreatedAt()
 * @method self setCreatedAt(DateTime $createdAt)
 * @method bool getSuccess()
 * @method self setSuccess(bool $success)
 * @method int getSizeBytes()
 * @method self setSizeBytes(int $sizeBytes)
 * @method string getArchiveName()
 * @method self setArchiveName(string $archiveName)
 * @method string getUrl()
 * @method self setUrl(string $url)
 * @method string getDestinations()
 * @method self setDestinations(string $destinations)
 * @method string getError()
 * @method self setError(string $error)
 * @method int getDurationSeconds()
 * @method self setDurationSeconds(int $durationSeconds)
 */
#[DS\Table('backup_log')]
class BackupLog extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected DateTime $createdAt;

    #[DS\Column]
    protected bool $success = false;

    #[DS\Column]
    protected int $sizeBytes = 0;

    #[DS\Column]
    protected string $archiveName = '';

    #[DS\Column]
    protected string $url = '';

    /** Comma-separated names of destinations the archive was successfully uploaded to. */
    #[DS\Column]
    protected string $destinations = '';

    /** Failure reason / accumulated non-fatal errors; empty on a clean success. */
    #[DS\Column]
    protected string $error = '';

    #[DS\Column]
    protected int $durationSeconds = 0;
}
