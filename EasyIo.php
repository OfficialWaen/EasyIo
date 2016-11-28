<?php

class EasyIo
{
    private $event = [];

    public function run($host, $port)
    {
        $serverPath = '/tmp/' . $port . '.easyIo';

        if (!file_exists($serverPath)) {

            $server = ['host' => $host, 'port' => $port, 'events' => $this->event];
            file_put_contents($serverPath, json_encode($server));

            $path = dirname(__FILE__) . '/EasyIoServer.php';

            $bash = 'bash -c "php -q ' . $path . ' ' . $port
                . ' && rm ' . $serverPath . '"'
                . ' > /dev/null 2>&1 &';

            shell_exec($bash);

            return true;
        }

        return false;
    }

    public function stop($port)
    {
        shell_exec('rm /tmp/' . $port . '.easyIo');
    }

    public function eventListeners(array $event)
    {
        $this->event = $event;
    }
}