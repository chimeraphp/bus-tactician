<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician;

use League\Tactician\Exception\MissingHandlerException;
use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Middleware;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function assert;
use function is_object;
use function method_exists;

final class CommandHandler implements Middleware, HandlerLocator
{
    /** @param array<class-string, array{service: class-string, method: string}> $handlers */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $handlers,
    ) {
    }

    /** @inheritdoc  */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function execute($command, callable $next)
    {
        $className = $command::class;

        $handler = $this->getHandlerForCommand($className);
        $method  = $this->getMethodToCall($className);
        assert(method_exists($handler, $method));

        return $handler->$method($command); // @phpstan-ignore-line
    }

    /** @inheritdoc */
    public function getHandlerForCommand($commandName): object
    {
        if (! array_key_exists($commandName, $this->handlers)) {
            throw MissingHandlerException::forCommand($commandName);
        }

        $handler = $this->container->get($this->handlers[$commandName]['service']);
        assert(is_object($handler));

        return $handler;
    }

    private function getMethodToCall(string $commandName): string
    {
        assert(array_key_exists($commandName, $this->handlers));

        return $this->handlers[$commandName]['method'];
    }
}
