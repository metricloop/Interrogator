<?php

namespace MetricLoop\Interrogator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
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
        'class_name',
        'options',
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
        $this->table = 'sections';
    }

    /**
     * Returns the Groups that belong to this Section.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Delete Groups before deleting Section itself.
     *
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        $this->groups->each(function ($group) {
            $group->delete();
        });
        return parent::delete();
    }

    /**
     * Updates the Options for the Section.
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