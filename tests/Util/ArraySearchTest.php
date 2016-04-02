<?php

class ArraySearchTest extends PHPUnit_Framework_TestCase
{
    public function testReturnsFalseOnNotLocated()
    {
        $arr = [1, 2, 3, 4, 5, 6];
        $this->assertFalse(\FlyPHP\Util\ArraySearch::findObject(7, $arr));
    }

    public function testReturnsIndexOnLocated()
    {
        $arr = [1, 2, 3];
        $this->assertEquals(0, \FlyPHP\Util\ArraySearch::findObject(1, $arr));
    }

    public function testReturnsCustomKeyOnLocated()
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->assertEquals('a', \FlyPHP\Util\ArraySearch::findObject(1, $arr));
    }
}