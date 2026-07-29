<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use BC\Api\DTO\Attention\AttentionDTO;
use BC\Api\DTO\Attention\AttentionItemDTO;
use BC\Core\Attention\Enum\AttentionSeverityEnum;
use BC\Core\Attention\IAttentionItemsProvider;
use Runway\Logger\ILogger;
use Runway\Singleton\Container;
use Throwable;

#[Docs\Group('Attention')]
class Attention extends AEndpoint {
    private const string PROVIDER_TAG = 'dashboard.attention_provider';

    private const array SEVERITY_ORDER = [
        AttentionSeverityEnum::Error->value => 0,
        AttentionSeverityEnum::Warning->value => 1,
        AttentionSeverityEnum::Info->value => 2,
    ];

    public function __construct(
        private readonly ILogger $logger
    ) {
    }

    #[API\Endpoint(path: 'attention', method: 'GET')]
    public function get(): AttentionDTO {
        $items = [];

        foreach (Container::getInstance()->getServicesByTag(self::PROVIDER_TAG) as $provider) {
            if (!$provider instanceof IAttentionItemsProvider) {
                continue;
            }

            // Сломавшийся источник не должен ронять весь виджет
            try {
                foreach ($provider->getItems() as $item) {
                    $items[] = new AttentionItemDTO(
                        message: $item->message,
                        severity: $item->severity->value,
                        adminPath: $item->adminPath
                    );
                }
            } catch (Throwable $e) {
                $this->logger->warning(
                    sprintf('[%s] Attention provider %s failed: %s', __METHOD__, $provider::class, $e->getMessage())
                );
            }
        }

        usort(
            $items,
            static fn (AttentionItemDTO $a, AttentionItemDTO $b): int =>
                (self::SEVERITY_ORDER[$a->severity] ?? 9) <=> (self::SEVERITY_ORDER[$b->severity] ?? 9)
        );

        return new AttentionDTO($items);
    }
}
