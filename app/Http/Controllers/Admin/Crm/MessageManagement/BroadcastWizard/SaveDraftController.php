<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastWizard;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcast;
use App\Models\Customers\SmsBroadcastEvent;
use App\Models\Customers\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Persists wizard progress onto a Draft broadcast event. Called as each
 * step is completed so Back navigation and Continue-Wizard-from-queue
 * never lose work. Only Draft events may be written to.
 */
class SaveDraftController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_id'         => ['nullable', 'integer', 'exists:sms_broadcast_events,id'],
            'wizard_step'      => ['required', 'integer', 'between:1,6'],
            'sms_broadcast_id' => ['nullable', 'exists:sms_broadcasts,id'],
            'audience_type'    => ['nullable', 'in:all,tags,saved'],
            'sms_audience_id'  => ['nullable', 'exists:sms_audiences,id'],
            'positive_mode'    => ['nullable', 'in:any,all'],
            'include_tags'     => ['nullable', 'array'],
            'include_tags.*'   => ['integer', 'exists:tags,id'],
            'exclude_tags'     => ['nullable', 'array'],
            'exclude_tags.*'   => ['integer', 'exists:tags,id'],
        ]);

        if (!empty($data['event_id'])) {
            $event = SmsBroadcastEvent::findOrFail($data['event_id']);
            if ($event->status !== SmsBroadcastStatus::Draft) {
                return response()->json([
                    'success' => false,
                    'message' => 'This broadcast is no longer a draft.',
                ], 422);
            }
        } else {
            $event = new SmsBroadcastEvent([
                'status'     => SmsBroadcastStatus::Draft,
                'created_by' => auth()->id(),
            ]);
        }

        // Step 1 — source message (snapshot refreshes at queue time)
        if (array_key_exists('sms_broadcast_id', $data) && $data['sms_broadcast_id']) {
            $message = SmsBroadcast::with('category')->findOrFail($data['sms_broadcast_id']);
            $event->sms_broadcast_id = $message->id;
            $event->name             = $message->name;
            $event->message_name     = $message->name;
            $event->message_content  = $message->description;
            $event->category_name    = $message->category?->name;
        }

        // Steps 2–3 — audience rules
        if (!empty($data['audience_type'])) {
            $event->audience_type   = $data['audience_type'] === 'all' ? 'all' : 'tags';
            $event->sms_audience_id = $data['sms_audience_id'] ?? null;
            $event->positive_mode   = $data['audience_type'] === 'all' ? null : ($data['positive_mode'] ?? 'any');

            $include = $data['audience_type'] === 'all' ? [] : array_values($data['include_tags'] ?? []);
            $exclude = array_values($data['exclude_tags'] ?? []);

            $tagNames = Tag::whereIn('id', array_merge($include, $exclude))->pluck('name', 'id');

            $event->include_tag_ids   = $include;
            $event->include_tag_names = array_values(array_map(fn ($id) => $tagNames[$id] ?? "#{$id}", $include));
            $event->exclude_tag_ids   = $exclude;
            $event->exclude_tag_names = array_values(array_map(fn ($id) => $tagNames[$id] ?? "#{$id}", $exclude));

            // Saved audiences resolve fresh each use; label the source
            $event->audience_description = null;
            if (!empty($data['sms_audience_id'])) {
                $audience = \App\Models\Customers\SmsAudience::find($data['sms_audience_id']);
                if ($audience) {
                    $event->audience_description = 'Saved audience: ' . $audience->name;
                }
            }
        }

        $event->wizard_step = max((int) $event->wizard_step, (int) $data['wizard_step']);
        $event->status = SmsBroadcastStatus::Draft;
        if (!$event->exists) {
            $event->created_by = auth()->id();
        }
        $event->save();

        return response()->json(['success' => true, 'event_id' => $event->id]);
    }
}
