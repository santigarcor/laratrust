<?php
namespace Laratrust\Rules;
use Illuminate\Http\Request;
use Laratrust\RuleInterface;

/**
 * User: liuchunhua
 * Datetime: 2018-04-28 15:38
 */

class DemoRule implements RuleInterface
{
    public function handle($userId, Request $request)
    {
        if ($userId <= 0) {
            return true;
        }

        if ($request->get('id') <= 0) {
            return true;
        }

        $std = new \stdClass();
        $std->user_id = 1;

        return $userId === $std->user_id;
    }
}