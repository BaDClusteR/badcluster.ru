<?php

declare(strict_types=1);

namespace BC\Core\Attention;

use BC\Core\Attention\DTO\AttentionItemDTO;

/**
 * Источник элементов для дашборд-виджета «Требует внимания». Реализации
 * регистрируются в services.yaml с тегом dashboard.attention_provider —
 * эндпоинт соберёт их все, так что новые источники добавляются одним
 * классом без правок эндпоинта.
 */
interface IAttentionItemsProvider {
    /**
     * @return AttentionItemDTO[] пустой массив — внимания ничего не требует
     */
    public function getItems(): array;
}
