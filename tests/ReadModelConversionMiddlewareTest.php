<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

use Chimera\ServiceBus\ReadModelConverter;
use Chimera\ServiceBus\Tactician\ReadModelConversionMiddleware;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\TestCase;

#[PHPUnit\CoversClass(ReadModelConversionMiddleware::class)]
final class ReadModelConversionMiddlewareTest extends TestCase
{
    #[PHPUnit\Test]
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
