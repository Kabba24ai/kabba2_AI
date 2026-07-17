<?php

namespace App\Services\Crm;

use App\Models\Customers\Customer;
use App\Services\TwilioService;
use Illuminate\Support\Collection;

/**
 * Canonical CRM SMS audience resolution. The SAME code produces the
 * wizard's recipient-review counts and the frozen recipient package, so
 * the preview can never disagree with what actually gets queued.
 *
 * Spec shape:
 *   base    'all' | 'tags'   — all eligible CRM recipients, or tag matching
 *   mode    'any' | 'all'    — positive matching mode (base=tags only)
 *   include [tag ids]        — positive tags
 *   exclude [tag ids]        — subtracted after the positive rule
 *
 * customers.tags is a JSON array of tag ids stored historically as BOTH
 * ints and strings — all comparisons normalize to ints.
 */
class SmsAudienceResolver
{
    /**
     * @return array{recipients: Collection<int, array{customer: Customer, phone: string}>, stats: array<string, int>}
     */
    public function resolve(array $spec): array
    {
        $base    = $spec['base'] ?? 'tags';
        $mode    = ($spec['mode'] ?? 'any') === 'all' ? 'all' : 'any';
        $include = $this->normalizeIds($spec['include'] ?? []);
        $exclude = $this->normalizeIds($spec['exclude'] ?? []);

        $stats = [
            'positive_count'     => 0,
            'excluded_by_tags'   => 0,
            'missing_phone'      => 0,
            'invalid_phone'      => 0,
            'duplicates_removed' => 0,
            'final_count'        => 0,
        ];

        if ($base !== 'all' && empty($include)) {
            return ['recipients' => collect(), 'stats' => $stats];
        }

        $customers = Customer::query()
            ->where('status', 'Active')
            ->orderBy('id')
            ->get(['id', 'first_name', 'last_name', 'phone', 'tags']);

        $recipients = collect();
        $seenPhones = [];

        foreach ($customers as $customer) {
            $tags = $this->customerTagIds($customer);

            // Positive rule
            if ($base !== 'all') {
                $matched = $mode === 'all'
                    ? count(array_diff($include, $tags)) === 0
                    : count(array_intersect($include, $tags)) > 0;
                if (!$matched) {
                    continue;
                }
            }
            $stats['positive_count']++;

            // Exclusion tags subtract from the positive pool
            if (!empty($exclude) && count(array_intersect($exclude, $tags)) > 0) {
                $stats['excluded_by_tags']++;
                continue;
            }

            // Phone eligibility
            $rawPhone = trim((string) ($customer->phone ?? ''));
            if ($rawPhone === '') {
                $stats['missing_phone']++;
                continue;
            }

            $phone = TwilioService::normalizePhone($rawPhone);
            if (!TwilioService::isValidPhone($phone)) {
                $stats['invalid_phone']++;
                continue;
            }

            // Dedup by normalized number — first customer keeps the slot
            if (isset($seenPhones[$phone])) {
                $stats['duplicates_removed']++;
                continue;
            }
            $seenPhones[$phone] = true;

            $recipients->push(['customer' => $customer, 'phone' => $phone]);
        }

        $stats['final_count'] = $recipients->count();

        return ['recipients' => $recipients, 'stats' => $stats];
    }

    /** @return int[] */
    private function normalizeIds(mixed $ids): array
    {
        return array_values(array_unique(array_map('intval', array_filter((array) $ids, 'is_numeric'))));
    }

    /** @return int[] */
    private function customerTagIds(Customer $customer): array
    {
        $raw = $customer->getRawOriginal('tags') ?? $customer->tags;

        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }

        return array_map('intval', array_filter((array) $raw, 'is_numeric'));
    }
}
