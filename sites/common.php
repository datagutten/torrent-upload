<?php

namespace torrentupload\sites;

use datagutten\musicbrainz;
use torrentupload\exceptions\AlreadyUploadedException;
use torrentupload\exceptions\LoginFailedException;
use torrentupload\exceptions\SiteErrorException;
use torrentupload\site;
use WpOrg\Requests;

abstract class common extends site
{
    /**
     * Check if a user is logged in
     * @param Requests\Response|null $response Response with the front page of the tracker
     * @return bool|string Username of the logged-in user. Return false if no user is logged in
     */
    abstract function is_logged_in(Requests\Response $response = null): bool|string;

    /**
     * Log in to the tracker website
     * @param string $username Tracker user name
     * @param string $password Tracker password
     * @return string Username of the logged-in user
     * @throws LoginFailedException
     */
    abstract public function login(string $username, string $password): string;

    /**
     * Upload a torrent with info from a MusicBrainz release object
     * @param string $torrentfile
     * @param musicbrainz\seed\Release $release MusicBrainz release object
     * @param string $description
     * @param array $tags
     * @param bool $re_release
     * @param array $logs
     * @return string
     */
    abstract public function upload_mb(string $torrentfile, musicbrainz\seed\Release $release, string $description, array $tags = [], bool $re_release = false, array $logs = []): string;

    /**
     * Check if the upload was successful
     * @param Requests\Response $response Response from upload
     * @return string Torrent file URL
     * @throws SiteErrorException Error message on site
     * @throws AlreadyUploadedException Torrent is already uploaded
     */
    abstract function handle_upload(Requests\Response $response): string;
}