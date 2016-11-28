<?php

class userSendMessageEasyIo extends EventEasyIoAbstract
{
    public function getSubscribedEvents()
    {
        return [EventEasyIoAbstract::PUBLISH];
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

        $message = str_replace('<', "[", $request->data->message);
        $message = str_replace('>', "]", $message);

        $this->setResponseForListeners([
            'username' => $this->getUser()->getUsername(),
            'message'  => $message
        ]);
    }
}