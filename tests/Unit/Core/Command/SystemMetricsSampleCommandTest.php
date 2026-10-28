<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Command;

use BC\Core\Command\SystemMetricsSampleCommand;
use BC\Core\SystemMetrics\DTO\SystemMetricsSampleDTO;
use BC\Core\SystemMetrics\ISystemMetricsHistory;
use BC\Tests\Fixture\SystemMetrics\CountersStub;
use BC\Tests\Support\BufferedOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Console\Input\Input;
use Runway\Console\Parameter\Enum\ParameterModeEnum;

#[CoversClass(SystemMetricsSampleCommand::class)]
final class SystemMetricsSampleCommandTest extends TestCase {
    public function testDefinition(): void {
        $command = new SystemMetricsSampleCommand(self::createHistory());

        self::assertSame('metrics:sample', $command->getName());
        self::assertSame('Sample system metrics into the dashboard ring buffer', $command->getDescription());

        $parameters = $command->getParameters();
        self::assertCount(2, $parameters);
        self::assertSame(['count', 'c', ParameterModeEnum::VALUE_REQUIRED, '1'], [
            $parameters[0]->getName(),
            $parameters[0]->getShortcut(),
            $parameters[0]->getMode(),
            $parameters[0]->getDefault(),
        ]);
        self::assertSame(['interval', 'i', ParameterModeEnum::VALUE_REQUIRED, '1'], [
            $parameters[1]->getName(),
            $parameters[1]->getShortcut(),
            $parameters[1]->getMode(),
            $parameters[1]->getDefault(),
        ]);
    }

    public function testOneSample(): void {
        $history = self::createHistory();
        $output = new BufferedOutput();

        self::assertSame(0, new SystemMetricsSampleCommand($history)->run(new Input(), $output));

        self::assertSame(1, $history->samples);
        self::assertSame("Took 1 sample(s), buffer now holds 1 point(s).\n", $output->stdout);
    }

    public function testCountIsAtLeastOne(): void {
        $history = self::createHistory();
        $output = new BufferedOutput();

        new SystemMetricsSampleCommand($history)->run(new Input(options: ['count' => '-3', 'interval' => '0']), $output);

        self::assertSame(1, $history->samples);
        self::assertSame("Took 1 sample(s), buffer now holds 1 point(s).\n", $output->stdout);
    }

    public function testSeveralSamplesAreAtLeastASecondApart(): void {
        $history = self::createHistory();
        $output = new BufferedOutput();
        $start = microtime(true);

        new SystemMetricsSampleCommand($history)->run(new Input(options: ['count' => '2', 'interval' => '0.01']), $output);

        self::assertSame(2, $history->samples);
        self::assertGreaterThanOrEqual(1.0, $history->times[1] - $history->times[0]);
        self::assertLessThan(3.0, microtime(true) - $start);
        self::assertSame("Took 2 sample(s), buffer now holds 2 point(s).\n", $output->stdout);
    }

    /**
     * @return ISystemMetricsHistory&object{samples: int, times: list<float>}
     */
    private static function createHistory(): ISystemMetricsHistory {
        return new class implements ISystemMetricsHistory {
            public int $samples = 0;

            /** @var list<float> */
            public array $times = [];

            public function sample(): SystemMetricsSampleDTO {
                $this->samples++;
                $this->times[] = microtime(true);

                return new SystemMetricsSampleDTO(
                    array_fill(0, $this->samples, [1, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0]),
                    CountersStub::counters()
                );
            }
        };
    }
}
