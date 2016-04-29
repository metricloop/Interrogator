![](interrogator.jpg)

# Interrogator

A simple way to associate Questions and Answers to Eloquent models using Laravel 5.2+

# Installation

Add this to your composer.json:

```
"require": {
    "metricloop/interrogator": "0.1.*",
},
```

Note: Since this is a pre-1.0 release, I would leave it as `0.1.*` because the package is young and might go in different
directions with unforeseen BCs.

And run `composer update` or `composer install`. Or do `composer require metricloop/interrogator`. 

Then add this to your `config/app.php` providers array:

```
providers = [
    MetricLoop\Interrogator\InterrogatorServiceProvider::class,
]
```

The `Interrogator` Facade will be installed automatically within the Service Provider.

# Configuration

Do this.
`php artisan vendor:publish --provider="MetricLoop\Interrogator\InterrogatorServiceProvider"`

It'll publish the `config/interrogator.php` file. More importantly, it will copy the 
migrations file into your migrations directory.

## Database

Next, you need to run the migrations.
`php artisan migrate`

## Eloquent Models

Next, add the `GetsInterrogated` trait to whichever Models you want to have Q&A support. This will add the functions and 
relationships to make it work. You also need to add each Model to the `config/interrogator.php` file, like so:

```
'classes' => [
    'user' => [
        'name' => 'User',
        'class' => 'App\User'
    ],
],
```

At some point you'll need to do `composer dump-autoload` to make sure everything sticks.

# Usage

Here's how it works. Every `Question` belongs to a `Group` which belongs to a `Section`. Each Section is associated to a 
namespaced class name of the corresponding Eloquent model.

## Basic Usage
Let's say you wanted to create a Q&A to collect some information on your Users. You're interested in their date of 
birth.

First, let's create a `Section` called Overview and a `Group` called Personal. Then, we'll create the Date of Birth 
`Question`.
 
```
$section  = Interrogator::createSection('Overview', [], 'App\User');
$group    = Interrogator::createGroup('Personal', $section);
$question = Interrogator::createQuestion('Date of Birth', 'date_time', $group);
```

### Answering Questions
Now all you have to do is call the `answerQuestion()` on the `User` model and give it the value, like so: 

```
$user->answerQuestion($question, '1989-09-10');
```

The `GetsInterrogated` trait will determine if you're creating a new Answer, or if you're updating an existing Answer. 
Also note that `$question` in this function call can be either the full `Question` object, the `id`, or the `slug` of 
the question.

### Retrieving Answers
Now you want to retrieve the Answer and do something with it, yeah? Display it, compare it against something, whatever.
That's super easy, too.

```
$answer = $user->getAnswerFromQuestion($question);
```

Again, `$question` can be the full `Question` object, the `id`, or the unique `slug`. 

## Searching (Detective Mode)
So you've interrogated someone (or something!) and now you want to recall those answers. You know the general answer, 
but you can't remember who said it. Enter the Detective.

### Exact Match
If you know beyond a shadow of a doubt the *exact* answer you're looking for, do this:
```
$results = Interrogator::searchExact('foo');
```
You could also do this if you know the fully qualified `class_name` or the `question_ids` of what you're searching for.
```
$results = Interrogator::searchExact('foo', 'App\User');
$results = Interrogator::searchExact('foo', null, [1,2,3]);
$results = Interrogator::searchExact('foo', 'App\User', [1,2,3]);
```

### More Realistic Use Case
This is more likely what you'll be doing:
```
$results = Interrogator::search('foo');
$results = Interrogator::search('foo', 'App\User');
$results = Interrogator::search('foo', 'App\User', [1,2,3]);
$results = Interrogator::search('foo', null, [1,2,3]);
```
The normal `search()` function wraps SQL wildcards (`%`) around the search term and uses the `LIKE` operator. Speaking 
of wildcards, both `search()` and `searchExact()` accept normal "human" wildcards like `*` and `?`. The asterisk will be
replaced with `%` (multiple characters) and the question mark will be replaced with `_` (single character) before 
conducting the Search.

Or you know the Question and you want to find all the Answers that match certain criteria, where `$question` can be the
model, the `id`, or the unique `slug`:
```
$results = Interrogator::searchQuestion('foo', 'App\User', $question);
```

Maybe all you know is the *type* of the Question (and the `class_name`, maybe.):
```
$results = Interrogator::searchSmallText('foo', 'App\User');
$results = Interrogator::searchLargeText('foo', 'App\User');
$results = Interrogator::searchNumeric('foo', 'App\User');
$results = Interrogator::searchDateTime('foo', 'App\User');
$results = Interrogator::searchMultipleChoice('foo', 'App\User');
$results = Interrogator::searchFileUpload('foo', 'App\User');
```

Oh and of course all of these accept the aforementioned wildcard replacement. ;)

### Multi Tenancy
You *might* need multi-tenancy, or you might not. Right now, this supports multi-tenancy with Laravel Spark by adding
`team_id` to every model. You'll need to add the `team_id` to your requests (usually the last parameter) so that 
Interrogator can scope accordingly. Otherwise, `team_id` will stay `null` and your query scope will be 
`->where('team_id', null)`. 

Please feel free to contribute and expand our single- and multi-tenant ability!

# Contributing

Thanks for wanting to contribute! We take this package seriously and want to maintain its integrity. We will look at all
suggestions and PRs but will ultimately have the last say in what gets merged or not. 

# License

[MIT](LICENSE.md)