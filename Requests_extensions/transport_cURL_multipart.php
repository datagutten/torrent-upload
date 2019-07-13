<?php
namespace datagutten\Requests_extensions;
use Requests_Transport_cURL;
use Requests;

class transport_cURL_multipart extends Requests_Transport_cURL
{
    /**
     * Setup the cURL handle for the given data
     *
     * @param string $url URL to request
     * @param array $headers Associative array of request headers
     * @param string|array $data Data to send either as the POST body, or as parameters in the URL for a GET/HEAD
     * @param array $options Request options, see {@see Requests::response()} for documentation
     */
    protected function setup_handle($url, $headers, $data, $options) {
        $options['hooks']->dispatch('curl.before_request', array(&$this->handle));

        // Force closing the connection for old versions of cURL (<7.22).
        if ( ! isset( $headers['Connection'] ) ) {
            $headers['Connection'] = 'close';
        }
        if(isset($headers['Content-Type']) && $headers['Content-Type']==='multipart/form-data') {
            $multipart_form = true;
        }
        else
            $multipart_form = false;

        $headers = Requests::flatten($headers);

        if (!empty($data)) {
            $data_format = $options['data_format'];

            if ($data_format === 'query') {
                $url = self::format_get($url, $data);
                $data = '';
            }
            elseif (!is_string($data) && !$multipart_form) {
                $data = http_build_query($data, null, '&');
            }
        }

        switch ($options['type']) {
            case Requests::POST:
                curl_setopt($this->handle, CURLOPT_POST, true);
                curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
                break;
            case Requests::HEAD:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $options['type']);
                curl_setopt($this->handle, CURLOPT_NOBODY, true);
                break;
            case Requests::TRACE:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $options['type']);
                break;
            case Requests::PATCH:
            case Requests::PUT:
            case Requests::DELETE:
            case Requests::OPTIONS:
            default:
                curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $options['type']);
                if (!empty($data)) {
                    curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
                }
        }

        // cURL requires a minimum timeout of 1 second when using the system
        // DNS resolver, as it uses `alarm()`, which is second resolution only.
        // There's no way to detect which DNS resolver is being used from our
        // end, so we need to round up regardless of the supplied timeout.
        //
        // https://github.com/curl/curl/blob/4f45240bc84a9aa648c8f7243be7b79e9f9323a5/lib/hostip.c#L606-L609
        $timeout = max($options['timeout'], 1);

        if (is_int($timeout) || $this->version < self::CURL_7_16_2) {
            curl_setopt($this->handle, CURLOPT_TIMEOUT, ceil($timeout));
        }
        else {
            curl_setopt($this->handle, CURLOPT_TIMEOUT_MS, round($timeout * 1000));
        }

        if (is_int($options['connect_timeout']) || $this->version < self::CURL_7_16_2) {
            curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT, ceil($options['connect_timeout']));
        }
        else {
            curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT_MS, round($options['connect_timeout'] * 1000));
        }
        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_REFERER, $url);
        curl_setopt($this->handle, CURLOPT_USERAGENT, $options['useragent']);
        if (!empty($headers)) {
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
        }
        if ($options['protocol_version'] === 1.1) {
            curl_setopt($this->handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }
        else {
            curl_setopt($this->handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        }

        if (true === $options['blocking']) {
            curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, array(&$this, 'stream_headers'));
            curl_setopt($this->handle, CURLOPT_WRITEFUNCTION, array(&$this, 'stream_body'));
            curl_setopt($this->handle, CURLOPT_BUFFERSIZE, Requests::BUFFER_SIZE);
        }
    }
}