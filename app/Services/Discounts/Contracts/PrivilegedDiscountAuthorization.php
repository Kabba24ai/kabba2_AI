<?php

namespace App\Services\Discounts\Contracts;

use App\Enums\Discounts\DiscountType;

/**
 * Proof that a discount type not exposed on the generic public path was
 * authorized by the domain that owns it.
 *
 * `DiscountApplicationService::apply()` refuses every type that is not
 * operationally exposed, which is what stops an arbitrary caller from
 * introducing a Goodwill concession with no authority, no reason and no audit
 * record. A dedicated entry point has to bypass that gate, so something must
 * guarantee it cannot be reached without the owning domain's own check.
 *
 * Requiring this type as a parameter is that guarantee. Implementations are
 * expected to be unconstructable except through a verified authorization —
 * `App\Services\Goodwill\GoodwillAuthorization` has a private constructor and
 * performs a direct Spatie permission check in its factory.
 *
 * The interface is declared HERE, in the Discounts namespace, so the shared
 * engine depends on its own contract rather than on a policy layer above it.
 * Goodwill implements it; the engine never imports Goodwill.
 *
 * This is a structural safeguard, not a cryptographic one: nothing prevents a
 * future class from implementing the interface. It makes the requirement
 * explicit and impossible to satisfy by accident, which is the realistic threat
 * — a caller who does not know the rule exists.
 */
interface PrivilegedDiscountAuthorization
{
    /** Which discount type this authorization covers. Checked at the entry point. */
    public function discountType(): DiscountType;

    /** The user the resulting discount is attributed to, or null for a system action. */
    public function actingUserId(): ?int;
}
