<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\ServiceBus\Tactician\Tests\DependencyInjection;

use Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices;
use Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\Tags;
use Lcobucci\Chimera\ServiceBus\Tactician\Tests\FetchById;
use Lcobucci\Chimera\ReadModelConverter;
use League\Tactician\Middleware;
use League\Tactician\Plugins\NamedCommand\NamedCommandExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function array_filter;
use function current;
use function end;
use function preg_match;

final class RegisterServicesTest extends TestCase
{
    private const DEFAULT_MIDDLEWARES_PATTERN = '/^(chimera\.(read_model_conversion|bus_internal)\..*|'
                                              . '.*\.inner\.handler)$/';

    private const COMMAND_BUS = 'command_bus';
    private const QUERY_BUS   = 'query_bus';

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldThrowAnExceptionIfAHandlerIsNotTaggedAsConnectedToAnyBus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $handler = new Definition();
        $handler->addTag(Tags::HANDLER, ['handles' => FetchById::class]);

        $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            [],
            ['handler' => $handler]
        );
    }

    /**
     * @test
     *
     * @dataProvider handlerTags
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldThrowAnExceptionIfAHandlerIsNotTaggedToHandleSomething(string $tag): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $handler = new Definition();
        $handler->addTag($tag, ['bus' => self::COMMAND_BUS]);

        $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            [],
            ['handler' => $handler]
        );
    }

    /**
     * @return mixed[]
     */
    public function handlerTags(): array
    {
        return [
            'bus handler'     => [Tags::HANDLER],
            'query handler'   => [Tags::QUERY_HANDLER],
            'command handler' => [Tags::COMMAND_HANDLER],
        ];
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldCreateCommandAndQueryBusesWithDefaultHandlersConnected(): void
    {
        $container = $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            [],
            []
        );

        $this->assertSameHandlers($container, self::COMMAND_BUS);
        $this->assertSameHandlers($container, self::QUERY_BUS);
    }

    /**
     * @test
     *
     * @dataProvider possibleHandlerTags
     *
     * @param mixed[]  $tags
     * @param string[] $expectedCommandHandlers
     * @param string[] $expectedQueryHandlers
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldCreateCommandAndQueryBusesWhichAreConnectedToTheTaggedHandlers(
        array $tags,
        array $expectedCommandHandlers,
        array $expectedQueryHandlers
    ): void {
        $handler = new Definition();

        foreach ($tags as $tag) {
            [$name, $attributes] = $tag;

            $handler->addTag($name, $attributes);
        }

        $container = $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            [],
            ['handler' => $handler]
        );

        $this->assertSameHandlers($container, self::COMMAND_BUS, $expectedCommandHandlers);
        $this->assertSameHandlers($container, self::QUERY_BUS, $expectedQueryHandlers);
    }

    /**
     * @return mixed[]
     */
    public function possibleHandlerTags(): array
    {
        return [
            'commands only'                   => [
                [[Tags::HANDLER, ['bus' => self::COMMAND_BUS, 'handles' => FetchById::class]]],
                [FetchById::class => 'handler'],
                [],
            ],
            'queries only'                    => [
                [[Tags::HANDLER, ['bus' => self::QUERY_BUS, 'handles' => FetchById::class]]],
                [],
                [FetchById::class => 'handler'],
            ],
            'both'                            => [
                [
                    [Tags::HANDLER, ['bus' => self::COMMAND_BUS, 'handles' => FetchById::class]],
                    [Tags::HANDLER, ['bus' => self::QUERY_BUS, 'handles' => FetchById::class]],
                ],
                [FetchById::class => 'handler'],
                [FetchById::class => 'handler'],
            ],
            'commands only (default handler)' => [
                [[Tags::COMMAND_HANDLER, ['handles' => FetchById::class]]],
                [FetchById::class => 'handler'],
                [],
            ],
            'queries only (default handler)'  => [
                [[Tags::QUERY_HANDLER, ['handles' => FetchById::class]]],
                [],
                [FetchById::class => 'handler'],
            ],
            'both (default handler)'          => [
                [
                    [Tags::COMMAND_HANDLER, ['handles' => FetchById::class]],
                    [Tags::QUERY_HANDLER, ['handles' => FetchById::class]],
                ],
                [FetchById::class => 'handler'],
                [FetchById::class => 'handler'],
            ],
        ];
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldAddMiddlewaresToBothBusesWhenAMiddlewareIsNotTaggedAsConnectedToAnyBus(): void
    {
        $middleware = new Definition();
        $middleware->addTag(Tags::MIDDLEWARE);

        $container = $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            [],
            ['middleware' => $middleware]
        );

        $this->assertSameDeclaredMiddlewares($container, self::COMMAND_BUS, [new Reference('middleware')]);
        $this->assertSameDeclaredMiddlewares($container, self::QUERY_BUS, [new Reference('middleware')]);
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldCreateCommandAndQueryBusesWithDefaultMiddlewaresIfContainerIsEmpty(): void
    {
        $container = $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            [],
            []
        );

        $this->assertSameDeclaredMiddlewares($container, self::COMMAND_BUS, []);
        $this->assertSameDeclaredMiddlewares($container, self::QUERY_BUS, []);
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldAlwaysCreateCommandAndQueryBusesWithAPrioritizedListOfMiddlewares(): void
    {
        $middleware1 = new Definition();
        $middleware1->addTag(Tags::MIDDLEWARE, ['bus' => self::COMMAND_BUS]);
        $middleware1->addTag(Tags::MIDDLEWARE, ['bus' => self::QUERY_BUS]);

        $middleware2 = new Definition();
        $middleware2->addTag(Tags::MIDDLEWARE, ['bus' => self::COMMAND_BUS, 'priority' => 123]);

        $container = $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            [],
            ['middleware1' => $middleware1, 'middleware2' => $middleware2]
        );

        $this->assertSameDeclaredMiddlewares(
            $container,
            self::COMMAND_BUS,
            [new Reference('middleware2'), new Reference('middleware1')]
        );

        $this->assertSameDeclaredMiddlewares(
            $container,
            self::QUERY_BUS,
            [new Reference('middleware1')]
        );
    }

    /**
     * @return mixed[]
     */
    public function provideOverridableDependencies(): array
    {
        return [
            'command bus with overridden inflector' => [
                self::COMMAND_BUS,
                'method_name_inflector',
                'inflector',
                2,
            ],
            'query bus with overridden inflector' => [
                self::QUERY_BUS,
                'method_name_inflector',
                'inflector',
                2,
            ],
            'command bus with overridden extractor' => [
                self::COMMAND_BUS,
                'class_name_extractor',
                'extractor',
                0,
            ],
            'query bus with overridden extractor' => [
                self::QUERY_BUS,
                'class_name_extractor',
                'extractor',
                0,
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideOverridableDependencies
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldCreateCommandAndQueryBusesThatUsesOverriddenDependencies(
        string $bus,
        string $overridableDependencyId,
        string $dependencyReference,
        int $argument
    ): void {
        $container = $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            [$overridableDependencyId => $dependencyReference],
            [$dependencyReference => new Definition(NamedCommandExtractor::class)]
        );

        $this->assertOverriddenDependency($container, $bus, $dependencyReference, $argument);
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldCreateCommandAndQueryBusesThatUseOverriddenConverterInsteadOfDefaultOne(): void
    {
        $definitions = ['converter' => new Definition(Middleware::class)];

        $container = $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            ['read_model_converter' => 'converter'],
            $definitions
        );

        self::assertSame($definitions['converter'], $this->getQueryBusConverter($container, self::QUERY_BUS));
    }

    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\ServiceBus\Tactician\DependencyInjection\RegisterServices
     */
    public function processShouldCreateCommandAndQueryBusesThatUseOverriddenMessageCreatorInsteadOfDefaultOne(): void
    {
        $definitions = ['creator' => new Definition(ReadModelConverter::class)];

        $container = $this->processCompilerPass(
            self::COMMAND_BUS,
            self::QUERY_BUS,
            ['message_creator' => 'creator'],
            $definitions
        );

        self::assertSame(
            $definitions['creator'],
            $container->getDefinition($container->getDefinition(self::COMMAND_BUS)->getArgument(1))
        );
    }

    /**
     * @param mixed[]      $dependencies
     * @param Definition[] $definitions
     */
    public function processCompilerPass(
        string $commandBusId,
        string $queryBusId,
        array $dependencies = [],
        array $definitions = []
    ): ContainerBuilder {
        $container = new ContainerBuilder();
        $container->setDefinitions($definitions);

        $pass = new RegisterServices($commandBusId, $queryBusId, $dependencies);
        $pass->process($container);

        return $container;
    }

    /**
     * @param Reference[] $expectedMiddlewares
     */
    private function assertSameDeclaredMiddlewares(
        ContainerBuilder $container,
        string $busId,
        array $expectedMiddlewares = []
    ): void {
        $bus         = $container->getDefinition($busId);
        $internalBus = $container->getDefinition($bus->getArgument(0));

        $middlewareList = array_filter(
            $internalBus->getArgument(0),
            function (Reference $reference): bool {
                return preg_match(self::DEFAULT_MIDDLEWARES_PATTERN, (string) $reference) === 0;
            }
        );

        self::assertEquals($expectedMiddlewares, $middlewareList);
    }

    /**
     * @param string[] $handlerMap
     */
    private function assertSameHandlers(ContainerBuilder $container, string $bus, array $handlerMap = []): void
    {
        $handlerLocator = $container->getDefinition(
            $this->getHandlerMiddleware($container, $bus)->getArgument(1)
        );

        self::assertSame($handlerMap, $handlerLocator->getArgument(1));
    }

    private function assertOverriddenDependency(
        ContainerBuilder $container,
        string $bus,
        string $dependency,
        int $argument
    ): void {
        $handlerMiddleware = $this->getHandlerMiddleware($container, $bus);

        self::assertSame(
            $container->getDefinition($dependency),
            $container->getDefinition($handlerMiddleware->getArgument($argument))
        );
    }

    private function getHandlerMiddleware(ContainerBuilder $container, string $bus): Definition
    {
        $middlewareList = $this->getBusMiddlewares($container, $bus);

        return $container->getDefinition(end($middlewareList));
    }

    private function getQueryBusConverter(ContainerBuilder $container, string $bus): Definition
    {
        $middlewareList = $this->getBusMiddlewares($container, $bus);

        return $container->getDefinition(
            $container->getDefinition(current($middlewareList))->getArgument(0)
        );
    }

    /**
     * @return string[]
     */
    private function getBusMiddlewares(ContainerBuilder $container, string $bus): array
    {
        $internalBus = $container->getDefinition(
            $container->getDefinition($bus)->getArgument(0)
        );

        return $internalBus->getArgument(0);
    }
}
