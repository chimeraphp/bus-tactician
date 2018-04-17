<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\ServiceBus\Tactician\Tests;

use Lcobucci\Chimera\ServiceBus\Tactician\CommandBus;
use Psr\Http\Message\ServerRequestInterface;

final class CommandBusTest extends MessageBusTestCase
{
    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\CommandBus
     */
    public function handleShouldCreateTheMessageAndSendItToTheBus(): void
    {
        $this->handle(
            function (FetchById $query): int {
                return $query->id;
            }
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\CommandBus
     */
    public function handleShouldNotCatchAnyException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->handle(
            function (): void {
                throw new \RuntimeException();
            }
        );
    }

    private function handle(callable $handler): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $commandBus = new CommandBus(
            $this->createServiceBus($handler),
            $this->createMessageCreator(FetchById::class, $request, new FetchById(1))
        );

        $commandBus->handle(FetchById::class, $request);
    }
}
