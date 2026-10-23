<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayReduceRector;
use RectorLaravel\Rector\Class_\AnonymousMigrationsRector;
use RectorLaravel\Rector\Class_\FillablePropertyToFillableAttributeRector;
use RectorLaravel\Rector\FuncCall\AppToResolveRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets(php83: true)
    // The upgrade rules for the installed Laravel version.
    ->withComposerBased(laravel: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSkip([
        // The tests instantiate the migrations by their class name.
        AnonymousMigrationsRector::class => [__DIR__.'/tests/Migrations'],
        // #[Fillable] merges with $fillable, so apps extending our models couldn't narrow it anymore.
        FillablePropertyToFillableAttributeRector::class => [__DIR__.'/src/Models'],
        // app() and resolve() are the same; keep the usual app().
        AppToResolveRector::class,
        // It types the carry from the elements, ignoring the initial value: array_reduce($files, fn (string $carry, ...))
        // crashed on the first call with the default null carry.
        AddClosureParamTypeForArrayReduceRector::class,
    ]);
