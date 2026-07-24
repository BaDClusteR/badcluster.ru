<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Config;

/**
 * Which database tables end up in the dump.
 *
 * Tables are named by their physical name WITHOUT the DB_PREFIX (i.e. the value of the
 * model's #[DS\Table(...)] attribute — e.g. 'geoip', not 'bc_geoip'). The prefix is
 * prepended by the dumper.
 */
readonly class DatabaseConfig {
    /**
     * @param bool     $greedy        true: dump the whole database EXCEPT $excludeTables.
     *                                false: dump ONLY $includeTables.
     * @param string[] $includeTables physical (unprefixed) table names, used when $greedy is false
     * @param string[] $excludeTables physical (unprefixed) table names, used when $greedy is true
     */
    public function __construct(
        public bool $greedy = true,
        public array $includeTables = [],
        public array $excludeTables = ['geoip'],
    ) {}
}