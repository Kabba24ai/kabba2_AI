<?php

namespace App\Http\Resources\Api\Admin\V1\ProductCategories;

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
            'title' => $this->title ?? '',
            'slug' => $this->slug ?? '',
            'short_content' => $this->short_content ?? '',
            'content' => $this->content ?? '',
            'seo_title' => $this->seo_title ?? '',
            'seo_description' => $this->seo_description ?? '',
            'status' => $this->status ?? '',
            'is_featured' => $this->is_featured ?? false,
            'sort_order' => $this->sort_order ?? 0,

            'child_categories' => $this->whenLoaded('childCategories', function () {
                return ListResource::collection($this->childCategories);
            }),
        ];

        return $return;
    }
}
