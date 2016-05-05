<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Schema\Blueprint;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class InterrogatorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Eloquent::unguard();

        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->schema()->create('users', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->unsignedInteger('current_team_id')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('clients', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->unsignedInteger('current_team_id')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('question_types', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        $question_types = ['Small Text', 'Large Text', 'Numeric', 'Date & Time', 'Multiple Choice', 'File Upload'];
        foreach($question_types as $question_type) {
            QuestionType::create([
                'name' => $question_type,
                'slug' => str_slug($question_type, '_'),
            ]);
        }

        $this->schema()->create('sections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('class_name')->nullable();
            $table->text('options')->nullable();
            $table->unsignedInteger('team_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        $this->schema()->create('groups', function (Blueprint $table)  {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('options')->nullable();
            $table->unsignedInteger('section_id')->index();
            $table->unsignedInteger('team_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('section_id')->references('id')->on('sections');
        });
        $this->schema()->create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('question_type_id')->index();
            $table->text('options')->nullable();
            $table->text('choices')->nullable();
            $table->unsignedInteger('group_id')->index();
            $table->unsignedInteger('team_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('question_type_id')->references('id')->on('question_types');
            $table->foreign('group_id')->references('id')->on('groups');
        });

        $this->schema()->create('answers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('question_id')->index();
            $table->morphs('answerable');
            $table->text('value');
            $table->text('options')->nullable();
            $table->unsignedInteger('team_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('questions');
        });
    }
    public function tearDown()
    {
        $this->schema()->drop('answers');
        $this->schema()->drop('questions');
        $this->schema()->drop('groups');
        $this->schema()->drop('sections');
        $this->schema()->drop('question_types');
        $this->schema()->drop('clients');
        $this->schema()->drop('users');
    }
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }
    public function createTestQuestion($interrogator, $offset = 1)
    {
        return $interrogator->createQuestion('Question ' . $offset, 1, $this->createTestGroup($interrogator, $offset), ['option_1' => 'Option 1'], 1);
    }
    public function createTestGroup($interrogator, $offset = 1)
    {
        return $interrogator->createGroup('Group ' . $offset, $this->createTestSection($interrogator, $offset), ['option_1' => 'Option 1'], 1);
    }
    public function createTestSection($interrogator, $offset = 1)
    {
        return $interrogator->createSection('Section ' . $offset, ['option_1' => 'Option 1'], 'MetricLoop\Interrogator\User', 1);
    }

    /*
     * -------------------------------------------------------
     * Interrogator Test (Q&A)
     * -------------------------------------------------------
     * /

    /** @test */
    public function can_create_section()
    {
        $interrogator = new Interrogator();
        $options = ['option_1' => 'Option 1'];
        $section = $interrogator->createSection('Section 1', $options, 'MetricLoop\Interrogator\User');

        $this->assertEquals('Section 1', $section->name);
        $this->assertEquals(['option_1' => 'Option 1'], $section->options);
    }

    /** @test */
    public function can_update_section()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);

        $options = ['option_1' => 'Option 2'];
        $section = $interrogator->updateSection($section, 'Section 2', $options);
        $this->assertEquals('Section 2', $section->name);
        $this->assertEquals(['option_1' => 'Option 2'], $section->options);
        $this->assertNotEquals('Section 1', $section->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $section->options);
    }

    /** @test */
    public function can_update_section_with_section_id()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);

        $options = ['option_1' => 'Option 2'];
        $section = $interrogator->updateSection($section->id, 'Section 2', $options);
        $this->assertEquals('Section 2', $section->name);
        $this->assertEquals(['option_1' => 'Option 2'], $section->options);
        $this->assertNotEquals('Section 1', $section->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $section->options);
    }

    /** @test */
    public function can_update_section_with_section_slug()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);

        $options = ['option_1' => 'Option 2'];
        $section = $interrogator->updateSection($section->slug, 'Section 2', $options);
        $this->assertEquals('Section 2', $section->name);
        $this->assertEquals(['option_1' => 'Option 2'], $section->options);
        $this->assertNotEquals('Section 1', $section->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $section->options);
    }

    /** @test */
    public function can_delete_section()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);

        $interrogator->deleteSection($section);
        $this->assertTrue(Section::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_delete_section_with_section_id()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);

        $interrogator->deleteSection($section->id);
        $this->assertTrue(Section::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_delete_section_with_section_slug()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);

        $interrogator->deleteSection($section->slug);
        $this->assertTrue(Section::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_create_group()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);
        $group = $interrogator->createGroup('Group 1', $section, ['option_1' => 'Option 1'], 1);

        $this->assertEquals('Group 1', $group->name);
        $this->assertEquals(['option_1' => 'Option 1'], $group->options);
        $this->assertEquals(1, $group->section_id);
    }

    /** @test */
    public function can_update_group()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\User', 1);

        $group = $interrogator->updateGroup($group, 'Group 2', $section2, ['option_1' => 'Option 2']);
        $this->assertEquals('Group 2', $group->name);
        $this->assertEquals(['option_1' => 'Option 2'], $group->options);
        $this->assertEquals(2, $group->section_id);
        $this->assertNotEquals('Group 1', $group->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $group->options);
        $this->assertNotEquals(1, $group->section_id);
    }

    /** @test */
    public function can_update_group_with_group_id()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\User');

        $group = $interrogator->updateGroup($group, 'Group 2', $section2->id, ['option_1' => 'Option 2']);
        $this->assertEquals('Group 2', $group->name);
        $this->assertEquals(['option_1' => 'Option 2'], $group->options);
        $this->assertEquals(2, $group->section_id);
        $this->assertNotEquals('Group 1', $group->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $group->options);
        $this->assertNotEquals(1, $group->section_id);
    }

    /** @test */
    public function can_update_group_with_group_slug()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\User');

        $group = $interrogator->updateGroup($group, 'Group 2', $section2->slug, ['option_1' => 'Option 2']);
        $this->assertEquals('Group 2', $group->name);
        $this->assertEquals(['option_1' => 'Option 2'], $group->options);
        $this->assertEquals(2, $group->section_id);
        $this->assertNotEquals('Group 1', $group->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $group->options);
        $this->assertNotEquals(1, $group->section_id);
    }

    /** @test */
    public function can_delete_group()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);

        $interrogator->deleteGroup($group);
        $this->assertTrue(Group::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_delete_group_with_group_id()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);

        $interrogator->deleteGroup($group->id);
        $this->assertTrue(Group::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_delete_group_with_group_slug()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);

        $interrogator->deleteGroup($group->slug);
        $this->assertTrue(Group::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_create_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $question = $interrogator->createQuestion('Question 1', 1, $group, ['option_1' => 'Option 1']);

        $this->assertEquals('Question 1', $question->name);
        $this->assertEquals(['option_1' => 'Option 1'], $question->options);
        $this->assertEquals(1, $question->group_id);
        $this->assertEquals(1, $question->question_type_id);
    }

    /** @test */
    public function can_update_question()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);
        $group2 = $interrogator->createGroup('Group 2', $question->group->section['id']);

        $question = $interrogator->updateQuestion($question, 'Question 2', $group2, ['option_1' => 'Option 2']);
        $this->assertEquals('Question 2', $question->name);
        $this->assertEquals(['option_1' => 'Option 2'], $question->options);
        $this->assertEquals(2, $question->group_id);
        $this->assertEquals(1, $question->question_type_id);
        $this->assertNotEquals('Question 1', $question->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $question->options);
        $this->assertNotEquals(1, $question->group_id);
    }

    /** @test */
    public function can_update_question_with_question_id()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);
        $group2 = $interrogator->createGroup('Group 2', $question->group->section['id']);

        $question = $interrogator->updateQuestion($question->id, 'Question 2', $group2, ['option_1' => 'Option 2']);
        $this->assertEquals('Question 2', $question->name);
        $this->assertEquals(['option_1' => 'Option 2'], $question->options);
        $this->assertEquals(2, $question->group_id);
        $this->assertEquals(1, $question->question_type_id);
        $this->assertNotEquals('Question 1', $question->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $question->options);
        $this->assertNotEquals(1, $question->group_id);
    }

    /** @test */
    public function can_update_question_with_question_slug()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);
        $group2 = $interrogator->createGroup('Group 2', $question->group->section['id']);

        $question = $interrogator->updateQuestion($question->slug, 'Question 2', $group2, ['option_1' => 'Option 2']);
        $this->assertEquals('Question 2', $question->name);
        $this->assertEquals(['option_1' => 'Option 2'], $question->options);
        $this->assertEquals(2, $question->group_id);
        $this->assertEquals(1, $question->question_type_id);
        $this->assertNotEquals('Question 1', $question->name);
        $this->assertNotEquals(['option_1' => 'Option 1'], $question->options);
        $this->assertNotEquals(1, $question->group_id);
    }

    /** @test */
    public function can_delete_question()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);

        $interrogator->deleteQuestion($question);
        $this->assertTrue(Question::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_delete_question_with_question_id()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);

        $interrogator->deleteQuestion($question->id);
        $this->assertTrue(Question::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_delete_question_with_question_slug()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);

        $interrogator->deleteQuestion($question->slug);
        $this->assertTrue(Question::withTrashed()->first()->trashed());
    }

    /** @test */
    public function can_create_multiple_choice_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $choices = ['option_1' => 'Option 1', 'option_2' => 'Option 2'];
        $question = $interrogator->createMultipleChoiceQuestion('Question 1', $group, $choices);
        $this->assertEquals('Question 1', $question->name);
        $this->assertEquals(['option_1' => 'Option 1', 'option_2' => 'Option 2'], $question->choices);
        $this->assertFalse($question->allowsMultipleChoiceOther());

        $question = $interrogator->createMultipleChoiceQuestion('Question 2', $group->id, $choices);
        $this->assertEquals('Question 2', $question->name);
        $this->assertEquals(['option_1' => 'Option 1', 'option_2' => 'Option 2'], $question->choices);
        $this->assertFalse($question->allowsMultipleChoiceOther());

        $question = $interrogator->createMultipleChoiceQuestion('Question 3', $group->slug, $choices);
        $this->assertEquals('Question 3', $question->name);
        $this->assertEquals(['option_1' => 'Option 1', 'option_2' => 'Option 2'], $question->choices);
        $this->assertFalse($question->allowsMultipleChoiceOther());
    }

    /** @test */
    public function can_create_multiple_choice_question_with_other_option()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $choices = ['Option 1', 'Option 2'];
        $question = $interrogator->createMultipleChoiceQuestion('Question 1', $group, $choices, true);
        $this->assertEquals('Question 1', $question->name);
        $this->assertContains('Option 1', $question->choices);
        $this->assertTrue($question->allowsMultipleChoiceOther());
    }

    /** @test */
    public function can_add_multiple_choice_option_to_existing_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $choices = ['Option 1','Option 2'];
        $question = $interrogator->createMultipleChoiceQuestion('Question 1', $group, $choices, true);
        $question = $question->addMultipleChoiceOption('Option 3');

        $this->assertContains('Option 1', $question->choices);
        $this->assertContains('Option 2', $question->choices);
        $this->assertContains('Option 3', $question->choices);
    }

    /** @test */
    public function can_add_multiple_choice_options_to_existing_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $choices = ['Option 1','Option 2'];
        $question = $interrogator->createMultipleChoiceQuestion('Question 1', $group, $choices, true);
        $question = $question->addMultipleChoiceOption(['Option 3', 'Option 4']);

        $this->assertContains('Option 1', $question->choices);
        $this->assertContains('Option 2', $question->choices);
        $this->assertContains('Option 3', $question->choices);
        $this->assertContains('Option 4', $question->choices);
    }

    /** @test */
    public function can_allow_other_option_after_question_created()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $choices = ['Option 1','Option 2'];
        $question = $interrogator->createMultipleChoiceQuestion('Question 1', $group, $choices);
        $this->assertFalse($question->allowsMultipleChoiceOther());
        $question = $question->setAllowsMultipleChoiceOtherOption();
        $this->assertTrue($question->allowsMultipleChoiceOther());
    }

    /** @test */
    public function can_create_small_text_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $question = $interrogator->createSmallTextQuestion('Question 1', $group);
        $this->assertEquals('Question 1', $question->name);
        $this->assertEquals('small_text', $question->type['slug']);
    }

    /** @test */
    public function can_create_large_text_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $question = $interrogator->createLargeTextQuestion('Question 1', $group);
        $this->assertEquals('Question 1', $question->name);
        $this->assertEquals('large_text', $question->type['slug']);
    }

    /** @test */
    public function can_create_numeric_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $question = $interrogator->createNumericQuestion('Question 1', $group);
        $this->assertEquals('Question 1', $question->name);
        $this->assertEquals('numeric', $question->type['slug']);
    }

    /** @test */
    public function can_create_date_and_time_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $question = $interrogator->createDateTimeQuestion('Question 1', $group);
        $this->assertEquals('Question 1', $question->name);
        $this->assertEquals('date_time', $question->type['slug']);
    }

    /** @test */
    public function can_create_file_upload_question()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $question = $interrogator->createFileUploadQuestion('Question 1', $group);
        $this->assertEquals('Question 1', $question->name);
        $this->assertEquals('file_upload', $question->type['slug']);
    }

    /** @test */
    public function user_can_see_question_when_section_has_user_class_name()
    {
        $interrogator = new Interrogator();
        $this->createTestQuestion($interrogator);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com'
        ]);

        $section = Section::where('class_name', get_class($user))->first();

        $this->assertNotEmpty($section);
    }

    /** @test */
    public function user_can_answer_a_question()
    {
        $interrogator = new Interrogator();
        $this->createTestQuestion($interrogator);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
        ]);

        $section = Section::where('class_name', get_class($user))->first();

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer');
            }
        }

        $question = Question::first();
        $answer = $user->getAnswerFromQuestion($question);
        $this->assertEquals('Test Answer', $answer->value);
    }

    /** @test */
    public function user_and_client_can_answer_a_question()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $question1 = $interrogator->createQuestion('Question 1', 1, $group);
        $question2 = $interrogator->createQuestion('Question 2', 1, $group2);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
        ]);
        $client = Client::create([
            'name' => 'Carly Client',
            'email' => 'cclient@metricloop.com',
        ]);

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'User Test Answer');
            }
        }

        foreach($section2->groups as $group) {
            foreach($group->questions as $question) {
                $client->answerQuestion($question, 'Client Test Answer');
            }
        }

        $answer = $user->getAnswerFromQuestion($question1);
        $this->assertEquals('User Test Answer', $answer->value);
        $this->assertNotEquals('Client Test Answer', $answer->value);

        $answer = $client->getAnswerFromQuestion($question2);
        $this->assertEquals('Client Test Answer', $answer->value);
        $this->assertNotEquals('User Test Answer', $answer->value);
    }

    /** @test */
    public function can_hide_section_from_user()
    {
        $interrogator = new Interrogator();
        $this->createTestQuestion($interrogator);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
        ]);

        $section = Section::where('class_name', get_class($user))->first();

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer');
            }
        }
        
        $interrogator->detachSection($section);

        $question = Question::first();
        $answer = $user->getAnswerFromQuestion($question);
        $this->assertNull($answer);
    }

    /** @test */
    public function can_copy_question()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);
        $interrogator->copyQuestion($question, $question->group);

        $sections = Section::all();
        $this->assertCount(1, $sections);
        $groups = Group::all();
        $this->assertCount(1, $groups);
        $questions = Question::all();
        $this->assertCount(2, $questions);
    }

    /** @test */
    public function can_copy_group()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);
        $interrogator->copyGroup($question->group, $question->group->section);

        $sections = Section::all();
        $this->assertCount(1, $sections);
        $groups = Group::all();
        $this->assertCount(2, $groups);
        $questions = Question::all();
        $this->assertCount(2, $questions);
    }

    /** @test */
    public function can_copy_section()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);
        $interrogator->copySection($question->group->section, 'MetricLoop\Interrogator\Client');

        $sections = Section::all();
        $this->assertCount(2, $sections);
        $groups = Group::all();
        $this->assertCount(2, $groups);
        $questions = Question::all();
        $this->assertCount(2, $questions);
    }

    /** @test */
    public function can_update_answer()
    {
        $interrogator = new Interrogator();
        $this->createTestQuestion($interrogator);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
        ]);

        $section = Section::where('class_name', get_class($user))->first();

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer');
            }
        }

        $question = Question::first();
        $answer = $user->getAnswerFromQuestion($question);
        $this->assertEquals('Test Answer', $answer->value);

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer 2');
            }
        }
        $question = Question::first();
        $answer = $user->getAnswerFromQuestion($question);
        $this->assertEquals('Test Answer 2', $answer->value);

        $answers = Answer::all();
        $this->assertCount(1, $answers);
    }

    /** @test */
    public function can_delete_and_cascade_sections_groups_and_questions()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $group3 = $interrogator->createGroup('Group 2', $section2);
        $question = $interrogator->createQuestion('Question 1', 1, $group);
        $interrogator->createQuestion('Question 2', 2, $group);
        $interrogator->createQuestion('Question 3', 3, $group);
        $interrogator->createQuestion('Question 4', 4, $group);
        $interrogator->createQuestion('Question 5', 5, $group);
        $interrogator->createQuestion('Question 6', 6, $group);
        $interrogator->createQuestion('Question 1', 1, $group2);
        $interrogator->createQuestion('Question 2', 2, $group2);
        $interrogator->createQuestion('Question 3', 3, $group2);
        $interrogator->createQuestion('Question 4', 4, $group2);
        $interrogator->createQuestion('Question 5', 5, $group2);
        $interrogator->createQuestion('Question 6', 6, $group2);
        $interrogator->createQuestion('Question 1', 1, $group3);
        $interrogator->createQuestion('Question 2', 2, $group3);
        $interrogator->createQuestion('Question 3', 3, $group3);
        $interrogator->createQuestion('Question 4', 4, $group3);
        $interrogator->createQuestion('Question 5', 5, $group3);
        $interrogator->createQuestion('Question 6', 6, $group3);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
        ]);
        $client = Client::create([
            'name' => 'Carly Client',
            'email' => 'cclient@metricloop.com'
        ]);

        $section = Section::where('class_name', get_class($user))->first();
        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer');
            }
        }
        $section = Section::where('class_name', get_class($client))->first();
        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $client->answerQuestion($question, 'Test Answer');
            }
        }

        $questions = Question::all();
        $groups = Group::all();
        $sections = Section::all();
        $answers = Answer::all();
        $this->assertCount(18, $questions);
        $this->assertCount(18, $answers);
        $this->assertCount(3, $groups);
        $this->assertCount(2, $sections);

        $interrogator->deleteQuestion($question);
        $questions = Question::all();
        $groups = Group::all();
        $sections = Section::all();
        $answers = Answer::all();
        $this->assertCount(17, $questions);
        $this->assertCount(17, $answers);
        $this->assertCount(3, $groups);
        $this->assertCount(2, $sections);

        $interrogator->deleteGroup($group2);
        $questions = Question::all();
        $groups = Group::all();
        $sections = Section::all();
        $answers = Answer::all();
        $this->assertCount(11, $questions);
        $this->assertCount(11, $answers);
        $this->assertCount(2, $groups);
        $this->assertCount(2, $sections);

        $interrogator->deleteSection($section);
        $questions = Question::all();
        $groups = Group::all();
        $sections = Section::all();
        $answers = Answer::all();
        $this->assertCount(6, $questions);
        $this->assertCount(6, $answers);
        $this->assertCount(1, $groups);
        $this->assertCount(1, $sections);
    }

    public function test_can_update_a_single_option_on_section()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);
        $section = $interrogator->updateSection($section, null, ['option_1' => 'Option 1', 'option_2' => 'Option 2']);

        $section = $interrogator->setOptionOnSection($section, 'option_1', 'Option 2');
        $this->assertEquals(['option_1' => 'Option 2', 'option_2' => 'Option 2'], $section->options);
    }

    public function test_can_update_a_single_option_on_group()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $group = $interrogator->updateGroup($group, null, null, ['option_1' => 'Option 1', 'option_2' => 'Option 2']);

        $group = $interrogator->setOptionOnGroup($group, 'option_1', 'Option 2');
        $this->assertEquals(['option_1' => 'Option 2', 'option_2' => 'Option 2'], $group->options);
    }

    public function test_can_update_a_single_option_on_question()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);
        $question = $interrogator->updateQuestion($question, null, null, ['option_1' => 'Option 1', 'option_2' => 'Option 2']);

        $question = $interrogator->setOptionOnQuestion($question, 'option_1', 'Option 2');
        $this->assertEquals(['option_1' => 'Option 2', 'option_2' => 'Option 2'], $question->options);
    }

    public function test_can_remove_a_single_option_on_section()
    {
        $interrogator = new Interrogator();
        $section = $this->createTestSection($interrogator);
        $section = $interrogator->updateSection($section, null, ['option_1' => 'Option 1', 'option_2' => 'Option 2']);

        $section = $interrogator->unsetOptionOnSection($section, 'option_1');
        $this->assertEquals(['option_2' => 'Option 2'], $section->options);
    }

    public function test_can_remove_a_single_option_on_group()
    {
        $interrogator = new Interrogator();
        $group = $this->createTestGroup($interrogator);
        $group = $interrogator->updateGroup($group, null, null, ['option_1' => 'Option 1', 'option_2' => 'Option 2']);

        $group = $interrogator->unsetOptionOnGroup($group, 'option_1');
        $this->assertEquals(['option_2' => 'Option 2'], $group->options);
    }

    public function test_can_remove_a_single_option_on_question()
    {
        $interrogator = new Interrogator();
        $question = $this->createTestQuestion($interrogator);
        $question = $interrogator->updateQuestion($question, null, null, ['option_1' => 'Option 1', 'option_2' => 'Option 2']);

        $question = $interrogator->unsetOptionOnQuestion($question, 'option_1');
        $this->assertEquals(['option_2' => 'Option 2'], $question->options);
    }
    
}

class User extends Eloquent
{
    use GetsInterrogated;
}

class Client extends Eloquent
{
    use GetsInterrogated;
}