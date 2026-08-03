<?php

namespace App\Services\Goodwill;

use App\Enums\Discounts\DiscountType;
use App\Services\Discounts\Contracts\PrivilegedDiscountAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Proof that a Goodwill operation was authorized, as a value that cannot be
 * fabricated.
 *
 * WHY A TOKEN AND NOT A BOOLEAN. `DiscountApplicationService::applyGoodwill()`
 * has to bypass the generic Phase-1 type gate, so something must guarantee it
 * is never reached without an authority check. A convention ("only call this
 * from the Goodwill service") is not a guarantee — the next caller has no way
 * to know the rule exists.
 *
 * The constructor is private and the only way to obtain an instance is
 * {@see self::grant()}, which performs the direct Spatie check itself and
 * throws otherwise. A method that requires this type as a parameter therefore
 * cannot be called by an unauthorized path, and that is enforced by the type
 * system rather than by review.
 *
 * The approver and the operator are recorded separately. Authority to receive a
 * payment must never imply authority to reduce what is owed, so the manager
 * named on the record is verified to hold the permission in their own right —
 * an operator naming a manager is not evidence that the manager has authority.
 */
final class GoodwillAuthorization implements PrivilegedDiscountAuthorization
{
    private function __construct(
        public readonly int $approvedBy,
        public readonly ?int $appliedBy,
    ) {
    }

    public function discountType(): DiscountType
    {
        return DiscountType::Goodwill;
    }

    /** The discount is attributed to the operator who executed it, not the approver. */
    public function actingUserId(): ?int
    {
        return $this->appliedBy;
    }

    /**
     * Verify authority and mint the token.
     *
     * @param  Authenticatable|null  $operator  the signed-in user performing the action
     * @param  Authenticatable|null  $approver  the manager authorizing it; defaults to the operator
     *
     * @throws GoodwillException when either party lacks authority
     */
    public static function grant(?Authenticatable $operator, ?Authenticatable $approver = null): self
    {
        if (! GoodwillPermissions::canApply($operator)) {
            throw GoodwillException::because(GoodwillFailure::Unauthorized);
        }

        $approver ??= $operator;

        if (! GoodwillPermissions::isValidApprover($approver)) {
            throw GoodwillException::because(GoodwillFailure::ApproverUnauthorized);
        }

        return new self(
            approvedBy: (int) $approver->getAuthIdentifier(),
            appliedBy: $operator === null ? null : (int) $operator->getAuthIdentifier(),
        );
    }

    /** The same proof, for a reversal — a separately granted permission. */
    public static function grantReversal(?Authenticatable $operator): self
    {
        if (! GoodwillPermissions::canReverse($operator)) {
            throw GoodwillException::because(GoodwillFailure::Unauthorized);
        }

        $id = (int) $operator->getAuthIdentifier();

        return new self(approvedBy: $id, appliedBy: $id);
    }
}
