<?php

namespace App\Http\Resources\Api\TimeTracker\V1\TimeClock;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TimeEntryCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'entries' => TimeEntryResource::collection($this->collection),
        ];
    }
}
