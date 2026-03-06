<?php

namespace QUITests\History\Exception;

use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\History\Exception\HistoryEntryNotFoundException;

class HistoryEntryNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsQuiException(): void
    {
        $Exception = new HistoryEntryNotFoundException('history entry missing');

        $this->assertInstanceOf(Exception::class, $Exception);
        $this->assertSame('history entry missing', $Exception->getMessage());
    }
}
