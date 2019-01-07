<?php

namespace Tests\Unit\Script;

use DarkGhostHunter\Larapoke\Blade\LarapokeDirective;
use Orchestra\Testbench\TestCase;

class ScriptTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            'DarkGhostHunter\Larapoke\LarapokeServiceProvider'
        ];
    }

    /** @var \Illuminate\Config\Repository & \Mockery\MockInterface */
    protected $mockConfig;

    /** @var \Illuminate\View\Factory & \Mockery\MockInterface */
    protected $mockView;

    protected $sessionLifetime;

    protected $times;

    public function setUp()
    {
        $this->mockConfig = \Mockery::mock(\Illuminate\Config\Repository::class);

        $this->mockView = \Mockery::mock(\Illuminate\View\Factory::class);

        LarapokeDirective::setWasRendered(false);
    }

    public function testSetAndGetWasRendered()
    {
        $this->assertFalse(LarapokeDirective::getWasRendered());
        LarapokeDirective::setWasRendered(true);
        $this->assertTrue(LarapokeDirective::getWasRendered());
        LarapokeDirective::setWasRendered(false);
        $this->assertFalse(LarapokeDirective::getWasRendered());
    }

    public function testReceivesConfig()
    {
        $this->mockView
            ->shouldReceive('make')
            ->with('larapoke::script', \Mockery::type('array'))
            ->andReturnUsing(function ($script, $config) {
                return (new class ($config)
                {
                    protected $config;

                    public function __construct($config)
                    {
                        $this->config = $config;
                    }

                    public function render()
                    {
                        return $this->config;
                    }
                });
            });

        $this->mockConfig->shouldReceive('get')
            ->with('session.lifetime')
            ->andReturn($this->sessionLifetime = rand(10, 240));

        $this->mockConfig->shouldReceive('get')
            ->with('larapoke.poking.route')
            ->andReturn('test-larapoke-route');

        $this->mockConfig->shouldReceive('get')
            ->with('larapoke.times')
            ->andReturn($this->times = rand(2, 16));

        $this->mockConfig->shouldReceive('get')
            ->with('larapoke.timeout')
            ->andReturn(false);

        $script = (new LarapokeDirective($this->mockConfig, $this->mockView))->getRenderedScript();

        $this->assertEquals($script['route'], 'test-larapoke-route');
        $this->assertEquals($script['interval'], (int)(($this->sessionLifetime * 60) / $this->times));
        $this->assertFalse($script['timeout']);
        $this->assertEquals($script['lifetime'], $this->sessionLifetime * 60);
    }
}