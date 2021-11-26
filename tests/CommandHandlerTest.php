<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

use Chimera\ServiceBus\Tactician\CommandHandler;
use League\Tactician\Exception\MissingHandlerException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/** @coversDefaultClass \Chimera\ServiceBus\Tactician\CommandHandler */
final class CommandHandlerTest extends TestCase
{
    /**
     * @test
     *
     * @covers ::__construct
     * @covers ::getHandlerForCommand
     */
    public function getHandlerForCommandShouldRaiseExceptionWhenNoHandlerWasRegistered(): void
    {
        $commandHandler = new CommandHandler(
            $this->createMock(ContainerInterface::class),
            [],
        );

        $this->expectExceptionObject(MissingHandlerException::forCommand(FetchById::class));
        $commandHandler->getHandlerForCommand(FetchById::class);
    }

    /**
     * @test
     *
     * @covers ::__construct
     * @covers ::execute
     * @covers ::getHandlerForCommand
     * @covers ::getMethodToCall
     */
    public function executeShouldCallTheRegisteredHandlerMethod(): void
    {
        $handler = new class {
            public function testing(FetchById $query): string
            {
                return 'Here is the item #' . $query->id;
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('handler')->willReturn($handler);

        $commandHandler = new CommandHandler(
            $container,
            [FetchById::class => ['service' => 'handler', 'method' => 'testing']],
        );

        self::assertSame('Here is the item #1', $commandHandler->execute(new FetchById(1), 'is_string'));
    }
}
