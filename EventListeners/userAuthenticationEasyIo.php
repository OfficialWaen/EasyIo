<?php

class userAuthenticationEasyIo extends EventEasyIoAbstract
{
    public function getSubscribedEvents()
    {
        return [EventEasyIoAbstract::PUBLISH];
    }

    public function getPermissionToListener()
    {
        return ['ROLE_CHAT'];
    }

    public function onEventListener($request)
    {
        if ($request && $request->data->password == 'toto') {
            $publisher = $this->getUser();
            $publisher->addPermission('ROLE_CHAT');

            $username = str_replace('<', "[", $request->data->username);
            $username = str_replace('>', "]", $username);

            $publisher->setUsername($username);
            $userConnected = [];
            foreach ($this->getListeners() as $user) {
                $userConnected[] = ['username' => $user->getUsername(), 'id' => $user->getId()];
            }
            $this->setResponseForPublisher(['accessChat' => true, 'userslist' => $userConnected]);
            $this->setResponseForListeners(['userConnected' => ['username' => $publisher->getUsername(), 'id' => $publisher->getId()]]);
        }
        else {
            $this->setResponseForPublisher(['accessChat' => false]);
        }
    }
}