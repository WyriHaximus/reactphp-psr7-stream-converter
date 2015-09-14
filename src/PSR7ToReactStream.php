<?php

namespace WyriHaximus\React\PSR7StreamConverter;

use Evenement\EventEmitter;
use Evenement\EventEmitterTrait;
use Psr\Http\Message\StreamInterface;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class PSR7ToReactStream extends EventEmitter implements ReadableStreamInterface, WritableStreamInterface
{
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

        $this->loop->addTimer(0.001, array($this, 'tick'));
    }

    public function tick()
    {
        do {
            $data = $this->psr7Stream->read(1024);
            $this->emit('data', array($data, $this));
        } while ($data !== '');

        if (!$this->psr7Stream->eof()) {
            $this->loop->addTimer(0.001, array($this, 'tick'));
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

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);
    }

    public function close()
    {
        $this->closed = true;
        $this->emit('end', array($this));
        $this->emit('close', array($this));
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
