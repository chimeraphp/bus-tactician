<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician;

use Chimera\ServiceBus\ReadModelConverter;
use League\Tactician\Middleware;

final class ReadModelConversionMiddleware implements Middleware
{
    public function __construct(private ReadModelConverter $converter)
    {
    }

    public function execute(mixed $query, callable $next): mixed
    {
        $result = $next($query);

        return $this->converter->convert($query, $result);
    }
}
