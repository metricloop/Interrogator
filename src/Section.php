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
     * Accessor for attribute.
     *
     * @return int
     */
    public function getOrderAttribute()
    {
        return isset($this->options['order']) ? $this->options['order'] : 1;
    }
}