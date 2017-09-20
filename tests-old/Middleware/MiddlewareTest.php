<?php

use Mockery as m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

abstract class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    public static $abortCode = null;
    protected $request;

    public function setUp()
    {
        parent::setUp();

        $app = m::mock('app')->shouldReceive('instance')->getMock();
        $this->request = m::mock('Illuminate\Http\Request');

        Config::setFacadeApplication($app);
        Config::swap(m::mock('config'));
        Auth::swap(m::mock('auth'));
    }

    public static function setupBeforeClass()
    {
        if (! function_exists('abort')) {
            /**
             * Mimicks Laravel5's abort() helper function.
             *
             * Instead of calling \Illuminate\Foundation\Application::abort(),
             * this function keeps track of the last abort called,
             * so the abort can be retrieved for test assertions.
             *
             * @param  int     $code
             * @param  string  $message
             * @param  array   $headers
             * @return void
             */
            function abort($code, $message = '', array $headers = [])
            {
                MiddlewareTest::$abortCode = $code;
            }
        }

        if (! function_exists('redirect')) {
            /**
             * Mimicks Laravel5's redirect() helper function.
             *
             * This function keeps track of the last abort called,
             * so the abort can be retrieved for test assertions.
             *
             * @see https://github.com/laravel/framework/blob/master/src/Illuminate/Foundation/helpers.php
             *
             * @param  string  $to
             * @param  int     $status
             * @param  array   $headers
             * @param  bool    $secure
             * @return void
             */
            function redirect($to = null, $status = 302, $headers = [], $secure = null)
            {
                MiddlewareTest::$abortCode = $url;
            }
        }
    }

    public function tearDown()
    {
        parent::tearDown();

        m::close();

        // Reset the abort code every end of test case,
        // so the result of previous test case does not pollute the next one.
        static::$abortCode = null;
    }

    public function assertAbortCode($code)
    {
        return $this->assertEquals($code, $this->getAbortCode());
    }

    public function assertDidNotAbort()
    {
        return $this->assertEquals(null, $this->getAbortCode());
    }

    public function getAbortCode()
    {
        return static::$abortCode;
    }
}
