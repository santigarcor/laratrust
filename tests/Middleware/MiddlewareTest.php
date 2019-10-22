<?php

namespace Laratrust\Tests\Middleware;

use Mockery as m;
use Laratrust\Tests\LaratrustTestCase;

abstract class MiddlewareTest extends LaratrustTestCase
{
    protected $request;
    protected $guard;
    const ABORT_MESSAGE = 'User does not have any of the necessary access rights.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = m::mock('Illuminate\Http\Request');
        $this->guard = m::mock('Illuminate\Contracts\Auth\Guard');
    }
}
