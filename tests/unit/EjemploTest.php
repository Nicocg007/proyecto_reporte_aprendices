<?php
use PHPUnit\Framework\TestCase;

final class EjemploTest extends TestCase
{
    public function testSuma(): void
    {
        $this->assertSame(4, 2 + 2);
    }
}