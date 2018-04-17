<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\ServiceBus\Tactician\Tests;

use Lcobucci\Chimera\ServiceBus\Tactician\QueryBus;
use Psr\Http\Message\ServerRequestInterface;

final class QueryBusTest extends MessageBusTestCase
{
    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\QueryBus
     */
    public function handleShouldCreateTheMessageSendItToTheBusAndReturnItsValue(): void
    {
        $callback = function (FetchById $query): int {
            return $query->id;
        };

        self::assertSame(1, $this->handle($callback));
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\QueryBus
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

    /**
     * @return mixed
     */
    private function handle(callable $handler)
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $queryBus = new QueryBus(
            $this->createServiceBus($handler),
            $this->createMessageCreator(FetchById::class, $request, new FetchById(1))
        );

        return $queryBus->handle(FetchById::class, $request);
    }
}
