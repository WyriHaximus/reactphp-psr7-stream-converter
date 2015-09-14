<?php

namespace WyriHaximus\React\PSR7StreamConverter;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

interface DuplexStreamInterface extends ReadableStreamInterface, WritableStreamInterface
{
}
