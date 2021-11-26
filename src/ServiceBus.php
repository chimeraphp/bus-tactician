<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician;

use Chimera\ServiceBus as ServiceBusInterface;
use League\Tactician\CommandBus;

final class ServiceBus implements ServiceBusInterface
{
    public function __construct(private CommandBus $bus)
    {
    }

    public function handle(object $message): mixed
    {
        return $this->bus->handle($message);
    }
}
