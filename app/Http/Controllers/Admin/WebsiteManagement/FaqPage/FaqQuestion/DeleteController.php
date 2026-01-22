<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

// Models
use App\Models\WebsiteManagement\FaqPage\FaqQuestions;


class DeleteController extends Controller
{

    public function __invoke(string $unique_id)
    {

        $FaqQuestion = FaqQuestions::where('unique_id', $unique_id)->firstOrFail();

        $FaqQuestion->delete();

        flash('FAQ deleted successfully.')->success();

        return redirect()->route('admin.website-management.faq-page.index');
    }
}
