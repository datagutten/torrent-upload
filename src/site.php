<?php


namespace torrentupload;


use CURLFile;
use datagutten\Requests_extensions\cookie_saver;
use DOMDocument;
use FileNotFoundException;
use InvalidArgumentException;
use Requests_Exception;
use Requests_Exception_HTTP;
use Requests_Response;
use Requests_Session;
use torrentupload\exceptions\LoginFailedException;

class site
{
    protected $site;
    protected $site_folder;
    protected $common;
    public $session;

    /**
     * site constructor.
     * @param $site
     * @throws FileNotFoundException
     */
    public function __construct($site)
    {
        $this->site_folder = realpath(__DIR__.'/../templates/'.$site);
        if(!file_exists($this->site_folder))
            throw new FileNotFoundException($this->site_folder);
        $this->common = $this->get_template('site');
        $this->site = $site;
        $options = array();

        $options += ['transport'=>'datagutten\\Requests_extensions\\transport_cURL_multipart'];
        $this->session = new Requests_Session($this->common['url'], array(), array(), $options);

        $cookie_saver = new cookie_saver();
        if(file_exists($cookie_saver->file($this->site)))
            $this->session->options['cookies'] = $cookie_saver->load_cookies($this->site);
    }

    function __destruct()
    {
        $cookie_saver = new cookie_saver();
        $cookie_saver->save_cookies($this->session->options['cookies'], $this->site);
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
        if(isset($this->common['not_utf8']))
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
        if(isset($this->common['not_utf8']))
            $data = utils::utf8_decode_array($data);
        $response = $this->session->post($url, $headers, $data, $options);
        $response->throw_for_status();
        if(isset($this->common['not_utf8']))
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
        $login_file = sprintf(__DIR__.'/../logins/%s.json', $this->site);
        if(!file_exists($login_file))
            throw new FileNotFoundException($login_file);
        else
            return json_decode(file_get_contents($login_file), true);
    }

    /**
     * @throws FileNotFoundException
     * @throws LoginFailedException
     * @throws Requests_Exception
     * @throws Requests_Exception_HTTP
     */
    public function login()
    {
        $template = $this->get_template('login');
        $login = $this->get_login();

        $data = array($template['username']=>$login['username'], $template['password']=>$login['password']);
        $response = $this->session->post($this->common['url'].'/'.$template['url'], array(), $data);
        $response->throw_for_status();

        $logged_in = $this->is_logged_in($response->body);

        if($logged_in===false)
            throw new LoginFailedException();
        elseif($logged_in===null)
            throw new LoginFailedException('Neither success or error text was found');
    }

    /**
     * @param $body
     * @return bool|null
     * @throws FileNotFoundException
     */
    public function is_logged_in($body)
    {
        $template = $this->get_template('login');
        if(strpos($body, $template['error_text'])!==false)
            return false;
        elseif(strpos($body, $template['success_text'])!==false)
            return true;
        else
            return null;
    }

    /**
     * @param array $args Array with the fields torrentfile, title and description, optionally mediainfo
     * @param string $topic
     * @return array|mixed
     * @throws FileNotFoundException
     */
    public function upload($args, $topic)
    {
        $template = $this->get_template('upload');
        $required_fields = array('torrentfile', 'title', 'description');
        $optional_fields = array('mediainfo');
        if(!file_exists($args['torrentfile']))
            throw new FileNotFoundException($args['torrentfile']);
        $args['torrentfile'] = new CURLFile($args['torrentfile']);

        $postdata = array();
        foreach($required_fields as $field)
        {
            if(!isset($args[$field]))
                throw new InvalidArgumentException('Missing required field: '.$field);
            $postdata[$template[$field]] = $args[$field];
        }
        foreach ($optional_fields as $field)
        {
            if(isset($args[$field]) && isset($template[$field]))
                $postdata[$template[$field]] = $args[$field];
            elseif(isset($template[$field]))
                $postdata[$template[$field]] = '';
        }


        $postdata += $template['static_fields'];
        $postdata += $this->get_template($topic);

        return $postdata;
    }

    /**
     * @param $postdata
     * @return Requests_Response
     * @throws FileNotFoundException
     * @throws Requests_Exception
     */
    function send_upload($postdata)
    {
        $template = $this->get_template('upload');
        return $this->post($template['url'], array('Content-Type'=>'multipart/form-data'), $postdata);
    }

    function get_title($body)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($body);
        $title = $dom->getElementsByTagName('title');
        if($title->length>0)
            return $title->item(0)->textContent;
        else
            return '';
    }
}