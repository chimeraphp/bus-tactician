<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician;

use League\Tactician\Exception\MissingHandlerException;
use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Middleware;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function assert;
use function get_class;
use function method_exists;

final class CommandHandler implements Middleware, HandlerLocator
{
    private ContainerInterface $container;

    /** @var array<string, array{service: string, method: string}> */
    private array $handlers;

    /** @param array<string, array{service: string, method: string}> $handlers */
    public function __construct(ContainerInterface $container, array $handlers)
    {
        $this->container = $container;
        $this->handlers  = $handlers;
    }

    /** @inheritdoc  */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function execute($command, callable $next)
    {
        $className = get_class($command);

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

        return $this->container->get($this->handlers[$commandName]['service']);
    }

    private function getMethodToCall(string $commandName): string
    {
        assert(array_key_exists($commandName, $this->handlers));

        return $this->handlers[$commandName]['method'];
    }
}
