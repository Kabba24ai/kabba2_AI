<?php

namespace App\Models\WebsiteManagement\FaqPage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;

class FaqQuestions extends Model
{
    use HasFactory;

    protected $table = 'faq_questions';

    protected $fillable = [
        'unique_id',
        'question_name',
        'category_id',
        'answer',
        'status',
        'related_question_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'FAQ-QUE');

            // assign next index automatically
            if (is_null($model->id)) {
                $model->id = (self::max('id') ?? 0) + 1;
            }
        });
    }
    public function category()
    {
        return $this->belongsTo(FaqCategory::class, 'category_id', 'id');
    }
    public function related()
    {
        return $this->belongsTo(FaqQuestions::class, 'related_question_id');
    }
}
