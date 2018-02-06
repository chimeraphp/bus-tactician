<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Bus\Tactician;

use Lcobucci\Chimera\MessageCreator;
use Lcobucci\Chimera\QueryBus as QueryBusInterface;
use League\Tactician\CommandBus as ServiceBus;
use Psr\Http\Message\ServerRequestInterface;

final class QueryBus implements QueryBusInterface
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

    /**
     * @return mixed
     */
    public function handle(string $query, ServerRequestInterface $request)
    {
        return $this->serviceBus->handle(
            $this->messageCreator->create($query, $request)
        );
    }
}
