<?php

declare(strict_types=1);

namespace BC\Model;

use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * Предупреждение или ошибка из лога (см. BC\Logger\AlertingLogger) — то, что стоит
 * посмотреть в админке. Повторы одной и той же записи (тот же уровень и текст)
 * не плодят строки, а увеличивают счётчик: зациклившаяся ошибка не забьёт таблицу.
 *
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getLevel()
 * @method self setLevel(string $level)
 * @method string getMessage()
 * @method self setMessage(string $message)
 * @method string getContext()
 * @method self setContext(string $context)
 * @method string getTrace()
 * @method self setTrace(string $trace)
 * @method string getUrl()
 * @method self setUrl(string $url)
 * @method string getIp()
 * @method self setIp(string $ip)
 * @method string getRequestId()
 * @method self setRequestId(string $requestId)
 * @method string getFingerprint()
 * @method self setFingerprint(string $fingerprint)
 * @method int getOccurrences()
 * @method self setOccurrences(int $occurrences)
 * @method DateTime getFirstSeenAt()
 * @method self setFirstSeenAt(DateTime $firstSeenAt)
 * @method DateTime getLastSeenAt()
 * @method self setLastSeenAt(DateTime $lastSeenAt)
 */
#[DS\Table('log_records')]
class LogRecord extends AEntity {
    public const string LEVEL_WARNING = 'WARNING';
    public const string LEVEL_ERROR = 'ERROR';
    public const string LEVEL_CRITICAL = 'CRITICAL';
    public const string LEVEL_EMERGENCY = 'EMERGENCY';

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $level = '';

    #[DS\Column]
    protected string $message = '';

    /** JSON контекста записи (уже замаскированного, см. MaskingLogger) */
    #[DS\Column]
    protected string $context = '';

    #[DS\Column]
    protected string $trace = '';

    /** URL запроса или «CLI: команда» */
    #[DS\Column]
    protected string $url = '';

    #[DS\Column]
    protected string $ip = '';

    #[DS\Column]
    protected string $requestId = '';

    /** sha1 уровня и текста — по нему находятся повторы */
    #[DS\Column]
    protected string $fingerprint = '';

    #[DS\Column]
    protected int $occurrences = 1;

    #[DS\Column]
    protected DateTime $firstSeenAt;

    #[DS\Column]
    protected DateTime $lastSeenAt;

    public function isError(): bool {
        return $this->level !== self::LEVEL_WARNING;
    }
}
