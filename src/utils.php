<?php


namespace torrentupload;


use DOMDocument;
use RuntimeException;
use PHP\BitTorrent\Torrent;

class utils
{

    public static function utf8_decode_array($array)
    {
        foreach ($array as $key => $value) {
            if (is_string($value))
                $array[$key] = utf8_decode($value);
        }
        return $array;
    }

    /**
     * @param string $path Path to create torrent from
     * @param string $torrent_file File name for the torrent file
     * @param string $tracker Tracker URL
     * @param integer $piece_length Piece length exponent
     * @return string The created torrent file
     */
    public static function create_torrent(string $path, string $torrent_file, $tracker='http://localhost', $piece_length = null): string
    {
        if (!file_exists($torrent_file))
        {
            $torrent = Torrent::createFromPath($path, $tracker);
            if(!empty($piece_length))
                $torrent->setPieceLengthExp($piece_length);
            $torrent->save($torrent_file);
        }
        else
            echo "Torrent is already created\n";
        return $torrent_file;
    }

    /**
     * Create torrent file
     * @param string $path Path to create torrent from
     * @param string $torrentfile Torrent file name
     * @param string $tracker Tracker URL
     * @param int $piece_length Piece length in megabytes
     * @return string Torrent file
     * Information from http://torrentinvites.org/f29/piece-size-guide-167985/ :
     * so lets assume we leave 100kb of space for the list of files and whatnot in .torrent. the piece hashes is a list of 20-byte SHA-1 hashes for each peace, so thats about 46000 pieces we can store in torrent. doing the division to keep the .torrent under 1 MB:
     * 1 mb piece size = max of about 45 GB torrent
     * 4 mb piece size = max of 180 GBs
     * 8 mb piece size = max of 360 GBs
     **/
    public static function buildtorrent(string $path, string $torrentfile, string $tracker = 'http://localhost', int $piece_length = 4): string
    {
        if (!file_exists($torrentfile))
        {
            echo "Creating torrent\n";

            $piece_length = $piece_length * pow(1024, 2); //Convert piece length to bytes
            $cmd = sprintf('buildtorrent -p1 -l %d -a %s "%s" "%s" 2>&1', $piece_length, $tracker, $path, $torrentfile);
            echo shell_exec($cmd);

            if (!file_exists($torrentfile))
            {
                throw new RuntimeException("Torrent creation failed\n$cmd\n");
                //return false;
            }
        }
        else
            echo "Torrent is already created\n";
        return $torrentfile;
    }

    /**
     * Get the page title
     * @param string $body HTML string
     * @return string Page title
     */
    public static function get_title(string $body): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($body);
        $title = $dom->getElementsByTagName('title');
        if($title->length>0)
            return $title->item(0)->textContent;
        else
            return '';
    }

    public static function strip_non_ascii($string): string
    {
        return preg_replace('/[^[:ascii:]]/', '', $string);
    }
}