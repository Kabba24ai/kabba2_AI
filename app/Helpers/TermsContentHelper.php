<?php

namespace App\Helpers;

use App\Models\TermsAndConditions\Terms;
use Illuminate\Support\Collection;

class TermsContentHelper
{
    /**
     * Generate merged terms content and array from live order relationships
     */
    public static function generateTermsContent($order): array
    {
        // Merge array and content
        $mergedTerms = self::generateTermsArrayFromOrder($order);

        // Now generate the content using the merged terms
        return self::generateTermsContentFromArray($mergedTerms, $order->customer_name ?? 'Customer');
    }

    /**
     * Generate merged terms array from order (fresh from DB)
     */
    public static function generateTermsArrayFromOrder($order): Collection
    {
        $globalTerms = Terms::query()->global()->first();

        $productTerms = $order->products->pluck('product.terms')->flatten()->unique('id')->values()->map(
            fn($term) => [
                'id' => $term->id,
                'unique_id' => $term->unique_id,
                'title' => $term->title,
                'content' => $term->content,
                'signature_block' => $term->signature_block,
                'is_global' => false,
            ],
        );

        return collect()
            ->when(
                $globalTerms,
                fn($c) => $c->push([
                    'id' => $globalTerms->id,
                    'unique_id' => $globalTerms->unique_id,
                    'title' => $globalTerms->title,
                    'content' => $globalTerms->content,
                    'signature_block' => $globalTerms->signature_block,
                    'is_global' => true,
                ]),
            )
            ->merge($productTerms);
    }

    /**
     * Generate terms content HTML from a previously stored merged array
     */
    public static function generateTermsContentFromArray(Collection|array $mergedTerms, string $customerName): array
    {
        $mergedTerms = collect($mergedTerms);

        $globalTerms = $mergedTerms->firstWhere('is_global', true);
        $productTerms = $mergedTerms->where('is_global', false);

        // Replace customer initials placeholder in product terms
        $approvalCheckbox = <<<HTML
        <div>
            <label for="customer_initials" class="flex items-center space-x-2">
                <input
                    name="customer_initials[]"
                    id="customer_initials"
                    type="checkbox"
                    class="customer_initials_checkbox h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                    required
                >
                <span class="font-semibold text-red-600">Customer Approval Required</span>
            </label>
        </div>
        HTML;

        $productTermsContent = $productTerms->map(fn($term) => str_replace('[customer_initials][/customer_initials]', $approvalCheckbox, $term['content']))->implode("\n");

        // Signature button HTML
        $signatureButton = <<<HTML
        <div class="flex items-center gap-x-1">
            <div class="flex flex-col items-start gap-y-2 w-full">
                <div id="signature-preview-div" class="h-32 w-50 hidden">
                    <div class="flex items-center gap-x-2">
                        <img id="signature-preview" src="" alt="Signature Preview" class="h-32 w-auto rounded border border-gray-300">
                        <button type="button" id="close-signature-btn"
                            class="text-gray-500 hover:text-gray-700 px-2 py-1 rounded text-2xl flex items-center justify-center"
                            aria-label="Close signature preview" onclick="closeSignaturePreview()">
                            &times;
                        </button>
                    </div>
                    <input type="hidden" name="signature" id="signature_input" value="">
                </div>
                <button type="button" id="open-signature-btn"
                    class="border-0 bg-yellow-400 px-6 py-4 font-medium rounded-lg hover:bg-yellow-300 transition-all duration-500 ease-in-out"
                    aria-label="Open signature modal" onclick="openModal()">
                    CLICK HERE TO SIGN
                </button>
            </div>
        </div>
        HTML;

        // Build terms content
        $termsContent = $globalTerms ? str_replace('[product_terms][/product_terms]', $productTermsContent, $globalTerms['content']) : $productTermsContent;

        if ($globalTerms) {
            $signatureBlock = str_replace(['[customer_name]', '[customer_signature]'], [$customerName, $signatureButton], $globalTerms['signature_block']);

            $termsContent .= $signatureBlock;
        }

        return [
            'terms_content' => $termsContent,
            'merged_terms' => $mergedTerms,
            'signature_button' => $signatureButton,
        ];
    }


    /**
     * Generate terms content HTML from a previously stored merged array
     */
    public static function generateAcceptedTermsContentFromArray(Collection|array $mergedTerms, string $customerName, string $signature): array
    {
        $mergedTerms = collect($mergedTerms);

        $globalTerms = $mergedTerms->firstWhere('is_global', true);
        $productTerms = $mergedTerms->where('is_global', false);

        // Replace customer initials placeholder in product terms
        $approvalCheckbox = <<<HTML
        <div>
            <label for="customer_initials" class="flex items-center space-x-2">
                <input
                    name="customer_initials[]"
                    id="customer_initials"
                    type="checkbox"
                    class="customer_initials_checkbox h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                    disabled
                    checked
                    required
                >
                <span class="font-semibold text-black">Customer Approved</span>
            </label>
        </div>
        HTML;

        $productTermsContent = $productTerms->map(fn($term) => str_replace('[customer_initials][/customer_initials]', $approvalCheckbox, $term['content']))->implode("\n");

        // Signature button HTML
        $signatureButton = <<<HTML
        <div class="flex items-center gap-x-1">
            <div class="flex flex-col items-start gap-y-2 w-full">
                <div id="signature-preview-div" class="h-32 w-50">
                    <div class="flex items-center gap-x-2">
                        <img id="signature-preview" src="{$signature}" alt="Signature Preview" class="h-32 w-auto rounded border border-gray-300">
                        <button type="button" id="close-signature-btn"
                            class="text-gray-500 hover:text-gray-700 px-2 py-1 rounded text-2xl flex items-center justify-center hidden"
                            aria-label="Close signature preview" onclick="closeSignaturePreview()">
                            &times;
                        </button>
                    </div>
                    <input type="hidden" name="signature" id="signature_input" value="{$signature}">
                </div>
                <button type="button" id="open-signature-btn"
                    class="border-0 bg-yellow-400 px-6 py-4 font-medium rounded-lg hover:bg-yellow-300 transition-all duration-500 ease-in-out hidden"
                    aria-label="Open signature modal" onclick="openModal()">
                    CLICK HERE TO SIGN
                </button>
            </div>
        </div>
        HTML;

        // Build terms content
        $termsContent = $globalTerms ? str_replace('[product_terms][/product_terms]', $productTermsContent, $globalTerms['content']) : $productTermsContent;

        if ($globalTerms) {
            $signatureBlock = str_replace(['[customer_name]', '[customer_signature]'], [$customerName, $signatureButton], $globalTerms['signature_block']);

            $termsContent .= $signatureBlock;
        }

        return [
            'accepted_terms_content' => $termsContent,
            'signature_button' => $signatureButton,
        ];
    }
}
