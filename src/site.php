<?php


namespace torrentupload;


use datagutten\Requests_extensions\cookie_saver;
use datagutten\tools\files\files;
use Dotenv\Dotenv;
use FileNotFoundException;
use torrentupload\exceptions\UploadFailedException;
use WpOrg\Requests;

abstract class site
{
    /**
     * @var string Logged in user name
     */
    public string $user_name;
    /**
     * @var string Logged in user id
     */
    public string $user_id;
    public static string $cookie_folder;
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
    protected string $site_folder;
    public Requests\Session $session;

    /**
     * @var array Post data to be used in HTTP request
     */
    private array $post_fields;

    /**
     * @var string Folder to save cache data
     */
    protected string $cache_path;

    /**
     * site constructor.
     * @throws FileNotFoundException
     */
    public function __construct()
    {
        static::load_env();
        $this->cache_path = files::path_join($_ENV['CACHE_PATH'], static::$site_slug);
        @mkdir($this->cache_path, recursive: true);
        $this->cache_path = realpath($this->cache_path);

        $site = static::$site_slug;
        $site_folder = files::path_join(__DIR__, '..', 'templates', $site);
        if (!file_exists($site_folder))
            throw new FileNotFoundException($site_folder);
        $this->site_folder = realpath($site_folder);
        $options = array();

        $this->session = new Requests\Session(static::$site_url, array(), array(), $options);
        static::$cookie_folder = files::path_join($this->cache_path, 'cookies');
        $cookie_saver = new cookie_saver(static::$cookie_folder);
        if (file_exists($cookie_saver->file(static::$site_slug)))
            $this->session->options['cookies'] = $cookie_saver->load_cookies(static::$site_slug);
    }

    function __destruct()
    {
        $cookie_saver = new cookie_saver(static::$cookie_folder);
        $cookie_saver->save_cookies($this->session->options['cookies'], static::$site_slug);
    }

    public function multipart_hook($ch): void
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_fields);
    }

    /**
     * Load environment variables from files
     * @return void
     */
    public static function load_env(): void
    {
        $config_dir = files::path_join(__DIR__, '..', 'config');
        Dotenv::createImmutable($config_dir, ['common.env'])->safeLoad();
        Dotenv::createImmutable($config_dir, [sprintf('%s.env', static::$site_slug), 'common.env'])->load();
    }

    /**
     * @param string $url
     * @param array $headers
     * @param array $options
     * @return Requests\Response
     * @throws Requests\Exception
     */
    public function get(string $url, array $headers = [], array $options = []): Requests\Response
    {
        $response = $this->session->get($url, $headers, $options);
        $response->throw_for_status();
        if (!static::$use_utf8)
            $response->body = utf8_encode($response->body);

        return $response;
    }

    /**
     * Send a POST request with multipart/form-data allowing file uploads
     * @param string $url URL
     * @param array $data POST data
     * @return Requests\Response
     * @throws Requests\Exception
     */
    public function post(string $url, array $data = []): Requests\Response
    {
        if (!static::$use_utf8)
            $data = utils::utf8_decode_array($data);

        $hooks = new Requests\Hooks();
        $hooks->register('curl.before_send', [$this, 'multipart_hook']);
        $this->post_fields = $data;
        $response = $this->session->post($url, array('Content-Type' => 'multipart/form-data'), $data, [
            'transport' => Requests\Transport\Curl::class,
            'hooks' => $hooks
        ]);
        file_put_contents(files::path_join($this->cache_path, 'response.txt'), $response->body);

        $response->throw_for_status();
        if (!static::$use_utf8)
            $response->body = utf8_encode($response->body);
        return $response;
    }

    /**
     * Get upload template
     * @param $topic
     * @return array
     * @throws UploadFailedException
     */
    public function get_template($topic): array
    {
        $template_file = sprintf('%s/%s.json', $this->site_folder, $topic);
        if (!file_exists($template_file))
            throw new UploadFailedException("Invalid category $topic");
        else
            return json_decode(file_get_contents($template_file), true);
    }

    public function autoload_path(string $file = null): string
    {
        if (!empty($file))
            return files::path_join(realpath($_ENV['TORRENT_AUTOLOAD_PATH']), $file);
        else
            return $_ENV['TORRENT_AUTOLOAD_PATH'];
    }
}