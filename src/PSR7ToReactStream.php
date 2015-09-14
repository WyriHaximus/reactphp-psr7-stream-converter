<?php

namespace WyriHaximus\React\PSR7StreamConverter;

use Evenement\EventEmitterTrait;
use Psr\Http\Message\StreamInterface;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class PSR7ToReactStream implements ReadableStreamInterface, WritableStreamInterface
{
    use EventEmitterTrait;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var StreamInterface
     */
    protected $psr7Stream;

    /**
     * @var bool
     */
    protected $closed = false;

    public function __construct(LoopInterface $loop, StreamInterface $psr7Stream)
    {
        $this->loop = $loop;
        $this->psr7Stream = $psr7Stream;

        $this->loop->futureTick([$this, 'tick']);
    }

    public function tick()
    {
        do {
            $data = $this->psr7Stream->read(1024);
            $this->emit('data', [$data, $this]);
        } while ($data !== '');

        if (!$this->psr7Stream->eof()) {
            $this->loop->futureTick([$this, 'tick']);
            return;
        }

        $this->close();
    }

    public function isReadable()
    {
        return !$this->closed;
    }

    public function pause()
    {
        // TODO: Implement pause() method.
    }

    public function resume()
    {
        // TODO: Implement resume() method.
    }

    public function pipe(WritableStreamInterface $dest, array $options = [])
    {
        Util::pipe($this, $dest, $options);
    }

    public function close()
    {
        $this->closed = true;
        $this->emit('end', [$this]);
        $this->emit('close', [$this]);
    }

    public function isWritable()
    {
        return !$this->closed;
    }

    public function write($data)
    {
        $this->psr7Stream->write($data);
        return strlen($data);
    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->close();
    }
}
