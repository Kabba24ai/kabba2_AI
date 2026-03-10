<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Invoice;

class MarkMailController extends Controller
{
  public function __invoke($unique_id)
{
    
    $invoice = Invoice::where('unique_id', $unique_id)->firstOrFail();

    $invoice->update([
        'is_mail' => 'yes',
        'is_mail_date' => now(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Email status updated'
    ]);
}
}