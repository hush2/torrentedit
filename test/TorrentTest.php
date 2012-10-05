<?php

require_once __DIR__.'/../vendor/autoload.php';

use TorrentEdit\Torrent,
    TorrentEdit\TorrentException;

class TorrentTest extends PHPUnit_Framework_TestCase
{
    public function testReadGoodTorrent()
    {
        try {
            $tor = new Torrent('test_read.torrent');
        } catch (TorrentEdit\TorrentException $e) {
            $this->fail('Good torrent failed!');
        }
    }

    public function testReadBadTorrent()
    {
        $this->setExpectedException('TorrentEdit\TorrentException');
        new Torrent('test_bad.torrent');
    }

    public function testWriteGoodTorrent()
    {
        try {
            $tor = new Torrent('test_read.torrent');
            file_put_contents('temp.torrent', $tor->save());
            new Torrent('temp.torrent');
        } catch (TorrentEdit\TorrentException $e) {
            $this->fail('Invalid torrent generated.');
        }
    }

   public function __destruct()
   {
        @unlink('temp.torrent');
   }

}
