<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Notes;


use App\Http\Controllers\Controller;
use App\Models\Customers\Tag;
use App\Models\Customers\CustomerNote;



class DeleteController extends Controller
{
    public function __invoke(CustomerNote $note)
    {
      $note->delete();
    return response()->json(['success' => true]);
    }
}
