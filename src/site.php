<?php


namespace torrentupload;


use datagutten\Requests_extensions\cookie_saver;
use datagutten\tools\files\files;
use FileNotFoundException;
use Requests_Exception;
use Requests_Response;
use Requests_Session;

abstract class site
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
    protected $site_folder;
    protected $common;
    public $session;

    /**
     * site constructor.
     * @param $site
     * @throws FileNotFoundException
     */
    public function __construct()
    {
        $site = static::$site_slug;
        $site_folder = files::path_join(__DIR__, '..', 'templates', $site);
        if(!file_exists($site_folder))
            throw new FileNotFoundException($site_folder);
        $this->site_folder = realpath($site_folder);
        $options = array();

        $options += ['transport'=>'datagutten\\Requests_extensions\\transport_cURL_multipart'];
        $this->session = new Requests_Session(static::$site_url, array(), array(), $options);

        $cookie_saver = new cookie_saver();
        if(file_exists($cookie_saver->file(static::$site_slug)))
            $this->session->options['cookies'] = $cookie_saver->load_cookies(static::$site_slug);
    }

    function __destruct()
    {
        $cookie_saver = new cookie_saver();
        $cookie_saver->save_cookies($this->session->options['cookies'], static::$site_slug);
    }

    /**
     * @param $url
     * @param array $headers
     * @param array $options
     * @return Requests_Response
     * @throws Requests_Exception
     */
    public function get($url, $headers = array(), $options = array())
    {
        $response = $this->session->get($url, $headers, $options);
        $response->throw_for_status();
        if(!static::$use_utf8)
            $response->body = utf8_encode($response->body);

        return $response;
    }

    /**
     * @param $url
     * @param array $headers
     * @param array $data
     * @param array $options
     * @return Requests_Response
     * @throws Requests_Exception
     */
    public function post($url, $headers = array(), $data = array(), $options = array())
    {
        if(!static::$use_utf8)
            $data = utils::utf8_decode_array($data);
        $response = $this->session->post($url, $headers, $data, $options);
        $response->throw_for_status();
        if(!static::$use_utf8)
            $response->body = utf8_encode($response->body);
        return $response;
    }

    /**
     * @param $topic
     * @return mixed
     * @throws FileNotFoundException
     */
    public function get_template($topic)
    {
        $template_file = sprintf('%s/%s.json', $this->site_folder, $topic);
        if(!file_exists($template_file))
            throw new FileNotFoundException($template_file);
        else
            return json_decode(file_get_contents($template_file), true);
    }

    /**
     * Get username and password for the current site
     * @return array Array with keys username and password
     * @throws FileNotFoundException
     */
    public function get_login()
    {
        $login_file = sprintf(__DIR__.'/../logins/%s.json', static::$site_slug);
        if(!file_exists($login_file))
            throw new FileNotFoundException($login_file);
        else
            return json_decode(file_get_contents($login_file), true);
    }
}