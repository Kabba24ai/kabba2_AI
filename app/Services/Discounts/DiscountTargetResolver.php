<?php

namespace App\Services\Discounts;

use App\Enums\Discounts\DiscountTargetType;
use App\Services\Discounts\Contracts\DiscountTarget;
use App\Services\Discounts\Targets\OrderDiscountTarget;

/**
 * Maps a constrained (target_type, target_id) to its DiscountTarget adapter.
 * Only registered surfaces are resolvable — an unknown/unimplemented type is a
 * hard rejection (no arbitrary morph). Fuel / Damage / Extension adapters are
 * registered as they land in Increment 2.
 */
class DiscountTargetResolver
{
    public function resolve(DiscountTargetType $type, int $id): DiscountTarget
    {
        return match ($type) {
            DiscountTargetType::Order => new OrderDiscountTarget($id),
            default => throw new DiscountException("Discount target '{$type->value}' is not yet supported."),
        };
    }
}
