<?php

namespace App\Http\Controllers\Admin\Documents\Text;

use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;

class ShowController extends Controller
{
    /** Document Text tab of the Price List module. */
    public function __invoke()
    {
        $text = ConfigurationHelper::getSettings('Price List Settings');

        return view('admin.documents.document_text', [
            'title'        => $text['price_list_title'] ?? '',
            'valueMessage' => $text['price_list_value_message'] ?? '',
            'disclaimer'   => $text['price_list_disclaimer'] ?? '',
        ]);
    }
}
