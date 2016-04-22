<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $table;

    protected $fillable = [
        'question_id',
        'answerable_id',
        'answerable_type',
        'value',
        'options'
    ];
    /**
     * Get all of the owning answerable models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function answerable()
    {
        return $this->morphTo();
    }
}