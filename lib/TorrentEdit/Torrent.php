<?php
// (C) 2012 hush2 <hushywushy@gmail.com>

namespace TorrentEdit;

use BEncoder\BEncoder,
    BEncoder\BEncoderException;

define('CRLF', "\r\n");

class Torrent
{
    private $_tor   = null,
            $_meta  = null,
            $_info  = null,
            $_size  = 0;

    function __construct($file)
    {
        if (is_file($file)) {
            $file = file_get_contents($file);
        }
        try  {
            $this->_tor = new BEncoder();
            $this->_tor->decode($file);
        } catch (BEncoderException $e) {
            throw new TorrentException('Bad torrent.');
        }
        $this->_meta = $this->_tor->source;
        if (empty($this->_meta)) {
            throw new TorrentException('Empty torrent.');
        }
        $this->_info = $this->_meta['info'];
    }

    function __get($name)
    {
        switch ($name) {

            // This field is ignored if announce-list is present (first entry on
            // announce-list used).
            case 'announce':
                if (isset($this->_meta['announce'])) {
                  return $this->_meta['announce'];
                }
                return;

            // This field is in UNIX timestamp format.
            case 'creation_date':
                if (isset($this->_meta['creation date'])) {
                    return date('n/j/Y g:i:s A', $this->_meta['creation date']);
                }
                return;

            case 'created_by':
                if (isset($this->_meta['created by'])) {
                    return $this->_meta['created by'];
                }
                break;

            case 'comment':
                if (isset($this->_meta['comment'])) {
                    return $this->_meta['comment'];
                }
                break;

            case 'encoding':
                if (isset($this->_meta['encoding'])) {
                    return $this->_meta['encoding'];
                }
                break;

            case 'piece_length':
                if (isset($this->_info['piece length'])) {
                    return $this->_info['piece length'];
                }
                break;

            case 'pieces':
                if (isset($this->_info['pieces'])) {
                    return strlen($this->_info['pieces']) / 20;
                }
                break;

            case 'info_hash':
                $info_hash = sha1($this->_tor->encode($this->_meta['info']));
                return strtoupper($info_hash);

            // http://bittorrent.org/beps/bep_0027.html
            case 'private':
                if (isset($this->_info['private'])) {
                    return $this->_info['private'] == "1" ? true : false;
                }
                return '';

            //http://bittorrent.org/beps/bep_0012.html
            case 'trackers':
            case 'announce_list':

                $announce_list = '';

                if (isset($this->_meta['announce-list'])) {
                    $alist = $this->_meta['announce-list'];
                    if (gettype($alist[0]) != 'array') { // Invalid
                        break;
                    }
                    foreach ($alist as $announce) {
                        foreach ($announce as $ann) { // Tiered
                            $announce_list .= $ann . CRLF;
                        }
                        $announce_list .= CRLF;
                    }
                }
                elseif (isset($this->_meta['announce'])) {
                    $announce_list = $this->_meta['announce'];
                }
                return $announce_list;


            // "Save Directory" for multi-file torrent.
            case 'dir':
                if (isset($this->_info['files'])) {
                    return $this->_info['name'];
                }
                break;

            case 'files':
                $filelist = array();

                if (isset($this->_info['files'])) {
                    $files = $this->_info['files'];
                    foreach ($files as $file) {
                        $filedir = '';
                        foreach ($file['path'] as $filepath) {
                           $filedir .=  "$filepath/";
                        }
                        $filelist[] = array('name' => substr($filedir, 0, -1),
                                            'size' => $file['length']);
                    }
                } else { // single file
                    $filelist[] = array('name' => $this->_info['name'],
                                        'size' => $this->_info['length']);
                }
                $this->_size = 0;
                foreach ($filelist as $file) {
                    $this->_size += (int) $file['size'];
                }
                return $filelist;

            // Total file size of torrent (access 'files' property or else this is zero).
            case 'size':
                return $this->_size;

            default:
                break;
        }
        return false;
    }

    function __set($name, $value)
    {
        switch ($name) {

            // Limit strings to 1024 bytes.
            case 'comment':
                $this->_meta['comment'] = substr($value, 0, 1024);
                return;

            case 'created_by':
                $this->_meta['created by'] = substr($value, 0, 1024);
                return;

            // Date format is "MM:DD:YYYY HH:MM:SS AM/PM".
            // If invalid, use current timestamp.
            case 'creation_date':
                $date = strtotime($value);
                $this->_meta['creation date'] = $date ?: time(); // PHP 5.3
                return;

            // TODO: Fix CRLF inconsistency.
            case 'trackers':
            case 'announce':
            case 'announce_list':

                $tracker_list   = explode(CRLF, $value);
                $tracker_list[] = ''; // terminate list
                $announce_list  = array();
                $trackers       = array();

                foreach ($tracker_list as $tracker) {
                    if (!empty($tracker)) {
                        $trackers[] = $tracker;
                    }
                    elseif (!empty($trackers)) {
                        $announce_list[] = $trackers;
                        $trackers = array();
                    }
                }

                //  Remove announce field.
                if (empty($announce_list)) {
                    unset($this->_meta['announce']);
                    unset($this->_meta['announce-list']);
                    return;
                }

                if (count($tracker_list > 1) && isset($this->_meta['announce-list'])) {
                    $this->_meta['announce-list'] = $announce_list;
                    $this->_meta['announce'] = $announce_list[0][0];
                }
                elseif (count($tracker_list == 1) && isset($this->_meta['announce'])) {
                    $this->_meta['announce'] = $announce_list[0][0];
                }
                return;

            default:
                return;
        }
    }

    function save()
    {
        return $this->_tor->encode($this->_meta);
    }
}
