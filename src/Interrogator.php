<?php

namespace MetricLoop\Interrogator;

class Interrogator
{
    /**
     * Creates a New Section.
     *
     * @param $name
     * @param array $options
     * @param string $class
     * @param null $team_id
     * @return mixed
     */
    public function createSection($name, $options = [], $class, $team_id = null)
    {
        $section = Section::create([
            'name' => $name,
            'slug' => str_slug($name . '_' . str_random(6), '_'),
            'class_name' => $class,
            'team_id' => $team_id,
        ]);

        $section = $section->syncOptions($options);

        return $section;
    }

    /**
     * Copies a Section to the target Class.
     * 
     * @param $section
     * @param $targetClass
     * @return mixed
     */
    public function copySection($section, $targetClass)
    {
        $newSection = $this->createSection($section->name, [], $targetClass);
        $section->groups->each(function($group) use ($newSection) {
            $this->copyGroup($group, $newSection);
        });
        return $newSection;
    }

    /**
     * Updates the gives section.
     *
     * @param $section
     * @param null $name
     * @param array $options
     * @param null $class
     * @return mixed
     */

    public function updateSection($section, $name = null, $options = [], $class = null)
    {
        $section = $this->resolveSection($section);

        $attributes = [];
        if(isset($name)) {
            $attributes['name'] = $name;
            $attributes['slug'] = str_slug($name . '_' . str_random(6), '_');
        }
        if(isset($class)) {
            $attributes['class_name'] = $class;
        }
        $section->update($attributes);
        $section = $section->syncOptions($options);

        return $section;
    }

    /**
     * Set specific Option on Section.
     *
     * @param $section
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setOptionOnSection($section, $key, $value)
    {
        return $this->resolveSection($section)->setOption($key, $value);
    }

    /**
     * Unset a specific Option on Section.
     *
     * @param $section
     * @param $key
     * @return mixed
     */
    public function unsetOptionOnSection($section, $key)
    {
        return $this->resolveSection($section)->unsetOption($key);
    }

    /**
     * Clears association of Section.
     *
     * @param $section
     * @return mixed
     */
    public function detachSection($section)
    {
        $section = $this->resolveSection($section);

        $section->class_name = null;
        $section->save();

        return $section;
    }

    /**
     * Updates the gives section.
     *
     * @param $section
     * @return mixed
     * @throws \Exception
     */
    public function deleteSection($section)
    {
        $section = $this->resolveSection($section);
        Section::destroy($section->id);
    }

    /**
     * Creates a New Group.
     *
     * @param $name
     * @param $section
     * @param array $options
     * @param null $team_id
     * @return mixed
     */
    public function createGroup($name, $section, $options = [], $team_id = null)
    {
        $section = $this->resolveSection($section);
        
        $group = Group::create([
            'name' => $name,
            'slug' => str_slug($name . '_' . str_random(6), '_'),
            'section_id' => $section->id,
            'team_id' => $team_id
        ]);

        $group = $group->syncOptions($options);

        return $group;
    }


    /**
     * Copies a Group to the given target Section.
     *
     * @param $group
     * @param $targetSection
     * @return mixed
     */
    public function copyGroup($group, $targetSection)
    {
        $newGroup = $this->createGroup($group->name, $targetSection, $group->options);
        $group->questions->each(function ($question) use ($newGroup) {
            $this->copyQuestion($question, $newGroup);
        });
        return $newGroup;
    }

    /**
     * Updates a Group.
     *
     * @param $group
     * @param null $name
     * @param null $section
     * @param array $options
     * @return mixed
     */
    public function updateGroup($group, $name = null, $section = null, $options = [])
    {
        $group = $this->resolveGroup($group);
        $section = $this->resolveSection($section);

        $attributes = [];
        if(isset($name)) {
            $attributes['name'] = $name;
            $attributes['slug'] = str_slug($name . '_' . str_random(6), '_');
        }
        if($section) {
            $attributes['section_id'] = $section->id;
        }

        $group->update($attributes);
        $group = $group->syncOptions($options);

        return $group;
    }

    /**
     * Set specific Option on Group.
     *
     * @param $group
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setOptionOnGroup($group, $key, $value)
    {
        return $this->resolveGroup($group)->setOption($key, $value);
    }

    /**
     * Unset a specific Option on Group.
     *
     * @param $group
     * @param $key
     * @return mixed
     */
    public function unsetOptionOnGroup($group, $key)
    {
        return $this->resolveGroup($group)->unsetOption($key);
    }

