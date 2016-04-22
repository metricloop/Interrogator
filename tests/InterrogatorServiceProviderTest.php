<?php
namespace MetricLoop\Interrogator;

/**
 * Function Mock.
 * 
 * @param null $path
 * @return string
 */
function config_path( $path = null )
{
    return 'test_config_path/' . $path ;
}

/**
 * Function Mock.
 * 
 * @param null $path
 * @return string
 */
function database_path( $path = null )
{
    return 'test_database_path/' . $path ;
}

/**
 * Function Mock.
 * 
 * @param $path
 * @return array
 */
function glob( $path )
{
    return TeamworkServiceProviderTest::$globResult;
}

/**
 * Begin Test.
 */

use Mockery as m;
use PHPUnit_Framework_TestCase;

class TeamworkServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public static $globResult = [];

    /**
     * Test clean up.
     */
    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function can_boot()
    {
        $sp = m::mock('MetricLoop\Interrogator\InterrogatorServiceProvider[publishConfig,publishMigration]',['app']);
        $sp->shouldAllowMockingProtectedMethods();
        $sp->shouldReceive('publishConfig')
            ->once()
            ->withNoArgs();
        $sp->shouldReceive('publishMigration')
            ->once()
            ->withNoArgs();
        $sp->boot();
    }

    /** @test */
    public function can_publish_config()
    {
        $test = $this;
        $sp = m::mock('MetricLoop\Interrogator\InterrogatorServiceProvider[publishes]',['app'])
            ->shouldAllowMockingProtectedMethods()
            ->shouldDeferMissing();
        $sp->shouldReceive('publishes')
            ->once()
            ->with( m::type('array') )
            ->andReturnUsing(function ($array) use ($test) {
                $test->assertContains('test_config_path/interrogator.php', $array);
            });
        $sp->publishConfig();
    }

    /** @test */
    public function can_publish_migration_once()
    {
        $test = $this;
        self::$globResult = [];
        $sp = m::mock('MetricLoop\Interrogator\InterrogatorServiceProvider[publishes]',['app'])
            ->shouldAllowMockingProtectedMethods()
            ->shouldDeferMissing();
        $sp->shouldReceive('publishes')
            ->once()
            ->with( m::type('array'), 'migrations' )
            ->andReturnUsing(function ($array) use ($test) {
                $values = array_values( $array);
                $target = array_pop( $values );
                $test->assertContains('_interrogator_tables.php', $target);
            });
        $sp->publishMigration();
    }

    /** @test */
    public function can_not_publish_migration_when_it_already_exists()
    {
        self::$globResult = [1,2,3];
        $sp = m::mock('MetricLoop\Interrogator\InterrogatorServiceProvider[publishes]',['app'])
            ->shouldAllowMockingProtectedMethods()
            ->shouldDeferMissing();
        $sp->shouldReceive('publishes')
            ->never();
        $sp->publishMigration();
    }

    /** @test */
    public function can_register()
    {
        $sp = m::mock('MetricLoop\Interrogator\InterrogatorServiceProvider[mergeConfig,registerInterrogator,registerFacade]',['app']);
        $sp->shouldAllowMockingProtectedMethods();
        $sp->shouldReceive('mergeConfig')
            ->once()
            ->withNoArgs();
        $sp->shouldReceive('registerInterrogator')
            ->once()
            ->withNoArgs();
        $sp->shouldReceive('registerFacade')
            ->once()
            ->withNoArgs();
        $sp->register();
    }

    /** @test */
    public function can_register_interrogator()
    {
        $test = $this;
        $app = m::mock('App');
        $sp = m::mock('MetricLoop\Interrogator\InterrogatorServiceProvider', [$app]);
        $app->shouldReceive('bind')
            ->once()->andReturnUsing(
                function ($name, $closure) use ($test, $app) {
                    $test->assertEquals('interrogator', $name);
                    $test->assertInstanceOf(
                        'MetricLoop\Interrogator\Interrogator',
                        $closure($app)
                    );
                }
            );
        $sp->registerInterrogator();
    }

    /** @test */
    public function can_register_facade()
    {
        $app = m::mock('App');
        $app->shouldReceive('booting')
            ->once()
            ->with( m::type('callable') );
        $sp = m::mock('MetricLoop\Interrogator\InterrogatorServiceProvider',[$app])
            ->shouldDeferMissing();
        $sp->registerFacade();
    }

    /** @test */
    public function should_merge_config()
    {
        $sp = m::mock('MetricLoop\Interrogator\InterrogatorServiceProvider',['app'])
            ->shouldDeferMissing()
            ->shouldAllowMockingProtectedMethods();
        $sp->shouldReceive('mergeConfigFrom')
            ->once()
            ->with(m::type('string'),'interrogator');
        $sp->mergeConfig();
    }


}