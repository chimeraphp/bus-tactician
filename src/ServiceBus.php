<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician;

use Chimera\ServiceBus as ServiceBusInterface;
use League\Tactician\CommandBus;

final class ServiceBus implements ServiceBusInterface
{
    private CommandBus $bus;

    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    public function handle(object $message): mixed
    {
        return $this->bus->handle($message);
    }
}
