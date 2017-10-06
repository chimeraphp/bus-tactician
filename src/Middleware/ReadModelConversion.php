<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Bus\Tactician\Middleware;

use Lcobucci\Chimera\ReadModelConverter;
use League\Tactician\Middleware;

final class ReadModelConversion implements Middleware
{
    /**
     * @var ReadModelConverter
     */
    private $converter;

    public function __construct(ReadModelConverter $converter)
    {
        $this->converter = $converter;
    }

    public function execute($query, callable $next)
    {
        $result = $next($query);

        return $this->converter->convert($query, $result);
    }
}