    /**
     * Updates the given section.
     *
     * @param $group
     * @return mixed
     * @throws \Exception
     */
    public function deleteGroup($group)
    {
        $group = $this->resolveGroup($group);
        $group->delete();
    }

    /**
     * Creates a New Question.
     *
     * @param $name
     * @param $question_type
     * @param $group
     * @param array $options
     * @param array $choices
     * @param null $team_id
     * @return mixed
     */
    public function createQuestion($name, $question_type, $group, $options = [], $choices = [], $team_id = null)
    {
        $group = $this->resolveGroup($group);
        $question_type = $this->resolveQuestionType($question_type);

        $question = Question::create([
            'name' => $name,
            'slug' => str_slug($name . '_' . str_random(6), '_'),
            'question_type_id' => $question_type->id,
            'group_id' => $group->id,
            'team_id' => $team_id,
        ]);

        $question = $question->syncOptions($options);
        $question = $question->addMultipleChoiceOption($choices);

        return $question;
    }

    /**
     * Updates a Question
     *
     * @param $question
     * @param null $name
     * @param null $group
     * @param array $options
     * @return mixed
     */
    public function updateQuestion($question, $name = null, $group = null, $options = [])
    {
        $question = $this->resolveQuestion($question);
        $group = $this->resolveGroup($group);

        $attributes = [];
        if(isset($name)) {
            $attributes['name'] = $name;
            $attributes['slug'] = str_slug($name . '_' . str_random(6), '_');
        }
        if($group) {
            $attributes['group_id'] = $group->id;
        }
        $question->update($attributes);
        $question = $question->syncOptions($options);

        return $question;
    }

    /**
     * Copies the given question to the Target Group.
     *
     * @param $question
     * @param $targetGroup
     * @return mixed
     */
    public function copyQuestion($question, $targetGroup)
    {
        return $this->createQuestion($question->name, $question->question_type_id, $targetGroup, $question->options, $question->choices);
    }

    /**
     * Set specific Option on Question.
     *
     * @param $question
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setOptionOnQuestion($question, $key, $value)
    {
        return $this->resolveQuestion($question)->setOption($key, $value);
    }

    /**
     * Unset a specific Option on Question.
     *
     * @param $question
     * @param $key
     * @return mixed
     */
    public function unsetOptionOnQuestion($question, $key)
    {
        return $this->resolveQuestion($question)->unsetOption($key);
    }

    /**
     * Create a Short Text Question
     *
     * @param $name
     * @param $group
     * @param array $options
     * @return mixed
     */
    public function createSmallTextQuestion($name, $group, $options = [])
    {
        $group = $this->resolveGroup($group);
        $question_type_id = QuestionType::where('slug', 'small_text')->first()->id;

        return $this->createQuestion($name, $question_type_id, $group, $options);
    }

    /**
     * Create a Short Text Question
     *
     * @param $name
     * @param $group
     * @param array $options
     * @return mixed
     */
    public function createLargeTextQuestion($name, $group, $options = [])
    {
        $group = $this->resolveGroup($group);
        $question_type_id = QuestionType::where('slug', 'large_text')->first()->id;

        return $this->createQuestion($name, $question_type_id, $group, $options);
    }

    /**
     * Create a Short Text Question
     *
     * @param $name
     * @param $group
     * @param array $options
     * @return mixed
     */
    public function createNumericQuestion($name, $group, $options = [])
    {
        $group = $this->resolveGroup($group);
        $question_type_id = QuestionType::where('slug', 'numeric')->first()->id;

        return $this->createQuestion($name, $question_type_id, $group, $options);
    }

    /**
     * Create a Short Text Question
     *
     * @param $name
     * @param $group
     * @param array $options
     * @return mixed
     */
    public function createDateTimeQuestion($name, $group, $options = [])
    {
        $group = $this->resolveGroup($group);
        $question_type_id = QuestionType::where('slug', 'date_time')->first()->id;

        return $this->createQuestion($name, $question_type_id, $group, $options);
    }

    /**
     * Create a Multiple Choice Question
     *
     * @param $name
     * @param $group
     * @param array $choices
     * @param bool $allows_multiple_choice_other
     * @return mixed
     */
    public function createMultipleChoiceQuestion($name, $group, $choices = [], $allows_multiple_choice_other = false)
    {
        $group = $this->resolveGroup($group);
        $question_type_id = QuestionType::where('slug', 'multiple_choice')->first()->id;

        $question = $this->createQuestion($name, $question_type_id, $group, [], $choices);
        if($allows_multiple_choice_other) {
            $question = $question->setAllowsMultipleChoiceOtherOption();
        }
        return $question;
    }

