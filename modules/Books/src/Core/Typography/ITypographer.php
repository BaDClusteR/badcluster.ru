<?php

declare(strict_types=1);

namespace BC\Modules\Books\Core\Typography;

interface ITypographer {
    /**
     * Расставляет неразрывные пробелы (U+00A0) в HTML абзаца при выводе:
     * перед тире внутри фразы, после тире в начале реплики и после однобуквенных
     * предлогов и союзов. Тэги и содержимое <kbd>/<code>/<pre> не трогает.
     */
    public function process(string $html): string;
}
