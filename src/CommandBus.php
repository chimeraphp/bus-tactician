<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Bus\Tactician;

use Lcobucci\Chimera\CommandBus as CommandBusInterface;
use Lcobucci\Chimera\MessageCreator;
use League\Tactician\CommandBus as ServiceBus;
use Psr\Http\Message\ServerRequestInterface;

final class CommandBus implements CommandBusInterface
{
    /**
     * @var ServiceBus
     */
    private $serviceBus;

    /**
     * @var MessageCreator
     */
    private $messageCreator;

    public function __construct(
        ServiceBus $serviceBus,
        MessageCreator $messageCreator
    ) {
        $this->serviceBus     = $serviceBus;
        $this->messageCreator = $messageCreator;
    }

    public function handle(string $command, ServerRequestInterface $request): void
    {
        $this->serviceBus->handle(
            $this->messageCreator->create($command, $request)
        );
    }
}
