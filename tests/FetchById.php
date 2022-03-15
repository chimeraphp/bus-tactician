<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

final class FetchById
{
    public function __construct(public readonly int $id)
    {
    }
}
