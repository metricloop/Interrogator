<?php namespace MetricLoop\Interrogator\Facades;

class Interrogator extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'interrogator';
    }
}