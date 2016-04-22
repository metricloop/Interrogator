<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;

class QuestionType extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Creates a new instance of the model.
     *
     * Question constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = 'question_types';
    }

    /**
     * Returns the Group to which this Question belongs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}