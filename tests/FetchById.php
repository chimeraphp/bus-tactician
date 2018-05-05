<?php
declare(strict_types=1);

namespace Chimera\ServiceBus\Tactician\Tests;

final class FetchById
{
    /**
     * @var int
     */
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
