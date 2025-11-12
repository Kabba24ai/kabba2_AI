<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\FaqPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\FaqPage\StoreRequest;
use App\Models\WebsiteManagement\FaqPage\FaqCategory;
use App\Helpers\MediaHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {

            

            $faqCategory = new FaqCategory([
                'category_name' => $validated['categoryName'],
                'description' => $validated['description'] ?? null,
                'default_expand' => $validated['default_expand'] ?? false,
            ]);

            $faqCategory->save();

            //  Handle category icon upload
            if ($request->hasFile('category_icon_media')) {
                $mediaData = MediaHelper::uploadStorageFile(
                    'Public Asset',
                    $validated['category_icon_media'],
                    'faq-categories',
                    $faqCategory
                );

                if (!empty($mediaData['mediaObj'])) {
                    $faqCategory->update([
                        'category_icon_media_id' => $mediaData['mediaObj']->id,
                    ]);
                }
            }

            DB::commit();


            session()->flash('active_tab', 'categories');



            flash('FAQ Category created successfully.')->success();

            return redirect()->route('admin.website-management.faq-page.index');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('FAQ Category Store Error:', ['error' => $e->getMessage()]);
            report($e);

            flash('Something went wrong while creating the FAQ category.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An unexpected error occurred.']);
        }
    }
}
