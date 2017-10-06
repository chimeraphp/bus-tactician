<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Bus\Tactician\DependencyInjection;

use Lcobucci\Chimera\Bus\Tactician\CommandBus;
use Lcobucci\Chimera\Bus\Tactician\Middleware\ReadModelConversion;
use Lcobucci\Chimera\Bus\Tactician\QueryBus;
use Lcobucci\Chimera\MessageCreator\NamedConstructorCreator;
use Lcobucci\Chimera\ReadModelConverter\CallbackConverter;
use League\Tactician\CommandBus as ServiceBus;
use League\Tactician\Container\ContainerLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterServices implements CompilerPassInterface
{
    private const MESSAGE_INVALID_HANDLER    = 'You must specify the "bus" and "handles" arguments in "%s" (tag "%s").';
    private const MESSAGE_INVALID_MIDDLEWARE = 'You must specify the "bus" argument in "%s" (tag "%s").';

    private const OVERRIDABLE_DEPENDENCIES = [
        'message_creator'       => NamedConstructorCreator::class,
        'read_model_converter'  => CallbackConverter::class,
        'class_name_extractor'  => ClassNameExtractor::class,
        'method_name_inflector' => HandleInflector::class,
    ];

    /**
     * @var string
     */
    private $commandBusId;

    /**
     * @var string
     */
    private $queryBusId;

    /**
     * @var array
     */
    private $dependencies;

    public function __construct(
        string $commandBusId,
        string $queryBusId,
        array $dependencies = []
    ) {
        $this->commandBusId = $commandBusId;
        $this->queryBusId   = $queryBusId;
        $this->dependencies = $dependencies;
    }

    public function process(ContainerBuilder $container)
    {
        $dependencies      = $this->extractDependencies($container);
        $parsedHandlers    = $this->extractHandlers($container);
        $parsedMiddlewares = $this->extractMiddlewares($container);

        $this->registerCommandBus(
            $container,
            $dependencies['message_creator'],
            $dependencies['class_name_extractor'],
            $dependencies['method_name_inflector'],
            $parsedHandlers[$this->commandBusId] ?? [],
            $this->prioritiseMiddlewares($parsedMiddlewares[$this->commandBusId] ?? [])
        );

        $this->registerQueryBus(
            $container,
            $dependencies['message_creator'],
            $dependencies['read_model_converter'],
            $dependencies['class_name_extractor'],
            $dependencies['method_name_inflector'],
            $parsedHandlers[$this->queryBusId] ?? [],
            $this->prioritiseMiddlewares($parsedMiddlewares[$this->queryBusId] ?? [])
        );
    }

    private function extractDependencies(ContainerBuilder $container): array
    {
        $services = [];

        foreach (self::OVERRIDABLE_DEPENDENCIES as $name => $defaultImplementation) {
            $services[$name] = $this->registerIfNotPresent(
                $container,
                $this->dependencies[$name] ?? null,
                $defaultImplementation
            );
        }

        return $services;
    }

    private function registerIfNotPresent(
        ContainerBuilder $container,
        ?string $id,
        string $defaultImplementation
    ): Reference {
        $id        = $id ?: uniqid('chimera.internal_object.');
        $reference = new Reference($id);

        if (! $container->has($id)) {
            $container->setDefinition($id, $this->createService($defaultImplementation));
        }

        return $reference;
    }

    /**
     * @return string[][]
     */
    private function extractHandlers(ContainerBuilder $container): array
    {
        $handlers = [];

        foreach ($container->findTaggedServiceIds(Tags::HANDLER) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (! isset($tag['bus'], $tag['handles'])) {
                    throw new InvalidArgumentException(
                        sprintf(self::MESSAGE_INVALID_HANDLER, $serviceId, Tags::HANDLER)
                    );
                }

                $handlers[$tag['bus']]                  = $handlers[$tag['bus']] ?? [];
                $handlers[$tag['bus']][$tag['handles']] = $serviceId;
            }
        }

        return $handlers;
    }

    /**
     * @return Reference[][][]
     */
    private function extractMiddlewares(ContainerBuilder $container): array
    {
        $middlewares = [];

        foreach ($container->findTaggedServiceIds(Tags::MIDDLEWARE) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (! isset($tag['bus'])) {
                    throw new InvalidArgumentException(
                        sprintf(self::MESSAGE_INVALID_MIDDLEWARE, $serviceId, Tags::MIDDLEWARE)
                    );
                }

                $priority = $tag['priority'] ?? 0;

                $middlewares[$tag['bus']]              = $middlewares[$tag['bus']] ?? [];
                $middlewares[$tag['bus']][$priority]   = $middlewares[$tag['bus']][$priority] ?? [];
                $middlewares[$tag['bus']][$priority][] = new Reference($serviceId);
            }
        }

        return $middlewares;
    }

    /**
     * @param Reference[][] $middlewares
     *
     * @return Reference[]
     */
    private function prioritiseMiddlewares(array $middlewares): array
    {
        krsort($middlewares);

        $prioritised = [];

        foreach ($middlewares as $list) {
            foreach ($list as $reference) {
                $prioritised[] = $reference;
            }
        }

        return $prioritised;
    }

    private function registerCommandBus(
        ContainerBuilder $container,
        Reference $messageCreator,
        Reference $commandNameExtractor,
        Reference $methodNameInflector,
        array $handlers,
        array $middlewares
    ): void {
        $internalBus = $this->registerTacticianBus(
            $container,
            $commandNameExtractor,
            $methodNameInflector,
            $handlers,
            $middlewares
        );

        $container->setDefinition(
            $this->commandBusId,
            $this->createService(CommandBus::class, [$internalBus, $messageCreator])
        );
    }

    private function registerQueryBus(
        ContainerBuilder $container,
        Reference $messageCreator,
        Reference $readModelConverter,
        Reference $commandNameExtractor,
        Reference $methodNameInflector,
        array $handlers,
        array $middlewares
    ): void {
        $middlewares[] = $this->registerReadModelConverter($container, $readModelConverter);

        $internalBus = $this->registerTacticianBus(
            $container,
            $commandNameExtractor,
            $methodNameInflector,
            $handlers,
            $middlewares
        );

        $container->setDefinition(
            $this->queryBusId,
            $this->createService(QueryBus::class, [$internalBus, $messageCreator])
        );
    }

    private function registerReadModelConverter(ContainerBuilder $container, Reference $readModelConverter): Reference
    {
        $readModelConversionId = uniqid('chimera.read_model_conversion.');

        $container->setDefinition(
            $readModelConversionId,
            $this->createService(ReadModelConversion::class, [$readModelConverter])
        );

        return new Reference($readModelConversionId);
    }

    private function registerTacticianBus(
        ContainerBuilder $container,
        Reference $commandNameExtractor,
        Reference $methodNameInflector,
        array $handlers,
        array $middlewares
    ): Reference {
        $id = uniqid('chimera.bus_internal.');

        $handlerMiddleware = $this->registerTacticianHandler(
            $container,
            $id,
            $commandNameExtractor,
            $methodNameInflector,
            $handlers
        );

        $middlewares[] = $handlerMiddleware;

        $container->setDefinition(
            $id,
            $this->createService(ServiceBus::class, [$middlewares])
        );

        return new Reference($id);
    }

    private function registerTacticianHandler(
        ContainerBuilder $container,
        string $busId,
        Reference $commandNameExtractor,
        Reference $methodNameInflector,
        array $handlers
    ): Reference {
        $id = $busId . '.handler';

        $arguments = [
            $commandNameExtractor,
            $this->registerTacticianLocator($container, $id, $handlers),
            $methodNameInflector,
        ];

        $container->setDefinition(
            $id,
            $this->createService(CommandHandlerMiddleware::class, $arguments)
        );

        return new Reference($id);
    }

    private function registerTacticianLocator(
        ContainerBuilder $container,
        string $handlerId,
        array $handlers
    ): Reference {
        $id = $handlerId . '.locator';

        $container->setDefinition(
            $id,
            $this->createService(
                ContainerLocator::class,
                [$this->registerServiceLocator($container, $handlers), $handlers]
            )
        );

        return new Reference($id);
    }

    private function registerServiceLocator(ContainerBuilder $container, array $handlers): Reference
    {
        $serviceIds = array_values($handlers);

        return ServiceLocatorTagPass::register(
            $container,
            array_map(
                function (string $id): Reference {
                    return new Reference($id);
                },
                array_combine($serviceIds, $serviceIds)
            )
        );
    }

    private function createService(string $class, array $arguments = []): Definition
    {
        return (new Definition($class, $arguments))->setPublic(false);
    }
}
