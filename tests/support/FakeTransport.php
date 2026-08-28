<?php
class DNAHosting_FakeTransport
{
    public $calls = array();
    private $queue = array();

    public function push($status, $body)
    {
        $this->queue[] = array('status' => $status, 'body' => $body, 'error' => '');
        return $this;
    }

    public function pushError($curlError)
    {
        $this->queue[] = array('status' => 0, 'body' => '', 'error' => $curlError);
        return $this;
    }

    public function __invoke($method, $url, $headers, $body, $timeout)
    {
        $this->calls[] = array(
            'method'  => $method,
            'url'     => $url,
            'headers' => $headers,
            'body'    => $body,
            'timeout' => $timeout,
        );
        if (!$this->queue) {
            return array('status' => 0, 'body' => '', 'error' => 'FakeTransport: kuyruk bos');
        }
        return array_shift($this->queue);
    }

    public function lastCall()
    {
        return $this->calls ? $this->calls[count($this->calls) - 1] : null;
    }
}
