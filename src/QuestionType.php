<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MetricLoop\Interrogator\Exceptions\QuestionTypeNotFoundException;

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

    /**
     * Resolves QuestionType object regardless of given identifier.
     * 
     * @param $questionType
     * @return null
     * @throws QuestionTypeNotFoundException
     */
    public static function resolveSelf($questionType)
    {
        if(is_null($questionType)) { return null; }

        if(!$questionType instanceof QuestionType) {
            if(is_numeric($questionType)) {
                try {
                    $questionType = QuestionType::findOrFail($questionType);
                } catch (ModelNotFoundException $e) {
                    throw new QuestionTypeNotFoundException('Question Type not found with the given ID.');
                }
            } else {
                try {
                    $questionType = QuestionType::whereSlug($questionType)->firstOrFail();
                } catch (ModelNotFoundException $e) {
                    throw new QuestionTypeNotFoundException('Question Type not found with the given slug.');
                }
            }
        }
        return $questionType;
    }
}