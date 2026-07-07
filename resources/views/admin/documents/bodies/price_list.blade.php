{{-- Dynamic Customer Price List — body only; the shell owns branding/footers. --}}
<style>
    .pl-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .pl-table caption {
        text-align: left; font-size: 13px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 0.4px; padding: 6px 8px; background: #111827; color: #fff;
    }
    .pl-table th {
        text-align: right; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px;
        color: #374151; background: #e5e7eb; padding: 4px 8px; border-bottom: 1px solid #9ca3af;
    }
    .pl-table th:first-child { text-align: left; width: 34%; }
    .pl-table td { padding: 4px 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-variant-numeric: tabular-nums; }
    .pl-table td:first-child { text-align: left; font-weight: 600; }
    .pl-table tbody tr:nth-child(even) td { background: #f9fafb; }
</style>

@forelse ($groups as $group)
    <table class="pl-table">
        <caption>{{ $group['category']->title }}</caption>
        <thead>
            <tr>
                <th>Product</th>
                <th>Daily</th>
                <th>Weekend</th>
                <th>Weekly</th>
                <th>Monthly</th>
                <th>Std Del</th>
                <th>Ext Del</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($group['products'] as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    @foreach ([
                        $product->rental_daily,
                        $product->rental_weekend,
                        $product->rental_weekly,
                        $product->rental_monthly,
                        $product->standard_delivery_fee,
                        $product->extended_delivery_fee,
                    ] as $price)
                        <td>{{ $price !== null ? '$' . number_format((float) $price, 2) : '—' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@empty
    <p style="color: #6b7280; font-style: italic;">No published products found in the selected categories.</p>
@endforelse
