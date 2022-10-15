<?php
namespace datagutten\Requests_extensions;
use WpOrg\Requests;
use FileNotFoundException;
use InvalidArgumentException;
use TypeError;

class cookie_saver
{
    public $folder;
    function __construct($folder = null)
    {
        if(empty($folder))
            $this->folder = __DIR__.'/cookies';
        else
            $this->folder = $folder;

        if(!file_exists($this->folder))
            mkdir($this->folder);
    }

    public function file($name)
    {
        if(!is_string($name))
            throw new InvalidArgumentException('Argument must be string');
        return sprintf('%s/%s.json', $this->folder, $name);
    }

    /**
     * @param Requests\Cookie\Jar $jar
     * @param string $file
     */
    function save_cookies($jar, $file) {
        $cookies = array();
        foreach($jar as $name=>$value)
        {
            if(is_a($value, 'WpOrg\\Requests\\Cookie'))
                $cookies[$value->name] = $value->value;
            elseif(is_string($value))
                $cookies[$name]=$value;
            else {
                var_dump($value);
                throw new TypeError('Invalid type');
            }
            //var_dump($value);
        }
        //$json = json_encode((array)$jar);
        //$json = substr($json, 24, -1);
        $json = json_encode($cookies);
        file_put_contents($this->file($file), $json);
    }

    /**
     * @param $file
     * @return Requests\Cookie\Jar Cookie jar
     * @throws FileNotFoundException
     */
    function load_cookies($file)
    {
        $file = $this->file($file);
        if(!file_exists($file))
            throw new FileNotFoundException($file);
        $json = file_get_contents($file);
        $cookies = json_decode($json, true);
        return new Requests\Cookie\Jar($cookies);
    }
}