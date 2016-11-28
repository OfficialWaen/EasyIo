<?php

class userDisconnectedEasyIo extends EventEasyIoAbstract
{
    public function getSubscribedEvents()
    {
        return [EventEasyIoAbstract::DISCONNECT];
    }

    public function getPermissionToListener()
    {
        return ['ROLE_CHAT'];
    }

    public function getPermissionToPublish()
    {
        return ['ROLE_CHAT'];
    }

    public function onEventListener($request)
    {
        $this->setResponseForListeners(['userDisconnected' => ['username' => $this->getUser()->getUsername(), 'id' => $this->getUser()->getId()]]);
    }
}