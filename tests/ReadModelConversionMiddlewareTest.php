<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

use Chimera\ServiceBus\ReadModelConverter;
use Chimera\ServiceBus\Tactician\ReadModelConversionMiddleware;
use PHPUnit\Framework\TestCase;

/** @coversDefaultClass \Chimera\ServiceBus\Tactician\ReadModelConversionMiddleware */
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

        $domainObject   = (object) ['name' => 'a'];
        $transferObject = (object) ['name' => 'b'];

        $callback = static fn (): object => $domainObject;

        $converter->expects(self::once())
                  ->method('convert')
                  ->with($query, $callback())
                  ->willReturn($transferObject);

        $middleware = new ReadModelConversionMiddleware($converter);

        self::assertSame($transferObject, $middleware->execute($query, $callback));
    }
}
