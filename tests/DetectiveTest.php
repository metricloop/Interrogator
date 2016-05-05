<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Schema\Blueprint;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DetectiveTest extends PHPUnit_Framework_TestCase
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

    /**
     * -------------------------------------------------------
     * Detective Tests (Search & Query)
     * -------------------------------------------------------
     */

    /** @test */
    public function can_search_for_exact_match()
    {
        $interrogator = new Interrogator();
        $this->createTestQuestion($interrogator);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
            'current_team_id' => 1
        ]);

        $section = Section::where('class_name', get_class($user))->first();

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer');
            }
        }

        $term = 'Test Answer';
        $results = $interrogator->searchExact($term, null, null, 1);
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_fuzzy_match()
    {
        $interrogator = new Interrogator();
        $this->createTestQuestion($interrogator);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
            'current_team_id' => 1,
        ]);

        $section = Section::where('class_name', get_class($user))->first();

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer');
            }
        }

        $term = 'est Answe';
        $results = $interrogator->search($term, null, null, 1);
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_fuzzy_match_with_single_char_wildcard()
    {
        $interrogator = new Interrogator();
        $this->createTestQuestion($interrogator);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
            'current_team_id' => 1,
        ]);

        $section = Section::where('class_name', get_class($user))->first();

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer');
            }
        }

        $term = 'Test ?nswer';
        $results = $interrogator->search($term, null, null, 1);
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_fuzzy_match_with_multi_char_wildcard()
    {
        $interrogator = new Interrogator();
        $this->createTestQuestion($interrogator);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
            'current_team_id' => 1,
        ]);

        $section = Section::where('class_name', get_class($user))->first();

        foreach($section->groups as $group) {
            foreach($group->questions as $question) {
                $user->answerQuestion($question, 'Test Answer');
            }
        }

        $term = 'Test *swer';
        $results = $interrogator->search($term, null, null, 1);
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_exact_match_with_class()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $interrogator->createQuestion('Question 1', 1, $group);
        $interrogator->createQuestion('Question 2', 1, $group2);

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

        $term = 'Test Answer';
        $results = $interrogator->searchExact($term, get_class($user));
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_fuzzy_match_with_class()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $interrogator->createQuestion('Question 1', 1, $group);
        $interrogator->createQuestion('Question 2', 1, $group2);

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

        $term = 'est Answe';
        $results = $interrogator->search($term, get_class($user));
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_small_text_fuzzy_match()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $interrogator->createQuestion('Question 1', 1, $group);
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

        $term = 'est Answe';
        $results = $interrogator->searchSmallText($term, get_class($user));
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_large_text_fuzzy_match()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $interrogator->createQuestion('Question 1', 1, $group);
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

        $term = 'est Answe';
        $results = $interrogator->searchLargeText($term, get_class($user));
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_numeric_fuzzy_match()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $interrogator->createQuestion('Question 1', 1, $group);
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

        $term = 'est Answe';
        $results = $interrogator->searchNumeric($term, get_class($user));
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_date_time_fuzzy_match()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $interrogator->createQuestion('Question 1', 1, $group);
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

        $term = 'est Answe';
        $results = $interrogator->searchDateTime($term, get_class($user));
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_multiple_choice_fuzzy_match()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $interrogator->createQuestion('Question 1', 1, $group);
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

        $term = 'est Answe';
        $results = $interrogator->searchMultipleChoice($term, get_class($user));
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_file_upload_fuzzy_match()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User');
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client');
        $group = $interrogator->createGroup('Group 1', $section);
        $group2 = $interrogator->createGroup('Group 2', $section2);
        $interrogator->createQuestion('Question 1', 1, $group);
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

        $term = 'est Answe';
        $results = $interrogator->searchFileUpload($term, get_class($user));
        $this->assertCount(1, $results);
    }

    /** @test */
    public function can_search_for_specific_question_fuzzy_match()
    {
        $interrogator = new Interrogator();
        $section = $interrogator->createSection('Section 1', [], 'MetricLoop\Interrogator\User', 1);
        $section2 = $interrogator->createSection('Section 2', [], 'MetricLoop\Interrogator\Client', 1);
        $group = $interrogator->createGroup('Group 1', $section, [], 1);
        $group2 = $interrogator->createGroup('Group 2', $section2, [], 1);
        $interrogator->createQuestion('Question 1', 1, $group, [], [], 1);
        $interrogator->createQuestion('Question 2', 2, $group, [], [], 1);
        $interrogator->createQuestion('Question 3', 3, $group, [], [], 1);
        $interrogator->createQuestion('Question 4', 4, $group, [], [], 1);
        $interrogator->createQuestion('Question 5', 5, $group, [], [], 1);
        $interrogator->createQuestion('Question 6', 6, $group, [], [], 1);
        $interrogator->createQuestion('Question 1', 1, $group2, [], [], 1);
        $interrogator->createQuestion('Question 2', 2, $group2, [], [], 1);
        $interrogator->createQuestion('Question 3', 3, $group2, [], [], 1);
        $interrogator->createQuestion('Question 4', 4, $group2, [], [], 1);
        $interrogator->createQuestion('Question 5', 5, $group2, [], [], 1);
        $interrogator->createQuestion('Question 6', 6, $group2, [], [], 1);

        $user = User::create([
            'name' => 'Ulysses User',
            'email' => 'uuser@metricloop.com',
            'current_team_id' => 1,
        ]);
        $client = Client::create([
            'name' => 'Carly Client',
            'email' => 'cclient@metricloop.com',
            'current_team_id' => 1,
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

        $term = 'est Answe';
        $question = Question::first();
        $results = $interrogator->searchQuestion($term, get_class($user), $question, 1);
        $this->assertCount(1, $results);
    }

}