<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

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
        'question_type_id',
        'options',
        'choices',
        'group_id',
    ];

    /**
     * The attributes that should be mutated as Dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
        'choices' => 'array',
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
        $this->table = 'questions';
    }

    /**
     * Returns the Question Type for this Question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(QuestionType::class, null, null, 'QuestionType');
    }

    /**
     * Returns the Group to which this Question belongs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Returns the Answers that belong to this Question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Delete Answers before deleting Question itself.
     *
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        $this->answers->each(function ($answer) {
            $answer->delete();
        });
        return parent::delete();
    }

    /**
     * Accessor for attribute.
     *
     * @return bool
     */
    public function allowMultipleChoiceOther()
    {
        return isset($this->options['allow_multiple_choice_other']) ? true : false;
    }

    /**
     * Updates the Options for the Question.
     *
     * @param $options
     * @return $this
     */
    public function updateOptions($options = [])
    {
        if(!is_array($options)) {
            $options = [$options];
        }
        $this->options = $options;
        $this->save();

        return $this;
    }

    /**
     * Add Multiple Choice options to Question.
     *
     * @param $newChoice
     * @return $this
     */
    public function addMultipleChoiceOption($newChoice)
    {
        if(!is_array($newChoice)) {
            $newChoice = [$newChoice];
        }
        $this->choices = array_merge(is_null($this->choices) ? [] : $this->choices, $newChoice);
        $this->save();

        return $this;
    }

    /**
     * Add Other as Multiple Choice option to Question.
     *
     * @return $this
     */
    public function addMultipleChoiceOtherOption()
    {
        $this->options = array_merge(is_null($this->options) ? [] : $this->options, ['allow_multiple_choice_other' => true]);
        $this->save();

        return $this;
    }
}