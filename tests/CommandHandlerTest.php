<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

use Chimera\ServiceBus\Tactician\CommandHandler;
use League\Tactician\Exception\MissingHandlerException;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[PHPUnit\CoversClass(CommandHandler::class)]
final class CommandHandlerTest extends TestCase
{
    #[PHPUnit\Test]
    public function getHandlerForCommandShouldRaiseExceptionWhenNoHandlerWasRegistered(): void
    {
        $commandHandler = new CommandHandler(
            $this->createMock(ContainerInterface::class),
            [],
        );

        $this->expectExceptionObject(MissingHandlerException::forCommand(FetchById::class));
        $commandHandler->getHandlerForCommand(FetchById::class);
    }

    #[PHPUnit\Test]
    public function executeShouldCallTheRegisteredHandlerMethod(): void
    {
        $handler = new class {
            public function testing(FetchById $query): string
            {
                return 'Here is the item #' . $query->id;
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with($handler::class)->willReturn($handler);

        $commandHandler = new CommandHandler(
            $container,
            [FetchById::class => ['service' => $handler::class, 'method' => 'testing']],
        );

        self::assertSame('Here is the item #1', $commandHandler->execute(new FetchById(1), 'is_string'));
    }
}
