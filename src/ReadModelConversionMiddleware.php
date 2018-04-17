<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\ServiceBus\Tactician;

use Lcobucci\Chimera\ServiceBus\ReadModelConverter;
use League\Tactician\Middleware;

final class ReadModelConversionMiddleware implements Middleware
{
    /**
     * @var ReadModelConverter
     */
    private $converter;

    public function __construct(ReadModelConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param object|mixed $query
     *
     * @return mixed
     */
    public function execute($query, callable $next)
    {
        $result = $next($query);

        return $this->converter->convert($query, $result);
    }
}
