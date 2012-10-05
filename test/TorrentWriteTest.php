<?php

require_once __DIR__.'/../vendor/autoload.php';

use TorrentEdit\Torrent;

class TorrentWriteTest extends PHPUnit_Framework_TestCase
{
    static $tmpfile  = 'temp.torrent';
    
    public function __construct()
    {        
        $this->testfile = 'test_read.torrent';
    }

    public function setUp()
    {
        $this->tor = new Torrent($this->testfile);
    }

    public function testAnnounce()
    {
        $announce = 'http://tracker.publicbt.com/announce';
        $this->tor->announce = $announce;
        $tor = $this->save_torrent();

        $this->assertEquals($announce, $tor->announce);
    }

    public function testTrackers()
    {
        $trackers =<<<EOF
http://tracker.publicbt.com/announce
udp://tracker.publicbt.com/announce

http://tracker.openbittorrent.com/announce
udp://tracker.openbittorrent.com/announce


EOF;
        $this->tor->trackers = $trackers;
        $tor = $this->save_torrent();

        $this->assertEquals($trackers, $tor->trackers);
    }
    public function testComment()
    {
        $comment = 'This is a comment...';
        $this->tor->comment = $comment;
        $tor = $this->save_torrent();

        $this->assertEquals($comment, $tor->comment);
    }

    public function testLongComment()
    {
        $comment = str_repeat('*', 2000);
        $this->tor->comment = $comment;
        $tor = $this->save_torrent();

        $this->assertEquals(1024, strlen($tor->comment));
    }

    public function testCreatedBy()
    {
        $created_by = 'Created by TorrentEdit';
        $this->tor->created_by = $created_by;
        $tor = $this->save_torrent();

        $this->assertEquals($created_by, $tor->created_by);
    }

    public function testLongCreatedBy()
    {
        $created_by = str_repeat('*', 2000);
        $this->tor->created_by = $created_by;
        $tor = $this->save_torrent();

        $this->assertEquals(1024, strlen($tor->created_by));
    }

    public function testCreationDate()
    {
        $creation_date = '12/25/2022 1:02:12 PM';
        $this->tor->creation_date = $creation_date;
        $tor = $this->save_torrent();

        $this->assertEquals($creation_date, $tor->creation_date);
    }

    public function testInvalidCreationDate()
    {
        $creation_date = '?';
        $this->tor->creation_date = $creation_date;
        $tor   = $this->save_torrent();
        $today = date('n/j/Y g:i:s A', time());
        // Skip seconds
        $date1 = substr($tor->creation_date, 0, strrpos($tor->creation_date, ':'));
        $date2 = substr($today, 0, strrpos($today, ':'));

        $this->assertEquals($date1, $date2);
    }

    public function testHash()
    {
        $hash = 'ED0E80CF622C27F1C4207D033C115F3A77467645';
        $this->tor->trackers      = "http://tracker.com";
        $this->tor->creation_date = "?";
        $this->tor->created_by    = "Meeeeeeeeeee!";
        $this->tor->comment       = "No Comment :)";
        $tor = $this->save_torrent();

        $this->assertEquals($tor->info_hash, $hash);
    }

    public function save_torrent()
    {
        file_put_contents(self::$tmpfile, $this->tor->save());
        return new Torrent(self::$tmpfile);
    }

    public static function tearDownAfterClass()    
    {
        @unlink(self::$tmpfile);
    }
}
