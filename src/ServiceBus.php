<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician;

use Chimera\ServiceBus as ServiceBusInterface;
use League\Tactician\CommandBus;

final class ServiceBus implements ServiceBusInterface
{
    /**
     * @var CommandBus
     */
    private $bus;

    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(object $message)
    {
        return $this->bus->handle($message);
    }
}