    /**
     * Create a Short Text Question
     *
     * @param $name
     * @param $group
     * @param array $options
     * @return mixed
     */
    public function createFileUploadQuestion($name, $group, $options = [])
    {
        $group = $this->resolveGroup($group);
        $question_type_id = QuestionType::where('slug', 'file_upload')->first()->id;

        return $this->createQuestion($name, $question_type_id, $group, $options);
    }

    /**
     * Deletes a Question.
     *
     * @param $question
     * @throws \Exception
     */
    public function deleteQuestion($question)
    {
        $question = $this->resolveQuestion($question);
        $question->delete();
    }

    /**
     * Search for term.
     *
     * @param $term
     * @param null $class_name
     * @param null $question_ids
     * @return mixed
     */
    public function searchExact($term, $class_name = null, $question_ids = null, $team_id = null)
    {
        $query = Answer::where('value', $term)
            ->where('team_id', $team_id);
        if($class_name) {
            $query = $query->where('answerable_type', $class_name);
        }
        if($question_ids) {
            $query = $query->whereIn('question_id', $question_ids);
        }
        return $query->get();
    }

    /**
     * Add wild cards before and after search term. Handle any wildcard replacements.
     *
     * @param $term
     * @param null $class_name
     * @param array $question_ids
     * @param null $team_id
     * @return mixed
     */
    public function search($term, $class_name = null, $question_ids = [], $team_id = null)
    {
        $term = '%' . $term . '%';

        $query = Answer::where('value', 'LIKE', $this->wildcardReplace($term))
            ->where('team_id', $team_id);
        if($class_name) {
            $query = $query->where('answerable_type', $class_name);
        }
        if($question_ids) {
            $query = $query->whereIn('question_id', $question_ids);
        }
        return $query->get();
    }

    /**
     * Replaces user-friendly wildcards with SQL-specific wildcards.
     *
     * @param $term
     * @return mixed
     */
    private function wildcardReplace($term)
    {
        return str_replace(['*', '?'], ['%', '_'], $term);
    }

    /**
     * Scopes the search based on Question Type.
     *
     * @param $term
     * @param null $class_name
     * @param $question
     * @param null $team_id
     * @return mixed
     */
    public function searchQuestion($term, $class_name = null, $question, $team_id = null)
    {
        $question = $this->resolveQuestion($question);

        return $this->search($term, $class_name, [$question->id], $team_id);
    }

    /**
     * Scopes the search based on Question Type.
     *
     * @param $term
     * @param null $class_name
     * @param null $team_id
     * @return mixed
     */
    public function searchSmallText($term, $class_name = null, $team_id = null)
    {
        $question_type = QuestionType::where('slug', 'small_text')->first();
        $questions = Question::where('question_type_id', $question_type->id)->pluck('id')->toArray();
        return $this->search($term, $class_name, $questions, $team_id);
    }

    /**
     * Scopes the search based on Question Type.
     *
     * @param $term
     * @param null $class_name
     * @param null $team_id
     * @return mixed
     */
    public function searchLargeText($term, $class_name = null, $team_id = null)
    {
        $question_type = QuestionType::where('slug', 'large_text')->first();
        $questions = Question::where('question_type_id', $question_type->id)->pluck('id')->toArray();
        return $this->search($term, $class_name, $questions, $team_id);
    }

    /**
     * Scopes the search based on Question Type.
     *
     * @param $term
     * @param null $class_name
     * @param null $team_id
     * @return mixed
     */
    public function searchNumeric($term, $class_name = null, $team_id = null)
    {
        $question_type = QuestionType::where('slug', 'numeric')->first();
        $questions = Question::where('question_type_id', $question_type->id)->pluck('id')->toArray();
        return $this->search($term, $class_name, $questions, $team_id);
    }

    /**
     * Scopes the search based on Question Type.
     *
     * @param $term
     * @param null $class_name
     * @param null $team_id
     * @return mixed
     */
    public function searchDateTime($term, $class_name = null, $team_id = null)
    {
        $question_type = QuestionType::where('slug', 'date_time')->first();
        $questions = Question::where('question_type_id', $question_type->id)->pluck('id')->toArray();
        return $this->search($term, $class_name, $questions, $team_id);
    }

