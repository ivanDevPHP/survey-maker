<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['survey_id', 'question', 'data', 'type', 'description'];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
