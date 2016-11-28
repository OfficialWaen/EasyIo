<?php

abstract class EventEasyIoAbstract
{
    private $user;
    private $responseForListeners;
    private $responseForPublisher;

    const CONNECT = 'connect';
    const PUBLISH = 'publish';
    const DISCONNECT = 'disconnect';

    public function init($user)
    {
        $this->user                 = $user;
        $this->responseForListeners = null;
        $this->responseForPublisher = null;
    }

    abstract public function onEventListener($request);

    abstract public function getSubscribedEvents();


    public function getPermissionToPublish()
    {
        return null;
    }

    public function isPermissionToPublish()
    {
        if ($this->getPermissionToPublish()) {
            $permissionIntersect = array_intersect($this->getPermissionToPublish(), $this->getUser()->getPermission());

            return (count($permissionIntersect) > 0);
        }

        return true;
    }

    public function setResponseForPublisher($responseForPublisher)
    {
        $this->responseForPublisher['event']        = get_class($this);
        $this->responseForPublisher['forPublisher'] = true;
        $this->responseForPublisher['data']         = $responseForPublisher;
    }

    public function getResponseForPublisher()
    {
        return $this->responseForPublisher;
    }


    public function getPermissionToListener()
    {
        return null;
    }

    public function isPermissionToListener($user)
    {
        if ($this->getPermissionToListener()) {
            $permissionIntersect = array_intersect($this->getPermissionToListener(), $user->getPermission());

            return (count($permissionIntersect) > 0);
        }

        return true;
    }

    public function setResponseForListeners($responseForListeners)
    {
        $this->responseForListeners['event']        = get_class($this);
        $this->responseForListeners['forPublisher'] = false;
        $this->responseForListeners['data']         = $responseForListeners;
    }

    public function getResponseForListeners()
    {
        return $this->responseForListeners;
    }


    public function setResponse($response)
    {
        $this->setResponseForListeners($response);
        $this->setResponseForPublisher($response);
    }

    public function getListeners()
    {
        $users = [];

        foreach (EasyIoServer::$users as $key => $user) {
            if ($this->user == $key) continue;
            if (!$this->isPermissionToListener($user)) continue;

            array_push($users, $user);
        }

        return $users;
    }

    public function dump($text)
    {
        global $logs;
        file_put_contents($logs, "\n\nDUMP(" . json_encode($text) . ")", FILE_APPEND | LOCK_EX);
    }

    public function getUser()
    {
        return EasyIoServer::$users[$this->user];
    }
}