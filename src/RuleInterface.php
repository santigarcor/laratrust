<?php

namespace Laratrust;

use Illuminate\Http\Request;

/**
 * User: liuchunhua
 * Datetime: 2018-04-28 15:33
 */
interface RuleInterface
{
    /**
     * @param integer $userId
     * @param Request $request
     * @return boolean
     */
    public function handle($userId, Request $request);
}