    /**
     * Scopes the search based on Question Type.
     *
     * @param $term
     * @param null $class_name
     * @param null $team_id
     * @return mixed
     */
    public function searchMultipleChoice($term, $class_name = null, $team_id = null)
    {
        $question_type = QuestionType::where('slug', 'multiple_choice')->first();
        $questions = Question::where('question_type_id', $question_type->id)->pluck('id')->toArray();
        return $this->search($term, $class_name, $questions, $team_id);
    }

    /**
     * Scopes the search based on Question Type.
     *
     * @param $term
     * @param null $class_name
     * @param null $team_id
     * @return mixed
     */
    public function searchFileUpload($term, $class_name = null, $team_id = null)
    {
        $question_type = QuestionType::where('slug', 'file_upload')->first();
        $questions = Question::where('question_type_id', $question_type->id)->pluck('id')->toArray();
        return $this->search($term, $class_name, $questions, $team_id);
    }

    /**
     * Return list of all Sections.
     *
     * @param null $class
     * @param null $team_id
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getSections($class = null, $team_id = null)
    {
        return Section::where('team_id', $team_id)
            ->when($class, function($query) use ($class) {
                return $query->where('class_name', $class);
            })
            ->get()
            ->sortBy('order');
    }

    /**
     * Return list of all Groups.
     *
     * @param null $section
     * @param null $team_id
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getGroups($section = null, $team_id = null)
    {
        return Group::where('team_id', $team_id)
            ->when($section, function($query) use ($section) {
                return $query->where('section_id', $this->resolveSection($section)->id);
            })
            ->get()
            ->sortBy('order');
    }

    /**
     * Return list of all Questions.
     *
     * @param null $question_type
     * @param null $group
     * @param null $team_id
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getQuestions($question_type = null, $group = null, $team_id = null)
    {
        return Question::where('team_id', $team_id)
            ->when($group, function($query) use ($group) {
                return $query->where('group_id', $this->resolveGroup($group)->id);
            })
            ->when($question_type, function($query) use ($question_type) {
                return $query->where('question_type_id', $this->resolveQuestionType($question_type)->id);
            })
            ->with('type')
            ->get()
            ->sortBy('order');
    }

    /**
     * Returns a single Section.
     *
     * @param null $section
     * @return bool|null
     */
    public function getSection($section = null)
    {
        if(is_null($section)) {
            return false;
        }
        $section = $this->resolveSection($section);
        return $section;
    }

    /**
     * Returns a single Section.
     *
     * @param null $group
     * @return bool|null
     */
    public function getGroup($group = null)
    {
        if(is_null($group)) {
            return false;
        }
        $group = $this->resolveGroup($group);
        return $group;
    }

    /**
     * Returns a single Question.
     *
     * @param null $question
     * @return null
     */
    public function getQuestion($question = null)
    {
        if(is_null($question)) {
            return false;
        }
        $question = $this->resolveQuestion($question);
        return $question;
    }

    /**
     * Resolves Section if given Object, ID, or Slug.
     *
     * @param $section
     * @return mixed
     */
    private function resolveSection($section)
    {
        if(!$section instanceof Section) {
            if(is_numeric($section)) {
                $section = Section::findOrFail($section);
            } else {
                $section = Section::whereSlug($section)->first();
            }
        }
        return $section;
    }

    /**
     * Resolves Group if given Object, ID, or Slug.
     *
     * @param $group
     * @return mixed
     */
    private function resolveGroup($group)
    {
        if(!$group instanceof Group) {
            if(is_numeric($group)) {
                $group = Group::findOrFail($group);
            } else {
                $group = Group::whereSlug($group)->first();
            }
        }
        return $group;
    }

    /**
     * Resolves Question if given Object, ID, or Slug.
     *
     * @param $question
     * @return mixed
     */
    private function resolveQuestion($question)
    {
        if(!$question instanceof Question) {
            if(is_numeric($question)) {
                $question = Question::with('type')->findOrFail($question);
            } else {
                $question = Question::whereSlug($question)->with('type')->first();
            }
        }
        return $question;
    }

    /**
     * Resolves QuestionType if given Object, ID, or Slug.
     *
     * @param $questionType
     * @return mixed
     */
    private function resolveQuestionType($questionType)
    {
        if(!$questionType instanceof QuestionType) {
            if(is_numeric($questionType)) {
                $questionType = QuestionType::findOrFail($questionType);
            } else {
                $questionType = QuestionType::whereSlug($questionType)->first();
            }
        }
        return $questionType;
    }

}