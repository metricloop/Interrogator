<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var
     */
    protected $table = 'answers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_id',
        'answerable_id',
        'answerable_type',
        'value',
        'options',
        'team_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'deleted_at',
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