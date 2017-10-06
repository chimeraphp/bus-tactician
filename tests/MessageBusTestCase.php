<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Bus\Tactician\Tests;

use Lcobucci\Chimera\MessageCreator;
use League\Tactician\CommandBus as ServiceBus;
use League\Tactician\Middleware;
use Psr\Http\Message\ServerRequestInterface;

abstract class MessageBusTestCase extends \PHPUnit\Framework\TestCase
{
    protected function createMessageCreator(
        string $message,
        ServerRequestInterface $request,
        $createdObject
    ): MessageCreator {
        $messageCreator = $this->createMock(MessageCreator::class);

        $messageCreator->expects($this->once())
                       ->method('create')
                       ->with($message, $request)
                       ->willReturn($createdObject);

        return $messageCreator;
    }

    protected function createServiceBus(callable $callback): ServiceBus
    {
        return new ServiceBus(
            [
                new class ($callback) implements Middleware
                {
                    /**
                     * @var callable
                     */
                    private $callback;

                    public function __construct(callable $callback)
                    {
                        $this->callback = $callback;
                    }

                    public function execute($command, callable $next)
                    {
                        return ($this->callback)($command);
                    }
                },
            ]
        );
    }
}
