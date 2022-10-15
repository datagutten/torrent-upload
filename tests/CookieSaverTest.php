<?php


namespace torrentupload\tests;


use datagutten\Requests_extensions\cookie_saver;
use PHPUnit\Framework\TestCase;
use WpOrg\Requests;

class CookieSaverTest extends TestCase
{
    /**
     * @var Requests\Session
     */
    public $session;
    /**
     * @var cookie_saver
     */
    public $saver;

    public $file;

    public function setUp(): void
    {
        $this->session = new Requests\Session('https://httpbin.org');
        $this->saver = new cookie_saver();
        $this->file = $this->saver->file('test');
        if(file_exists($this->file))
            unlink($this->file);
    }

    function testSaveCookie()
    {
        $this->session->get('https://httpbin.org/cookies/set/test/value');
        $this->saver->save_cookies($this->session->options['cookies'], 'test');
        $this->assertFileExists($this->file);
        $string = file_get_contents($this->file);
        $this->assertEquals('{"test":"value"}', $string);
    }

    /**
     * @throws FileNotFoundException
     */
    function testLoadCookie()
    {
        file_put_contents($this->file, '{"test":"value"}');
        $jar = $this->saver->load_cookies('test');
        $this->session->options['cookies'] = $jar;
        $response = $this->session->get('https://httpbin.org/cookies');
        $cookies = json_decode($response->body, true);
        $this->assertArrayHasKey('test', $cookies['cookies']);
        $this->assertEquals('value', $cookies['cookies']['test']);
    }

    function tearDown(): void
    {
        unlink($this->file);
    }
}