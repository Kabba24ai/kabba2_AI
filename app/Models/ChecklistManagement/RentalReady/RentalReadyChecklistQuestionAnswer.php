<?php

namespace App\Models\ChecklistManagement\RentalReady;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalReadyChecklistQuestionAnswer extends Model
{
    use SoftDeletes;

    protected $fillable = ['unique_id', 'answer_name', 'question_id', 'type', 'index_number'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'ANS'); // Prefix ANS
            }
        });
    }

    public function question() {
        return $this->belongsTo(RentalReadyChecklistQuestion::class, 'question_id');
    }
}
