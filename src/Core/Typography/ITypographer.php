<?php

declare(strict_types=1);

namespace BC\Core\Typography;

interface ITypographer {
    /**
     * Расставляет неразрывные пробелы (U+00A0) в HTML блока при выводе:
     * перед тире внутри фразы, после тире в начале реплики, после однобуквенных
     * предлогов и союзов, а в английском тексте — после артиклей и коротких предлогов.
     * Тэги и содержимое <kbd>/<code>/<pre> не трогает.
     */
    public function process(string $html): string;
}
