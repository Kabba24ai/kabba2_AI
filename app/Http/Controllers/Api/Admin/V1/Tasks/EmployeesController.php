<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\User;

class EmployeesController extends BaseController
{
    public function __invoke()
    {
        $employees = User::active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        return response()->json([
            'success' => true,
            'data'    => $employees->map(fn($u) => [
                'id'   => $u->id,
                'name' => $u->full_name,
            ]),
        ]);
    }
}
