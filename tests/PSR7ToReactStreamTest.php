<?php

namespace WyriHaximus\React\Tests\PSR7StreamConverter;

use Phake;
use React\EventLoop\Factory;
use WyriHaximus\React\PSR7StreamConverter\PSR7ToReactStream;

class PSR7ToReactStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testBasic()
    {
        $self = $this;
        $dataChunks = array(
            'foo',
            '',
            'bar',
            '',
        );
        $loop = Factory::create();
        $psr7Stream = Phake::mock('Psr\Http\Message\StreamInterface');

        $psr7StreamRead = Phake::when($psr7Stream)->read(1024);
        foreach ($dataChunks as $chunk) {
            $psr7StreamRead = $psr7StreamRead->thenReturn($chunk);
            Phake::when($psr7Stream)->write($chunk)->thenCallParent();
        }
        Phake::when($psr7Stream)->eof()->thenReturn(false)->thenReturn(true);
        $converter = new PSR7ToReactStream($loop, $psr7Stream);
        $callableData = false;
        $i = 0;
        $converter->on('data', function ($data, $stream) use (&$callableData, &$i, $dataChunks, $self) {
            $self->assertEquals($dataChunks[$i++], $data);
            $self->assertInstanceOf('WyriHaximus\React\PSR7StreamConverter\PSR7ToReactStream', $stream);
            $stream->write($data);
            $callableData = true;
        });
        $callableEnd = false;
        $converter->on('end', function ($stream) use (&$callableEnd, $self) {
            $self->assertInstanceOf('WyriHaximus\React\PSR7StreamConverter\PSR7ToReactStream', $stream);
            $callableEnd = true;
        });
        $callableClose = false;
        $converter->on('close', function ($stream) use (&$callableClose, $self) {
            $self->assertInstanceOf('WyriHaximus\React\PSR7StreamConverter\PSR7ToReactStream', $stream);
            $callableClose = true;
        });

        $loop->run();

        $converter->end('baz');

        $this->assertTrue($callableData);
        $this->assertTrue($callableEnd);
        $this->assertTrue($callableClose);
        Phake::inOrder
        (
            Phake::verify($psr7Stream)->write($dataChunks[0]),
            Phake::verify($psr7Stream)->write($dataChunks[2]),
            Phake::verify($psr7Stream, Phake::times(2))->write($dataChunks[1]),
            Phake::verify($psr7Stream)->write('baz')
        );
    }
}
