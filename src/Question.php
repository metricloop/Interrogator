<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use MetricLoop\Interrogator\Exceptions\QuestionNotFoundException;

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
        'team_id',
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'order'
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
     * Restores Question and Answers with matching "deleted_at" timestamps.
     */
    public function restore()
    {
        $deleted_at = $this->deleted_at;
        $this->answers()->withTrashed()->get()->filter(function ($answer) use ($deleted_at) {
            $first = $second = $deleted_at;
            return $answer->deleted_at->gte($first) && $answer->deleted_at->lte($second->addSecond());
        })->each(function ($answer) {
            $answer->restore();
        });
        parent::restore();
    }

    /**
     * Accessor for attribute.
     *
     * @return bool
     */
    public function allowsMultipleChoiceOther()
    {
        return isset($this->options['allows_multiple_choice_other']) ? true : false;
    }

    /**
     * Set an Option (brand new or updating existing).
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function setOption($key, $value)
    {
        $options = $this->options;
        $options[$key] = $value;
        $this->options = $options;
        $this->save();
        return $this;
    }

    /**
     * Unset an Option.
     *
     * @param $key
     * @return $this
     */
    public function unsetOption($key)
    {
        $options = $this->options;
        unset($options[$key]);
        $this->options = $options;
        $this->save();
        return $this;
    }

    /**
     * Sync list of Options.
     *
     * @param $options
     * @return $this
     */
    public function syncOptions($options)
    {
        $currentOptions = is_array($this->options) ? $this->options : [];
        $optionsToRemove = array_diff_key($currentOptions, $options);
        foreach($options as $key => $value) {
            $this->setOption($key, $value);
        }
        foreach($optionsToRemove as $key => $value) {
            $this->unsetOption($key);
        }

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
    public function setAllowsMultipleChoiceOtherOption()
    {
        $this->setOption('allows_multiple_choice_other', true);
        return $this;
    }

    /**
     * Accessor for attribute.
     *
     * @return int
     */
    public function getOrderAttribute()
    {
        return isset($this->options['order']) ? $this->options['order'] : 1;
    }

    /**
     * Resolves Question object regardless of given identifier.
     *
     * @param $question
     * @param bool $withTrashed
     * @return \Illuminate\Database\Eloquent\Collection|Model|null
     * @throws QuestionNotFoundException
     */
    public static function resolveSelf($question, $withTrashed = false)
    {
        if(is_null($question)) { return null; }

        if(!$question instanceof Question) {
            if(is_numeric($question)) {
                try {
                    if($withTrashed) {
                        $question = Question::withTrashed()->with('type')->findOrFail($question);
                    } else {
                        $question = Question::with('type')->findOrFail($question);
                    }
                } catch (ModelNotFoundException $e) {
                    throw new QuestionNotFoundException('Question not found with the given ID.');
                }
            } else {
                try {
                    if($withTrashed) {
                        $question = Question::withTrashed()->whereSlug($question)->with('type')->firstOrFail();
                    } else {
                        $question = Question::whereSlug($question)->with('type')->firstOrFail();
                    }
                } catch (ModelNotFoundException $e) {
                    throw new QuestionNotFoundException('Question not found with the given slug.');
                }
            }
        }
        return $question;
    }
}