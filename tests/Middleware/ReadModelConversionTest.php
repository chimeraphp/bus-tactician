<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Bus\Tactician\Tests\Middleware;

use Lcobucci\Chimera\Bus\Tactician\Middleware\ReadModelConversion;
use Lcobucci\Chimera\Bus\Tactician\Tests\FetchById;
use Lcobucci\Chimera\ReadModelConverter;

final class ReadModelConversionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     *
     * @covers \Lcobucci\Chimera\Bus\Tactician\Middleware\ReadModelConversion
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

        $middleware = new ReadModelConversion($converter);

        self::assertSame('b', $middleware->execute($query, $callback));
    }
}
