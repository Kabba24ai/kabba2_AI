<?php

namespace Database\Seeders\Tutorials;

use App\Models\Tutorials\SystemLogicDocument;
use Illuminate\Database\Seeder;

class DispatchSystemLogicSeeder extends Seeder
{
    public function run(): void
    {
        $records = [

            // ── Driver Assignment Logic ────────────────────────────────
            [
                'section_key' => 'driver_assignment_logic',
                'title'       => 'Driver CDL Requirement Constraint',
                'summary'     => 'Drivers without the required CDL classification cannot be assigned to trucks or trailers that mandate a CDL.',
                'logic_body'  => "The Dispatch AI must not assign a driver to a truck or trailer combination that requires a CDL unless the driver has the required CDL A or CDL B classification configured in HRM.\n\nDriver capability settings are hard constraints, not suggestions. If no qualified driver is available, the system should flag the assignment for manager review rather than forcing an unqualified assignment.\n\nCDL type is stored on the driver record in HRM (cdl_a / cdl_b boolean fields). Truck and trailer records each carry a requires_cdl flag.",
            ],
            [
                'section_key' => 'driver_assignment_logic',
                'title'       => 'Delivery vs Return Slot Assignment',
                'summary'     => 'Each driver job is classified as either a Delivery or a Return and stored in separate priority queues.',
                'logic_body'  => "Dispatch creates separate delivery_jobs and return_jobs for each driver. These are sorted by their respective priority fields (delivery_priority / pickup_priority).\n\nA driver card in Combined view merges both queues and sorts by the numeric priority value, with deliveries and returns interleaved as the priorities dictate.\n\nIf priorities are identical across delivery and return jobs, deliveries are shown first.",
            ],

            // ── Truck Assignment Logic ─────────────────────────────────
            [
                'section_key' => 'truck_assignment_logic',
                'title'       => 'Truck Capacity and Equipment Size Matching',
                'summary'     => 'The AI should only assign a truck if its capacity rating accommodates the equipment being transported.',
                'logic_body'  => "Each truck in the Dispatch AI Trucks table carries a weight capacity and a size/type classification. Equipment transport rules define the minimum truck class required for each equipment category.\n\nWhen the AI builds draft assignments, it must check that the selected truck's capacity is not exceeded by the total weight of all equipment on that run. If no truck meets capacity, the AI should surface a conflict rather than assigning an undersized truck.",
            ],
            [
                'section_key' => 'truck_assignment_logic',
                'title'       => 'Hitch Type Compatibility',
                'summary'     => 'Trucks must have a compatible hitch type before they can be matched with any trailer requiring that hitch.',
                'logic_body'  => "Trucks carry a hitch_type JSON array (e.g., ['gooseneck', 'ball_5th_wheel']). Trailers carry a required_hitch string. The AI must confirm at least one element in the truck's hitch_type array matches the trailer's required_hitch before assigning the pair.",
            ],

            // ── Trailer Assignment Logic ───────────────────────────────
            [
                'section_key' => 'trailer_assignment_logic',
                'title'       => 'Trailer Not Required for All Deliveries',
                'summary'     => 'Not every delivery requires a trailer. The AI should not force a trailer assignment when equipment fits in the truck bed.',
                'logic_body'  => "Some equipment (e.g., small attachments, walk-behind tools) can be transported in a truck bed without a trailer. Equipment transport rules define whether a trailer is mandatory, optional, or prohibited for each equipment category.\n\nIf a trailer is optional and none is available, the AI should proceed with a truck-only assignment and log a note that no trailer was used.",
            ],

            // ── Equipment Transport Logic ──────────────────────────────
            [
                'section_key' => 'equipment_transport_logic',
                'title'       => 'Equipment Transport Mode Selection',
                'summary'     => 'Each order product carries a delivery and pickup transport mode that determines which truck/trailer class is required.',
                'logic_body'  => "order_products stores delivery_transport_mode and pickup_transport_mode. These values are set during order creation and reflect what the customer ordered (e.g., 'flat-bed delivery', 'customer pickup').\n\nWhen mode is 'customer_pickup', no Dispatch truck assignment is created for that leg. When mode is 'delivery', a Dispatch job record is created for the delivery leg.",
            ],

            // ── Routing Logic ─────────────────────────────────────────
            [
                'section_key' => 'routing_logic',
                'title'       => 'Priority-Based Route Ordering',
                'summary'     => 'Delivery and return jobs within a driver\'s day are sequenced by their numeric priority value (lower = first).',
                'logic_body'  => "Priority values are set manually by dispatchers on driver cards (dispatch-priority-badge buttons). Lower numbers are executed first within the day.\n\nThe system does not auto-compute geographic routing currently. Priority assignment is the dispatcher's responsibility. Future AI routing enhancement would override or suggest priority based on distance clustering.",
            ],

            // ── Early Delivery Logic ───────────────────────────────────
            [
                'section_key' => 'early_delivery_logic',
                'title'       => 'Early Delivery Window Configuration',
                'summary'     => 'The system allows scheduled deliveries to be made within a configurable number of days before the customer\'s official delivery date.',
                'logic_body'  => "The early delivery window is configured in Dispatch AI Settings (early_delivery_days). If a delivery is scheduled within this window before the customer's delivery_date, the AI may treat it as eligible for early dispatch.\n\nEarly delivery requires manager acknowledgment in some configurations. The policy_overrides JSON in dispatch_ai_settings can override this at a per-rule level.",
            ],

            // ── Schedule Conflict Logic ────────────────────────────────
            [
                'section_key' => 'schedule_conflict_logic',
                'title'       => 'Overdue Return Detection',
                'summary'     => 'Any order product whose pickup_date has passed without a completed return is flagged as overdue.',
                'logic_body'  => "An overdue order product is one where pickup_date < today() and the order has not been marked as returned. These appear as red blocks on the Schedule Assignment calendar.\n\nOn Driver Cards, overdue delivery or return jobs show a red exclamation-triangle icon next to the order number. The icon is determined by comparing delivery_date or pickup_date to Carbon::today().",
            ],

            // ── AI Dispatch Logic ──────────────────────────────────────
            [
                'section_key' => 'ai_dispatch_logic',
                'title'       => 'AI Draft Generation Process',
                'summary'     => 'The AI draft runs against all pending dispatch jobs and produces suggested driver/truck/trailer assignments stored in dispatch_ai_drafts.',
                'logic_body'  => "Running AI Dispatch Now triggers RunDraftController, which calls DispatchAIService. The service evaluates all active general rules, driver capabilities, truck and trailer constraints, equipment rules, and routing preferences.\n\nDraft assignments are written to dispatch_ai_draft_assignments linked to a dispatch_ai_drafts record. These are suggestions only — dispatchers must approve or override each assignment before it becomes live.",
            ],

            // ── AI Policy Logic ────────────────────────────────────────
            [
                'section_key' => 'ai_dispatch_logic',
                'title'       => 'Policy Overrides and Confidence Scoring',
                'summary'     => 'The AI Policy tab allows per-rule overrides and confidence thresholds that adjust how aggressively the AI enforces each constraint.',
                'logic_body'  => "Dispatch AI Settings stores a policy_overrides JSON column. Each entry maps a rule_key to override parameters (e.g., confidence_threshold, require_manager_review, disabled).\n\nIntelligence Rules (dispatch_intelligence_rules) carry a confidence_score (0.0–1.0). Rules below the configured threshold are surfaced as suggestions rather than hard blocks.",
            ],

            // ── Manual Override Logic ──────────────────────────────────
            [
                'section_key' => 'manual_override_logic',
                'title'       => 'Dispatcher Can Override Any AI Suggestion',
                'summary'     => 'AI-generated assignments are advisory. Dispatchers may change any assignment without restriction.',
                'logic_body'  => "The Dispatch module is designed so that human dispatchers retain full control. AI draft assignments exist in a separate draft state and are never automatically applied to live orders.\n\nA dispatcher reviewing the AI draft may accept, modify, or reject each suggested assignment. Manual changes do not trigger AI re-evaluation unless the dispatcher explicitly re-runs the AI.",
            ],

            // ── Manager Approval Logic ─────────────────────────────────
            [
                'section_key' => 'manager_approval_logic',
                'title'       => 'Intelligence Rules Require Admin Approval',
                'summary'     => 'New Intelligence Rules submitted without admin approval are queued as pending and excluded from active AI evaluation.',
                'logic_body'  => "dispatch_intelligence_rules carries an approved_by_admin boolean. Only rules where approved_by_admin = true and is_active = true are loaded by the AI during draft generation.\n\nPending rules are visible in the Intelligence Rules tab with a Pending badge. A manager or admin can approve rules from that interface.",
            ],

            // ── Safety / Compliance Logic ──────────────────────────────
            [
                'section_key' => 'safety_compliance_logic',
                'title'       => 'CDL Verification is a Hard Stop',
                'summary'     => 'The system treats CDL mismatches as hard stops, not warnings. No assignment is issued if CDL is missing.',
                'logic_body'  => "Unlike most AI suggestions which can be overridden, CDL violations are enforced as hard constraints in the AI policy engine. The AI will not produce a draft assignment that places a non-CDL driver in a CDL-required vehicle.\n\nIf a dispatcher needs to override this for an exceptional circumstance, they must do so by manually creating the assignment outside the AI draft and documenting the override reason.",
            ],

        ];

        foreach ($records as $record) {
            SystemLogicDocument::firstOrCreate(
                [
                    'module_key'  => 'dispatch',
                    'section_key' => $record['section_key'],
                    'title'       => $record['title'],
                ],
                array_merge($record, [
                    'module_key' => 'dispatch',
                    'status'     => 'active',
                    'visibility' => 'internal_admin',
                    'sort_order' => 0,
                ])
            );
        }
    }
}
