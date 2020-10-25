<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

use Chimera\ServiceBus\Tactician\ServiceBus;
use League\Tactician\CommandBus;
use League\Tactician\Middleware;
use PHPUnit\Framework\TestCase;

use function assert;

/** @coversDefaultClass \Chimera\ServiceBus\Tactician\ServiceBus */
final class ServiceBusTest extends TestCase
{
    private CommandBus $tacticianBus;

    /** @before */
    public function createBus(): void
    {
        $middleware = new class implements Middleware
        {
            /**
             * @param mixed $command
             *
             * @return mixed
             */
            // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
            public function execute($command, callable $next)
            {
                assert($command instanceof FetchById);

                return 'Everything good';
            }
        };

        $this->tacticianBus = new CommandBus([$middleware]);
    }

    /**
     * @test
     *
     * @covers ::__construct()
     * @covers ::handle()
     */
    public function handleShouldProcessTheMessageUsingTheDecoratedServiceAndReturnTheResult(): void
    {
        $bus = new ServiceBus($this->tacticianBus);

        self::assertSame('Everything good', $bus->handle(new FetchById(1)));
    }
}
