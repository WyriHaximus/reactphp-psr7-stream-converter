<?php

namespace WyriHaximus\React\Tests\PSR7StreamConverter;

use React\EventLoop\ExtEventLoop;
use React\EventLoop\Factory;
use React\EventLoop\LibEventLoop;
use React\EventLoop\LibEvLoop;
use React\EventLoop\StreamSelectLoop;

class EventLoopProvider
{
    public static function getLoops()
    {
        $loops = [
            [Factory::create()],
            [new StreamSelectLoop()],
        ];

        if (function_exists('event_base_new')) {
            $loops[] = [new LibEventLoop()];
        }
        if (class_exists('libev\EventLoop')) {
            $loops[] = [new LibEvLoop()];
        }

        if (class_exists('EventBase')) {
            $loops[] = [new ExtEventLoop()];
        }

        return $loops;
    }
}
