<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MetricLoop\Interrogator\Exceptions\QuestionNotFoundException;

/**
 * Trait GetsInterrogated.
 */

trait GetsInterrogated
{
    /**
     * BelongsToMany relationship with ModelSection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function sections()
    {
        return Section::where('class_name', get_class($this))
            ->where('team_id', $this->current_team_id)
            ->get();
    }

    /**
     * HasMany relationship with ModelAnswer;
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers()
    {
        return $this->morphMany(Answer::class, 'answerable');
    }

    /**
     * Get an Answer based on a given Question or Question ID
     *
     * @param $question
     * @return Answer
     */
    public function getAnswerFromQuestion($question)
    {
        $question = Question::resolveSelf($question);

        if($question->group->section->class_name === get_class($this)) {
            try {
                return $this->answers()
                    ->where('question_id', $question->id)
                    ->where('team_id', $this->current_team_id)
                    ->firstOrFail();

            } catch (ModelNotFoundException $e) {
                return null;
            }
        }

        return null;

    }

    /**
     * Answer a Question.
     *
     * @param $question
     * @param $value
     * @return Model|Answer
     */
    public function answerQuestion($question, $value)
    {
        $question = Question::resolveSelf($question);
        $answer = $this->getAnswerFromQuestion($question);

        if(!$answer) {
            $answer = $this->createNewAnswer($question, $value);
        } else {
            $answer = $this->updateExistingAnswer($answer, $value);
        }
        
        $this->touch();

        return $answer;
    }

    /**
     * Create new Answer.
     *
     * @param Question $question
     * @param $value
     * @return Model
     */
    public function createNewAnswer(Question $question, $value)
    {
        return $this->answers()->create([
            'question_id' => $question->id,
            'value' => $value,
            'team_id' => $this->current_team_id
        ]);
    }

    /**
     * Update an existing Answer.
     *
     * @param Answer $answer
     * @param $value
     * @return Answer
     */
    public function updateExistingAnswer(Answer $answer, $value)
    {
        if($this->checkIfAnswerChanged($answer, $value)) {
            $answer->update([
                'value' => $value,
            ]);
        }
        return $answer;
    }

    /**
     * Check if the given answer is different from what's in the database.
     *
     * @param $answer
     * @param $value
     * @return bool
     */
    public function checkIfAnswerChanged($answer, $value)
    {
        if(strcmp($answer->value, $value) === 0) {
            return false;
        } else {
            return true;
        }
    }
}