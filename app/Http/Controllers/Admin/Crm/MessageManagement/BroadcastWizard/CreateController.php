<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastWizard;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsAudience;
use App\Models\Customers\SmsBroadcast;
use App\Models\Customers\SmsBroadcastEvent;
use App\Models\Customers\SmsCategory;
use App\Models\Customers\Tag;
use Illuminate\Http\Request;

/**
 * Broadcast Creation Wizard page. Steps: 1 choose message, 2 audience
 * type, 3 build audience, 4 review recipients, 5 review broadcast,
 * 6 created. Draft state persists server-side so Back never loses work
 * and drafts can be continued from the queue.
 */
class CreateController extends Controller
{
    public function __invoke(Request $request)
    {
        $event = null;
        if ($request->filled('event')) {
            $event = SmsBroadcastEvent::where('status', SmsBroadcastStatus::Draft->value)
                ->findOrFail($request->integer('event'));
        }

        return view('admin.crm.message_management.broadcast_wizard.create', [
            'event'      => $event,
            'categories' => SmsCategory::orderBy('name')->get(['id', 'name']),
            'messages'   => SmsBroadcast::active()->with('category')->orderBy('name')->get()
                ->map(fn ($m) => [
                    'id'          => $m->id,
                    'name'        => $m->name,
                    'category_id' => $m->sms_cat_id,
                    'category'    => $m->category?->name ?? 'No Category',
                    'content'     => $m->description ?? '',
                ])->values(),
            'tags'       => Tag::orderBy('name')->get(['id', 'name']),
            'audiences'  => SmsAudience::orderBy('name')->get(),
        ]);
    }
}
