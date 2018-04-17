<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\ServiceBus\Tactician\Tests;

use Lcobucci\Chimera\ServiceBus\Tactician\ReadModelConversionMiddleware;
use Lcobucci\Chimera\ServiceBus\ReadModelConverter;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Lcobucci\Chimera\ServiceBus\Tactician\ReadModelConversionMiddleware
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

        $converter->expects($this->once())
                  ->method('convert')
                  ->with($query, $callback())
                  ->willReturn('b');

        $middleware = new ReadModelConversionMiddleware($converter);

        self::assertSame('b', $middleware->execute($query, $callback));
    }
}
