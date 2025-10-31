<?php

namespace App\Http\Resources\Api\Admin\V1\Customers;

use App\Http\Resources\Api\Admin\V1\CustomerNotes\ListResource as CustomerNotesListResource;
use App\Http\Resources\Api\Admin\V1\CustomerTags\ListResource as CustomerTagsListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? 0,
            'full_name' => $this->full_name ?? '',
            'email' => $this->email ?? '',
            'status' => $this->status ?? '',
            'first_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'company_name' => $this->company_name ?? '',
            'company_phone' => $this->company_phone ?? '',
            'company_website' => $this->company_website ?? '',
            'tax_document_status' => $this->tax_document_status ?? '',
            'phone' => $this->phone ?? '',
            'dob' => $this->dob ?? '',
            'authorize_profile_id' => $this->authorize_profile_id ?? '',
            'is_guest' => $this->is_guest ?? false,
            'tax_status' => $this->tax_status ?? '',
            //'tax_document_media_id' => $this->tax_document_media_id ?? '',
            'tax_document_upload_date' => $this->tax_document_upload_date ?? '',
            'tax_document_valid_until' => $this->tax_document_valid_until ?? '',
            'is_credit_account' => $this->is_credit_account ?? false,
            'credit_limit' => $this->credit_limit ?? 0,
            //'account_approved_by' => $this->account_approved_by ?? '',
            'account_application_completed' => $this->account_application_completed ?? false,
            'is_reset' => $this->is_reset ?? false,
            'tax_document_type' => $this->tax_document_type ?? '',
            // 'tax_status_approved_by' => $this->tax_status_approved_by ?? '',
            'tax_document_media' => $this?->media->url ?? '',

            'tags' => CustomerTagsListResource::collection($this->getTagObjectsAttribute()),
            'notes' => CustomerNotesListResource::collection($this->whenLoaded('notes')),

        ];

        return $return;
    }
}
