<?php

declare(strict_types=1);

namespace Laratrust\Panel;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * The role and permission keys submitted by the panel's checklists.
 *
 * @internal
 */
final class SubmittedKeys
{
    /**
     * Validation rules for a list of existing keys of the model.
     *
     * @param class-string<Model> $model
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $field, string $model): array
    {
        $isKey = function (string $attribute, mixed $value, Closure $fail): void {
            if (!is_int($value) && !is_string($value)) {
                $fail('The :attribute field must be a key.');
            }
        };

        return [
            $field       => ['array'],
            "{$field}.*" => ['bail', $isKey, Rule::exists($model, (new $model())->getKeyName())],
        ];
    }

    /**
     * Give the keys as ['id' => $key], so a string key (e.g. a uuid) is never taken for a name.
     *
     * @param array<array-key, int|string> $keys
     *
     * @return list<array{id: int|string}>
     */
    public static function assignable(array $keys): array
    {
        return array_values(array_map(fn (int|string $key): array => ['id' => $key], $keys));
    }
}
