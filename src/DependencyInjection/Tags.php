<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Bus\Tactician\DependencyInjection;

interface Tags
{
    public const MIDDLEWARE = 'chimera.bus_middleware';
    public const HANDLER    = 'chimera.bus_handler';
}
