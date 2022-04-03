<?php
declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

// All of these are false positives
// @see https://github.com/composer-unused/composer-unused/issues/326

return static fn(Configuration $config): Configuration => $config
    ->addNamedFilter(NamedFilter::fromString('chimera/foundation'))
    ->addNamedFilter(NamedFilter::fromString('league/tactician'))
    ->addNamedFilter(NamedFilter::fromString('psr/container'));
