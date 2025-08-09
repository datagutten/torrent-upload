<?php


namespace torrentupload;

use datagutten\descriptionMaker\utils as descriptionUtils;
use datagutten\tools\files\files;
use GetOpt;
use torrentupload\sites\common;
use WpOrg\Requests;

class upload
{
    public GetOpt\GetOpt $getOpt;
    /**
     * @var array
     */
    public array $path_info;
    public string $path;
    /**
     * @var string Content folder
     */
    public string $folder;
    public sites\common $site;

    public function __construct(array $options)
    {
        $this->getOpt = new GetOpt\GetOpt($options);
        // process arguments and catch user errors
        try
        {
            try
            {
                $this->getOpt->process();
            }
            catch (GetOpt\ArgumentException\Missing $exception)
            {
                // catch missing exceptions if help is requested
                if (!$this->getOpt->getOption('help'))
                {
                    throw $exception;
                }
            }
        }
        catch (GetOpt\ArgumentException $exception)
        {
            file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
            echo PHP_EOL . $this->getOpt->getHelpText();
            exit;
        }

        if (empty($this->getOpt->getOptions()) /*|| count($this->getOpt->getOptions()) != count($options) * 2*/)
            die($this->getOpt->getHelpText());

        $this->site_class();
    }

    /**
     * Get and initiate the class for the given site
     * @return common
     */
    public function site_class(): sites\common
    {
        $site_name = $this->getOpt->getOption('site');
        $this->site = new ('torrentupload\\sites\\' . $site_name);
        return $this->site;
    }

    public function login(): void
    {
        try
        {
            $user = $this->site->is_logged_in();
            if ($user === false)
            {
                $user = $this->site->login();
            }
            printf("logged in as %s\n", $user);
        }
        catch (exceptions\LoginFailedException $e)
        {
            die(sprintf("Login failed: %s\n%s", $e->getMessage(), $e->getTraceAsString()));
        }
        catch (Requests\Exception $e)
        {
            die(sprintf("HTTP error on login: %s\n%s", $e->getMessage(), $e->getTraceAsString()));
        }
    }

    /**
     * Upload a torrent to the site
     * @return string Torrent file URL
     * @throws exceptions\AlreadyUploadedException
     * @throws exceptions\SiteErrorException
     * @throws exceptions\UploadFailedException
     */
    public function upload(): string
    {
        $path_info = $this->pathinfo();
        $mediainfo = $this->mediainfo();
        $categories = $this->site->get_template($this->getOpt->getOption('category'));

        return $this->site->upload($this->torrent_file(), $path_info['filename'], $this->description(), $categories, mediainfo: $mediainfo);
    }

    public function pathinfo(): array
    {
        $this->path_info = pathinfo($this->getOpt->getOption('path'));
        $this->path = realpath($this->getOpt->getOption('path'));
        if (is_file($this->path))
            $this->folder = $this->path_info['dirname'];
        else
            $this->folder = $this->path;
        return $this->path_info;
    }

    /**
     * Get torrent file and create it if it does not exist
     * @return string
     */
    public function torrent_file(): string
    {
        $file = files::path_join($this->path_info['dirname'], utils::strip_non_ascii($this->path_info['basename'] . '.torrent'));
        if (!file_exists($file))
        {
            utils::buildtorrent($this->path, $file, 'http://tracker/announce.php');
        }
        return $file;
    }

    public function mediainfo(): string
    {
        $file_mediainfo = files::path_join($this->path_info['dirname'], $this->path_info['basename'] . '.mediainfo');
        if (!file_exists($file_mediainfo))
        {
            printf("Mediainfo not found %s\n", $file_mediainfo);
            return '';
        }
        else
            return file_get_contents($file_mediainfo);
    }

    /**
     * Get description for the upload
     * @return string
     */
    public function description(): string
    {
        $file_description = descriptionUtils::description_file($this->path);
        return file_get_contents($file_description);
    }

    public function log_files(): array
    {
        return glob($this->folder . '/*.log');
    }
}