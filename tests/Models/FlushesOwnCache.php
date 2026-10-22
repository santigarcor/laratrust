<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

/**
 * Mimics third party traits (e.g. watson/rememberable) that also
 * define a flushCache() method, see #706.
 */
trait FlushesOwnCache
{
    public bool $ownCacheFlushed = false;

    public function flushCache(): void
    {
        $this->ownCacheFlushed = true;
    }
}
