<?php

declare(strict_types=1);

namespace Laratrust\Tests\Middleware;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Laratrust\Tests\LaratrustTestCase;
use Mockery as m;

abstract class MiddlewareTest extends LaratrustTestCase
{
    protected $request;

    protected $guard;

    const ABORT_MESSAGE = 'User does not have any of the necessary access rights.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = m::mock(Request::class);
        $this->guard = m::mock(Guard::class);
    }
}
