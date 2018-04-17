<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection;

interface Tags
{
    public const MIDDLEWARE      = 'chimera.bus_middleware';
    public const HANDLER         = 'chimera.bus_handler';
    public const COMMAND_HANDLER = 'chimera.command_handler';
    public const QUERY_HANDLER   = 'chimera.query_handler';
}
