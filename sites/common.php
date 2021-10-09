<?php


namespace torrentupload\sites;


use Requests_Response;
use torrentupload\exceptions\AlreadyUploadedException;
use torrentupload\exceptions\LoginFailedException;
use torrentupload\exceptions\SiteErrorException;
use torrentupload\site;

abstract class common extends site
{
    /**
     * @var bool Use UTF-8 for the site?
     */
    public static bool $use_utf8 = true;

    /**
     * @var string Site slug used as identifier
     */
    public static string $site_slug;

    /**
     * @var string Site URL
     */
    public static string $site_url;

    /**
     * Check if a user is logged in
     * @param Requests_Response|null $response Response with the front page of the tracker
     * @return bool|string Username of the logged-in user. Return false if no user is logged in
     */
    abstract function is_logged_in(Requests_Response $response = null): bool|string;

    /**
     * Log in to the tracker website
     * @param string $username Tracker user name
     * @param string $password Tracker password
     * @return string Username of the logged-in user
     * @throws LoginFailedException
     */
    abstract public function login(string $username, string $password): string;

    /**
     * Check if the upload was successful
     * @param Requests_Response $response Response from upload
     * @return string Torrent file URL
     * @throws SiteErrorException Error message on site
     * @throws AlreadyUploadedException Torrent is already uploaded
     */
    abstract function handle_upload(Requests_Response $response): string;
}