<?php

namespace WyriHaximus\React\PSR7StreamConverter;

use Psr\Http\Message\StreamInterface;
use React\EventLoop\LoopInterface;

class ReactToPSR7Stream implements StreamInterface
{
    protected $eof = false;
    protected $size = 0;
    protected $buffer = '';
    protected $loop;
    protected $reactStream;

    public function __construct(LoopInterface $loop, $reactStream)
    {
        $this->loop = $loop;
        $this->reactStream = $reactStream;
        $this->reactStream->on(
            'data',
            function ($data) {
                $this->buffer .= $data;
                $this->size = strlen($this->buffer);
            }
        );

        $this->reactStream->on(
            'end',
            function () {
                $this->eof = true;
            }
        );
    }

    public function eof()
    {
        return $this->eof && $this->size === 0;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function isReadable()
    {
        return $this->reactStream->isReadable();
    }

    public function tell()
    {
        return false;
    }

    public function write($string)
    {
        $this->reactStream->write($string);
        return strlen($string);
    }

    public function rewind()
    {
        return false;
    }

    public function isWritable()
    {
        return $this->reactStream->isWritable();
    }

    public function isSeekable()
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return false;
    }

    public function read($length)
    {
        $this->toTickOrNotToTick();

        if (strlen($this->buffer) <= $length) {
            $buffer = $this->buffer;
            $this->buffer = '';
            $this->size = 0;
            return $buffer;
        }

        $buffer = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, $length);
        $this->size = strlen($this->buffer);
        return $buffer;
    }

    public function getContents($maxLength = -1)
    {
        $buffer = '';
        while (!$this->eof()) {
            $buffer .= $this->read(1000000);
        }
        return $buffer;
    }

    public function __toString()
    {
        return $this->getContents();
    }

    public function getMetadata($key = null)
    {
        $metadata = array(
            'timed_out'     => '',
            'blocked'       => false,
            'eof'           => $this->eof(),
            'unread_bytes'  => '',
            'stream_type'   => '',
            'wrapper_type'  => '',
            'wrapper_data'  => '',
            'mode'          => '',
            'seekable'      => false,
            'uri'           => '',
        );

        if (!$key) {
            return $metadata;
        }

        return isset($metadata[$key]) ? $metadata[$key] : null;
    }

    public function attach($stream)
    {
    }

    public function detach()
    {
    }

    public function close()
    {
        $this->reactStream->close();
    }

    protected function toTickOrNotToTick()
    {
        if ($this->size === 0) {
            $this->loop->tick();
        }
    }
}
