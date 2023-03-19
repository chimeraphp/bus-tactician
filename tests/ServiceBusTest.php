<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

use Chimera\ServiceBus\Tactician\ServiceBus;
use League\Tactician\CommandBus;
use League\Tactician\Middleware;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\TestCase;

use function assert;

#[PHPUnit\CoversClass(ServiceBus::class)]
final class ServiceBusTest extends TestCase
{
    private CommandBus $tacticianBus;

    #[PHPUnit\Before]
    public function createBus(): void
    {
        $middleware = new class implements Middleware
        {
            // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
            public function execute(mixed $command, callable $next): mixed
            {
                assert($command instanceof FetchById);

                return 'Everything good';
            }
        };

        $this->tacticianBus = new CommandBus([$middleware]);
    }

    #[PHPUnit\Test]
    public function handleShouldProcessTheMessageUsingTheDecoratedServiceAndReturnTheResult(): void
    {
        $bus = new ServiceBus($this->tacticianBus);

        self::assertSame('Everything good', $bus->handle(new FetchById(1)));
    }
}
