<?php

declare(strict_types=1);

namespace Laratrust\Tests\Middleware;

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Laratrust\Tests\LaratrustTestCase;

abstract class MiddlewareTest extends LaratrustTestCase
{
    protected $request;
    protected $guard;
    const ABORT_MESSAGE = 'User does not have any of the necessary access rights.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrate();
        $this->request = m::mock(Request::class);
        $this->guard = m::mock(Guard::class);
    }
}
