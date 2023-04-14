<?php

declare(strict_types=1);

namespace Laratrust\Tests\Enums;

enum Permission: string
{
    case PERM_A = 'permission_a';
    case PERM_B = 'permission_b';
    case PERM_C = 'permission_c';
    case PERM_D = 'permission_d';
}
