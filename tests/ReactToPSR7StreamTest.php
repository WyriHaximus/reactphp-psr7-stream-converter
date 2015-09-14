<?php

namespace WyriHaximus\React\Tests\PSR7StreamConverter;

use React\EventLoop\LoopInterface;
use React\Stream\ThroughStream;
use WyriHaximus\React\PSR7StreamConverter\ReactToPSR7Stream;

class ReactToPSR7StreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param LoopInterface $loop
     * @dataProvider WyriHaximus\React\Tests\PSR7StreamConverter\EventLoopProvider::getLoops
     */
    public function testBasic(LoopInterface $loop)
    {
        $throughStream = new ThroughStream();
        $converter = new ReactToPSR7Stream($loop, $throughStream);

        $this->assertTrue($converter->isReadable());
        $this->assertTrue($converter->isWritable());



        $throughStream->write('abc');
        $this->assertEquals('abc', $converter->read(3));


        $throughStream->write('def');
        $this->assertFalse($converter->eof());
        $this->assertEquals(3, $converter->getSize());
        $throughStream->write('ghi');
        $this->assertEquals(6, $converter->getSize());
        $this->assertEquals('defghi', $converter->read(6));
        $this->assertEquals(0, $converter->getSize());

        $throughStream->write('jklmnopqrst');
        $this->assertEquals('jklmno', $converter->read(6));
        $throughStream->end('uvwxyz');
        $this->assertEquals('pqrstuvwxyz', (string)$converter);
        $this->assertEquals(0, $converter->getSize());
        $this->assertEquals('', $converter->read(123456));

        $this->assertTrue($converter->eof());

        $this->assertFalse($converter->isReadable());
        $this->assertFalse($converter->isWritable());
        $this->assertFalse($converter->isSeekable());
        $this->assertFalse($converter->seek(1));
        $this->assertFalse($converter->tell());
        $this->assertFalse($converter->rewind());
    }
}
