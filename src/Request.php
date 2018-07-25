<?php
declare(strict_types=1);


namespace Request;


class Request
{
    private $ch;
    private $baseUrl = '';

    private $headers = [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Encoding' => 'gzip, deflate',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Cache-Control' => 'no-cache',
        'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
//        'Referer' => 'https://www.google.com',
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36',
        'cookie' => '',
    ];

    public function __construct()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 1);
    }

    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
    }

    public function authBasic(string $username, string $password)
    {
        curl_setopt($this->ch, CURLOPT_USERPWD, $username . ":" . $password);
    }

    public function get(string $url, array $queryParams = [])
    {
        $url = $this->buildUrl($url, $queryParams);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->mergeHeaders());
        curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip');
        return $this->exec();
    }

    public function post(string $url, array $data = [], array $queryParams = [])
    {
        $url = $this->buildUrl($url, $queryParams);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->mergeHeaders());
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip');
        return $this->exec();
    }

    private function exec()
    {
        $result = curl_exec($this->ch);
        $result = json_decode($result, true);
//        curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        return $result;
    }

    private function buildUrl($url, array $queryParams = [])
    {
        $url = $this->baseUrl . $url;
        if (count($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        return $url;
    }

    private function mergeHeaders(array $headers = [])
    {
        $merged = array_merge($this->headers, $headers);
        $result = [];
        foreach ($merged as $name => $value) {
            $result[] = $name . ': ' . $value;
        }
        return $result;
    }
}