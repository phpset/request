<?php
declare(strict_types=1);


namespace Request;


// https://github.com/phpset/request
class Request
{
    private static $ch = false;
    private $throwExceptions = true;
    private $baseUrl = '';
    private $lastResult = [];
    private $retryCount = 5;

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
        if (self::$ch != false) {
            return;
        }
        self::$ch = curl_init();
        $this->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->setOption(CURLOPT_FOLLOWLOCATION, 1);
        $this->setOption(CURLOPT_TIMEOUT_MS, 1000);
        $this->setOption(CURLOPT_HEADER, 1);
    }

    public function setOption($option, $value)
    {
        curl_setopt($this->getCh(), $option, $value);
    }

    public function setRetryCount(int $count)
    {
        $this->retryCount = $count;
    }

    public function setThrowExceptions($bool)
    {
        $this->throwExceptions = $bool;
    }

    private function getCh()
    {
        return self::$ch;
    }


    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
    }

    public function authBasic(string $username, string $password)
    {
        $this->setOption(CURLOPT_USERPWD, $username . ":" . $password);
    }

    public function get(string $url, array $queryParams = [])
    {
        $url = $this->buildUrl($url, $queryParams);
        $this->setOption(CURLOPT_URL, $url);
        $this->setOption(CURLOPT_HTTPHEADER, $this->mergeHeaders());
        $this->setOption(CURLOPT_ENCODING, 'gzip');
        return $this->exec();
    }

    public function post(string $url, array $data = [], array $queryParams = [])
    {
        $url = $this->buildUrl($url, $queryParams);
        $this->setOption(CURLOPT_URL, $url);
        $this->setOption(CURLOPT_HTTPHEADER, $this->mergeHeaders());
        $this->setOption(CURLOPT_POST, 1);
        $this->setOption(CURLOPT_POSTFIELDS, $data);
        $this->setOption(CURLOPT_ENCODING, 'gzip');
        return $this->exec();
    }

    private function exec()
    {
//        $startTime = microtime(true);
        $response = curl_exec($this->getCh());
        if ($response === false && $this->retryCount) {
            for ($atempt = 1; $atempt <= $this->retryCount && $response === false; $atempt++) {
                $response = curl_exec($this->getCh());
            }
        }
//        echo 'request in ' . floor((microtime(true) - $startTime) * 1000) . PHP_EOL;

        if ($response === false) {
            if ($this->throwExceptions) {
                throw new \Exception(curl_error($this->getCh()), curl_errno($this->getCh()));
            } else {
                $this->lastResult = [
                    'code' => (int)curl_getinfo($this->getCh(), CURLINFO_HTTP_CODE),
                    'headers' => false,
                    'body' => false,
                    'url' => curl_getinfo($this->getCh(), CURLINFO_EFFECTIVE_URL),
                ];
                return false;
            }
        }

        $header_size = curl_getinfo($this->getCh(), CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $this->lastResult = [
            'code' => (int)curl_getinfo($this->getCh(), CURLINFO_HTTP_CODE),
            'headers' => $headers,
            'body' => $body,
            'url' => curl_getinfo($this->getCh(), CURLINFO_EFFECTIVE_URL),
        ];

//        $body = json_decode($body, true);
        return $this->lastResult['body'];
    }

    public function lastCode()
    {
        return $this->lastResult['code'];
    }

    public function lastHeaders()
    {
        return $this->lastResult['headers'];
    }

    public function lastBody()
    {
        return $this->lastResult['body'];
    }

    public function lastUrl()
    {
        return $this->lastResult['url'];
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