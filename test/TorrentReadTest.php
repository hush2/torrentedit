<?php

require_once __DIR__.'/../vendor/autoload.php';

use TorrentEdit\Torrent,
    TorrentEdit\TorrentException;

class TorrentReadTest extends PHPUnit_Framework_TestCase
{
    private static $tor;

    public static function setUpBeforeClass()
    {
        self::$tor = new Torrent('test_read.torrent');
    }

    public function testAnnounce()
    {
        $announce = 'http://tracker1.com';

        $this->assertEquals($announce, self::$tor->announce);
    }

    public function testTrackers()
    {
        $trackers =<<<EOF
http://tracker1.com
udp://tracker2.com

http://tracker3.org
udp://tracker4.org


EOF;
        $this->assertEquals($trackers, self::$tor->trackers);
    }

    public function testCreationDate()
    {
        $date = '6/25/2012 7:58:20 AM';

        $this->assertEquals($date, self::$tor->creation_date);
    }

    public function testCreatedBy()
    {
        $created_by = 'uTorrent/2020';

        $this->assertEquals($created_by, self::$tor->created_by);
    }

    public function testComment()
    {
        $comment = 'This is a comment.';

        $this->assertEquals($comment, self::$tor->comment);
    }

    public function testPrivate()
    {
        $this->assertTrue(self::$tor->private);
    }

    public function testEncoding()
    {
        $this->assertEquals('UTF-8', self::$tor->encoding);
    }

    public function testPieceLength()
    {
        $this->assertEquals('65536', self::$tor->piece_length);
    }

    public function testPieces()
    {
        $this->assertEquals(3, self::$tor->pieces);
    }

    public function testFiles()
    {
        $files = array(array('name' => 'TestFile2.css',
                             'size' => 187832),
                       array('name' => 'TestFile1.htm',
                             'size' => 8015));

        $this->assertEquals($files, self::$tor->files);

        return self::$tor->size;
    }

    /**
     * @depends testFiles
     */
    public function testTotalSize($size)
    {
        $this->assertEquals(195847, $size);
    }

    public function testDirName()
    {
        $this->assertEquals('TestDir', self::$tor->dir);
    }

    public function testInfoHash()
    {
        $info_hash = 'ED0E80CF622C27F1C4207D033C115F3A77467645';

        $this->assertEquals($info_hash, self::$tor->info_hash);
    }


}
