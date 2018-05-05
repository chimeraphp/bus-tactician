<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

use Chimera\ServiceBus\ReadModelConverter;
use Chimera\ServiceBus\Tactician\ReadModelConversionMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Chimera\ServiceBus\Tactician\ReadModelConversionMiddleware
 */
final class ReadModelConversionMiddlewareTest extends TestCase
{
    /**
     * @test
     *
     * @covers ::__construct()
     * @covers ::execute()
     */
    public function executeShouldProcessNextMiddlewareAndUseTheConverterToModifyTheResult(): void
    {
        $query     = new FetchById(1);
        $converter = $this->createMock(ReadModelConverter::class);

        $callback = function (): string {
            return 'a';
        };

        $converter->expects(self::once())
                  ->method('convert')
                  ->with($query, $callback())
                  ->willReturn('b');

        $middleware = new ReadModelConversionMiddleware($converter);

        self::assertSame('b', $middleware->execute($query, $callback));
    }
}
