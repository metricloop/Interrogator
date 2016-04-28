<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
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
        'options',
        'section_id',
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
    ];

    /**
     * Creates a new instance of the model.
     *
     * @param array $attributes
     */
    public function __construct( array $attributes = [ ] )
    {
        parent::__construct( $attributes );
        $this->table = 'groups';
    }

    /**
     * Returns the Section to which this Group belongs.
     *
     * @return BelongsTo
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get all the Questions that belong to this Group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Delete Questions before deleting Group itself.
     *
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        $this->questions->each(function ($question) {
            $question->delete();
        });
        return parent::delete();
    }

    /**
     * Updates the Options for the Group.
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
}