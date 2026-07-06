<?php

namespace App\Services\DocumentGenerator;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

/**
 * Base class for every generated document (price lists, and later invoices,
 * receipts, catalogs, quote packets, handouts…).
 *
 * Division of responsibility: business modules own the ACTION that triggers
 * a document; the Document Generator owns PRESENTATION — rendering, layout,
 * branding, headers, footers, disclaimers, print formatting, and versioning.
 * Documents render through the shared print shell
 * (admin/documents/render.blade.php) sized for 8.5×11 paper.
 */
abstract class AbstractDocument
{
    protected Carbon $generatedAt;

    public function __construct()
    {
        $this->generatedAt = now();
    }

    /** Registry key, e.g. 'customer-price-list'. */
    abstract public function key(): string;

    /** Document title shown in the header. */
    abstract public function title(): string;

    /** Blade view for the document BODY (rendered inside the print shell). */
    abstract public function bodyView(): string;

    /** Build the body view's data from the caller's options. */
    abstract public function build(array $options): array;

    /** Document template version — printed in the footer for traceability. */
    public function version(): string
    {
        return '1.0';
    }

    /** Company branding block used by the shared shell. */
    public function brand(): array
    {
        return [
            'name'    => "Rent 'n King",
            'website' => 'RentnKing.com',
        ];
    }

    /** Short notice repeated on every printed page. */
    public function pageNotice(): ?string
    {
        return null;
    }

    /** Long-form disclaimer for the final page (null = none). */
    public function disclaimer(): ?string
    {
        return null;
    }

    /** General information block for the final page (null = none). */
    public function generalInfo(): ?array
    {
        return null;
    }

    public function generatedAt(): Carbon
    {
        return $this->generatedAt;
    }

    /** Render the complete printable document through the shared shell. */
    public function render(array $options = []): View
    {
        return view('admin.documents.render', array_merge($this->build($options), [
            'document' => $this,
        ]));
    }
}
