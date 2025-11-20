<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\FaqPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\FaqPage\UpdateRequest;
use App\Models\WebsiteManagement\FaqPage\FaqCategory;
use App\Helpers\MediaHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $faqCategory = FaqCategory::where('unique_id', $unique_id)->firstOrFail();

            // Update text fields
            $faqCategory->category_name = $validated['category_name'];
            $faqCategory->description = $validated['description'] ?? null;
            $faqCategory->default_expand = $request->boolean('default_expand', false);
            $faqCategory->save();

            // Handle icon upload
            if ($request->hasFile('icon')) {
                $mediaData = MediaHelper::uploadStorageFile(
                    'Public Asset',
                    $validated['icon'],
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
            flash('FAQ Category updated successfully.')->success();
            //  If it's an AJAX request, return JSON

            return response()->json([
                    'success' => true,
                    'message' => 'FAQ Category updated successfully.',
                    'category' => [
                        'id' => $faqCategory->id,
                        'unique_id' => $faqCategory->unique_id,
                        'category_name' => $faqCategory->category_name,
                        'description' => $faqCategory->description,
                        'default_expand' => $faqCategory->default_expand,
                        'icon_url' => $faqCategory->iconMedia?->getUrl(),
                    ]
                ]);
            

            // Otherwise normal redirect (if form submitted traditionally)
           
            // flash('FAQ Category updated successfully.')->success();
            // return redirect()->route('admin.website-management.faq-page.index');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('FAQ Category Update Error:', ['error' => $e->getMessage()]);
            report($e);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while updating the FAQ category.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            flash('Something went wrong while updating the FAQ category.')->error();
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An unexpected error occurred.']);
        }
    }
}
