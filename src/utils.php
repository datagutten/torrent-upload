<?php


namespace torrentupload;


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
}