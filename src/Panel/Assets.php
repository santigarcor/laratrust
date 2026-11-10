<?php

declare(strict_types=1);

namespace Laratrust\Panel;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use RuntimeException;

/**
 * The panel's compiled CSS and JS, inlined in the page: there's nothing to publish,
 * and they can't get out of date after an update. Built with "npm run panel:build".
 *
 * @internal
 */
final class Assets
{
    /** @var array<string, string> */
    private static array $contents = [];

    public static function css(): HtmlString
    {
        return self::tag('style', 'laratrust.css');
    }

    public static function js(): HtmlString
    {
        return self::tag('script', 'laratrust.js');
    }

    private static function tag(string $tag, string $file): HtmlString
    {
        // The application's CSP nonce, set with Vite::useCspNonce().
        $nonce = Vite::cspNonce();
        $attributes = $nonce === null ? '' : ' nonce="'.e($nonce).'"';

        return new HtmlString("<{$tag}{$attributes}>".self::contents($file)."</{$tag}>");
    }

    private static function contents(string $file): string
    {
        if (isset(self::$contents[$file])) {
            return self::$contents[$file];
        }

        $contents = @file_get_contents(__DIR__.'/../../dist/'.$file);

        if ($contents === false) {
            throw new RuntimeException("The Laratrust panel asset dist/{$file} is missing. Run \"npm run panel:build\".");
        }

        return self::$contents[$file] = $contents;
    }
}
