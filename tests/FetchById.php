<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

final class FetchById
{
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
