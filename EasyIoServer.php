<?php

require_once 'EventEasyIoAbstract.php';


$date = new DateTime();
$date = $date->format(DateTime::ATOM);
$logs = dirname(__FILE__) . '/logs/EasyIoServer' . $date . '.log';

function exceptionHandler($exception)
{
    global $logs;
    file_put_contents($logs, "\n\n\n-> ERROR PHP : " .
        "\n\t Error : " . $exception->getCode() . " " . $exception->getMessage() .
        "\n\t Location : " . $exception->getFile() . " (LINE " . $exception->getLine() . ")", FILE_APPEND | LOCK_EX);
    exit;
}

set_exception_handler('exceptionHandler');

$easyIoServer = new EasyIoServer($argv[1], $date);

class UserIo
{
    private $id;
    private $username;
    private $permission = [];
    private $socket;
    private $persist;
    private $data;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPermission()
    {
        return $this->permission;
    }

    public function addPermission($permission)
    {
        $this->permission[] = $permission;
    }

    public function removePermission($permission)
    {
        $key = array_search($permission, $this->permission, true);

        if ($key === false) {
            return false;
        }

        unset($this->permission[$key]);

        return true;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function setSocket($socket)
    {
        $this->socket = $socket;
    }

    public function getPersist()
    {
        return $this->persist;
    }

    public function setPersist($persist)
    {
        $this->persist = $persist;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}

class EasyIoServer
{
    private $sockets = [];
    private $master;
    public static $users = [];
    private $eventListeners;
    private $serverPath;

    const ERROR_BAD_REQUEST = ['code' => 1, 'title' => 'Bad request.', 'detail' => 'The request is not an object.'];
    const ERROR_PUBLISH_NOT_FOUND = ['code' => 2, 'title' => 'Publish not found.', 'detail' => 'the publish is not found'];
    const ERROR_PUBLISH_FORBIDDEN = ['code' => 3, 'title' => 'Forbidden.', 'detail' => 'You have no right to do this.'];

    public function __construct($port, $date)
    {
        $this->serverPath = '/tmp/' . $port . '.easyIo';

        if (file_exists($this->serverPath)) {
            $server        = @file_get_contents($this->serverPath);
            $server        = json_decode($server);
            $this->master  = $this->socketBuilder($server->host, $server->port);
            $this->sockets = [$this->master];
            $this->writeLog("-> RUN SERVER : " . $date . "\n-> HOST : " . $server->host . "\n-> PORT : " . $server->port . "\n");
            $this->cachedEventListeners($server->events);
            $this->run();
        }
        else {
            $this->writeLog("\nFAIL -> Server not found (\"" . $this->serverPath . "\").");
        }
    }

    public function cachedEventListeners($events)
    {
        function __autoload($class_name)
        {
            $path = dirname(__FILE__) . '/EventListeners/' . $class_name . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }

        foreach ($events as $nameEvent) {
            $class = $nameEvent . 'EasyIo';
            if (class_exists($class)) {
                $event = new $class();
                if ($event instanceof EventEasyIoAbstract) {
                    $subs = $event->getSubscribedEvents();
                    foreach ($subs as $sub) {
                        $this->eventListeners[$sub][$nameEvent] = $event;
                        $this->writeLog("\n-> EventListener : " . $sub . " => " . $nameEvent);
                    }
                }
                else {
                    $this->writeLog("\nFAIL -> EventListener : " . $nameEvent . " (EventListener doesn't inherit from the EventEasyIoAbstract class.)");
                }
            }
            else {
                $this->writeLog("\nFAIL -> EventListener : " . $nameEvent . " (EventListener not found.)");
            }
        }
    }

    public function run()
    {
        $time  = null;
        $write = null;

        while (true) {
            $read   = $this->sockets;
            $expect = $this->sockets;

            socket_select($read, $write, $expect, 0, 1000000);

            foreach ($read as $socket) {
                if ($socket == $this->master) {
                    $client = socket_accept($this->master);
                    if ($client < 0) continue;
                    else {
                        if(!socket_set_option($client, SOL_SOCKET, SO_KEEPALIVE, 1))
                        {
                            $this->writeLog("\nFAIL ->  Unable to set option on socket.");
                            break;
                        }
                        $this->connect($client);
                    }
                }
                else {
                    if (@socket_recv($socket, $buffer, 2048, 0) == 0) {
                        $this->disconnect($socket);
                    }
                    else {
                        $user = $this->getUserBySocket($socket);
                        ($user->getPersist()) ? $this->publishProcess($user, $buffer) : $this->persist($user, $buffer);
                    }
                }
            }

            if (count(static::$users) == 0) {
                $timestamp = time();
                if (!$time) $time = $timestamp;
                elseif (($time + 60) < $timestamp) break;
            }
            else {
                $time = null;
            }

            if (!file_exists($this->serverPath)) break;
        }

        $this->writeLog("\n\n-> Shutdown : Automatic.");
        socket_close($this->sockets[0]);
    }

    private function connectedDisconnectedProcess($user, $forConnected)
    {
        $for = ($forConnected) ? 'connect' : 'disconnect';
        foreach ($this->eventListeners[$for] as $event) {
            $this->broadcast($user, $event);
        }
    }

    private function publishProcess($user, $buffer)
    {
        $request = json_decode($this->bufferDecode($buffer));

        if (is_object($request)) {
            if (array_key_exists($request->event, $this->eventListeners['publish'])) {
                $event = $this->eventListeners['publish'][$request->event];
                $this->broadcast($user, $event, $request);

                return;
            }
            else $error = EasyIoServer::ERROR_PUBLISH_NOT_FOUND;
        }
        else $error = EasyIoServer::ERROR_BAD_REQUEST;

        $this->emit([$user], ['error' => $error]);
    }

    private function broadcast($user, $event, $request = null)
    {
        $event->init($user->getId());
        if ($event->isPermissionToPublish()) {
            $event->onEventListener($request);
            if ($event->getResponseForListeners()) $this->emit($event->getListeners(), $event->getResponseForListeners());
            if ($event->getResponseForPublisher()) $this->emit([$user], $event->getResponseForPublisher());
        }
        else {
            $this->emit([$user], EasyIoServer::ERROR_PUBLISH_FORBIDDEN);
        }
    }

    private function emit($users, $response)
    {
        $response = $this->bufferEncode($response);
        foreach ($users as $user) {
            @socket_write($user->getSocket(), $response, strlen($response));
        }
    }

    private function socketBuilder($address, $port)
    {
        $master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() failed");
        socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1) or die("socket_option() failed");
        socket_bind($master, $address, $port) or die("socket_bind() failed");
        socket_listen($master, 20) or die("socket_listen() failed");

        return $master;
    }

    private function connect($socket)
    {
        $user = new UserIo();
        $user->setId(uniqid());
        $user->setSocket($socket);
        static::$users[$user->getId()] = $user;

        array_push($this->sockets, $socket);
    }

    private function disconnect($socket)
    {
        $found = null;
        $user  = null;
        foreach (static::$users as $key => $user) {
            if ($user->getSocket() == $socket) {
                $found = $key;
                $user  = $user;
                break;
            }
        }

        $this->connectedDisconnectedProcess($user, false);

        if (!is_null($found)) {
            unset(static::$users[$found]);
        }
        $index = array_search($socket, $this->sockets);
        socket_close($socket);
        if ($index >= 0) {
            array_splice($this->sockets, $index, 1);
        }
    }

    private function getUserBySocket($socket)
    {
        $found = null;
        foreach (static::$users as $user) {
            if ($user->getSocket() == $socket) {
                $found = $user;
                break;
            }
        }

        return $found;
    }

    private function persist($user, $buffer)
    {

        $language = $agent = $resource = $host = $origin = $key = $persist = null;
        preg_match('#GET (.*?) HTTP#', $buffer, $match) && $resource = $match[1];
        preg_match("#Host: (.*?)\r\n#", $buffer, $match) && $host = $match[1];
        preg_match("#Sec-WebSocket-Key: (.*?)\r\n#", $buffer, $match) && $key = $match[1];
        preg_match("#Origin: (.*?)\r\n#", $buffer, $match) && $origin = $match[1];
        preg_match("#Accept-Language: (.*?)\r\n#", $buffer, $match) && $language = $match[1];
        preg_match("#User-Agent: (.*?)\r\n#", $buffer, $match) && $agent = $match[1];

        $key = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $persist =
            "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: WebSocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Accept-Language: " . $language . "\r\n" .
            "User-Agent: " . $agent . "\r\n" .
            "WebSocket-Origin: " . $origin . "\r\n" .
            "WebSocket-Location: ws://" . $host . $resource . "\r\n" .
            "Sec-WebSocket-Accept:" . $key . "\r\n" .
            "\r\n";

        socket_write($user->getSocket(), $persist, strlen($persist));
        $user->setPersist(true);

        $this->connectedDisconnectedProcess($user, true);

        return true;
    }

    private function bufferDecode($request)
    {
        $length = ord($request[1]) & 127;
        if ($length == 126) {
            $masks = substr($request, 4, 4);
            $data  = substr($request, 8);
        }
        elseif ($length == 127) {
            $masks = substr($request, 10, 4);
            $data  = substr($request, 14);
        }
        else {
            $masks = substr($request, 2, 4);
            $data  = substr($request, 6);
        }
        $request = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $request .= $data[$i] ^ $masks[$i % 4];
        }

        return $request;
    }

    private function bufferEncode($response)
    {
        $response = json_encode($response);
        $b1       = 0x80 | (0x1 & 0x0f);
        $length   = strlen($response);
        if ($length <= 125) $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536) $header = pack('CCn', $b1, 126, $length);
        elseif ($length >= 65536) $header = pack('CCNN', $b1, 127, $length);

        return $header . $response;
    }

    private function writeLog($text)
    {
        global $logs;
        file_put_contents($logs, $text, FILE_APPEND | LOCK_EX);
    }

}