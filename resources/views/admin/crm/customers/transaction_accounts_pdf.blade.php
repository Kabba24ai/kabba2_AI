<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transaction PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #262626;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .logo {
            height: 50px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
        }

        .contact-info {
            margin-top: 5px;
            font-size: 11px;
        }

        .container {
            padding: 30px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid #ccc;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: #999;
        }
    </style>
</head>
<body>

    <div class="header">
        {{-- Optional Logo --}}
        <img src="{{ public_path('storage/admin/images/logo/rent-n-king-logo-outro.png') }}" class="logo" alt="Logo"> 
        <div class="company-name">Rent 'n King</div>
        <div class="contact-info">
            Phone: (615) 815-6734 &nbsp; | &nbsp;
            Email: support@rentnking.com &nbsp; | &nbsp;
            Address: 123 Main St, Dickson, TN
        </div>
    </div>

    <div class="container">
        <div class="title">Customer Transaction</div>


  @php
    // Amount without tax
    if($transaction->sales_tax > 0 && ($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse'))) {
        // Tax is already included in the amount
        $amountWithoutTax = ($transaction->amount ?? 0) / (1 + $transaction->sales_tax);
    } else {
        // No tax included, or tax added on top
        $amountWithoutTax = $transaction->amount ?? 0;
    }

    // Tax amount
    if($transaction->sales_tax > 0 && ($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse'))) {
        $taxAmount = ($transaction->amount ?? 0) - $amountWithoutTax;
    } elseif($transaction->sales_tax > 0) {
        $taxAmount = ($transaction->amount ?? 0) * $transaction->sales_tax;
    } else {
        $taxAmount = 0;
    }

    // Total with tax
    $totalWithTax = $transaction->amount;
    if ($transaction->sales_tax > 0 && !($transaction->type === 'payment' || ($transaction->type === 'charge' && $transaction->sales_tax_type === 'reverse'))) {
        $totalWithTax += ($transaction->amount * $transaction->sales_tax);
    }
@endphp


        <table>
            <tr>
                <th>Transaction ID</th>
                <td>{{ $transaction->unique_id }}</td>
            </tr>
            <tr>
                <th>Customer Name</th>
                <td>{{ $transaction->customer->full_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Type</th>
                <td>{{ ucfirst($transaction->type) }}</td>
            </tr>
            <tr>
                <th>Amount (without Tax)</th>
                <td>{{ \App\Helpers\CustomHelper::formatCurrency($amountWithoutTax) }}</td>
            </tr>
           <tr>
                <th>Sales Tax</th>
                <td>{{ \App\Helpers\CustomHelper::formatCurrency($taxAmount) }}</td>
            </tr>
            <tr>
                <th>Total (with Tax)</th>
                <td>{{ \App\Helpers\CustomHelper::formatCurrency($totalWithTax) }}</td>
            </tr>
            <tr>
                <th>Date</th>
                <td>{{ App\Helpers\CustomHelper::formatDate($transaction->date) ?? 'N/A' }}</td>
            </tr>

             <tr>
                <th>Running Balance</th>
                <td>
            
                     {{ \App\Helpers\CustomHelper::formatCurrency($transaction->balance) }} 
            
                 </td>
            </tr>

            <tr>
                <th>Notes</th>
                <td>{{ $transaction->notes ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        &copy; {{ now()->year }} Rent 'n King. All rights reserved.
    </div>

</body>
</html>
