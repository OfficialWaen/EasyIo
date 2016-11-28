# EasyIo - WebSockets for PHP.

### Introduction

<p>
Go to real time with only PHP!<br/> 
EasyIo allows you to make Websockets with much ease and while in PHP.
</p>

### Installation

```bash
### LINUX ###

$ git clone https://github.com/OfficialWaen/EasyIo.git <PATH YOUR PROJECT>
$ cd <PATH YOUR PROJECT>
$ cp EasyIo.bash /etc/init.d/EasyIo
$ chmod 755 /etc/init.d/EasyIo
$ update-rc.d EasyIo defaults 
$ /etc/init.d/EasyIo save
$ chown -R :www-data .
$ chmod -R 775 logs
```


```bash
### MAC ###

$ git clone https://github.com/OfficialWaen/EasyIo.git <PATH YOUR PROJECT>
$ cd <PATH YOUR PROJECT>
$ chown -R :staff .
$ chmod -R 775 logs
```

<p>For each restart of your mac, it is advisable to execute the following command : </p>

```bash
$ rm /tmp/*.easyIo
```

<p> Or set daily_clean_tmps_days="0" to the file "/etc/defaults/periodic.conf".</p>

#### ipTables

```bash
 iptables -t filter -A INPUT -p tcp --dport <PORT SERVER> -j ACCEPT
```

### Usage

<p>
Initialize the EasyIo server by providing it with the ip or DNS of the server and the port it can use.
The server will only run if a user log in.
The eventListeners are provided in the "eventListeners" method.
</p>

```php
$easyIo = new EasyIo('www.example.com', 11350);
$easyIo->eventListeners(['exampleListener', '...']);
$easyIo->run();
```

<p>
For EasyIo to find binded events, they are placed in a "EventListeners" subfolder.
EventListeners must inherit the abstract class "EventEasyIoAbstract" and their name must end with "EasyIo"
</p>

```php
class exampleListenerEasyIo extends EventEasyIoAbstract
{
    public function getSubscribedEvents()
    {
        return [EventEasyIoAbstract::PUBLISH];
    }

    public function getPermissionToListener()
    {
        return ['ROLE_USER'];
    }

    public function getPermissionToPublish()
    {
        return ['ROLE_USER'];
    }

    public function onEventListener($request)
    {
       ...
    }
}
```

<p>
Javascript side, just include the library "easyio-.* Js" and bind your events with an object include the properties "publisher" and "listener".
</p>

```html
<script src="easyIo-1.0.min.js"></script>
<script>
var easyIo = new EasyIo('www.example.com', 11350);

easyIo.ready(function () {
    console.log('easyIo is Ready !');

    easyIo.on('exampleListener', {
                publisher: function (response) {
                    //...
                 },
                listener: function (response) {
                    //...
                }
            });
});
</script>
```

### In case of errors

##### Error in connection establishment (JS)

<p>It is possible that this error is generated if your PHP service suddenly restarted. (We're not 
talking about the server restart, but only the PHP service).
To remedy this, simply type the following command :</p>

```bash
$ rm /tmp/*.easyIo
OR
$ rm /tmp/<port of server>.easyIo
```

### Documentation

 *****
#### EasyIo.php

###### Methods

 - function run($host, $port)

<p>Checks if the webSocket server is running, otherwise it runs it.</p>

<br/>

 - function stop($port)
 
<p>Will stop the webSocket server running on this port</p>
 
<br/> 
 
 - function eventListeners(array $event)
 
<p>Provided the active events table on the server.</p>
  
 *****
#### EventEasyIoAbstract.php
 
###### Properties
 
```php
     const CONNECT = 'connect';
     const PUBLISH = 'publish';
     const DISCONNECT = 'disconnect';
```

<p>Allows to feed the method "getSubscribedEvents" to select when the event should be called</p>
   
<br/> 

###### Methods
 
 - function onEventListener($request)
 
<p>Method of calling the event.</p>
  
<br/> 
 
 - function getSubscribedEvents()
 
<p>Designates when the event is to be called.</p>
 
<br/> 
 
- function getPermissionToPublish

<p>Refers to the permissions for publisher on this event.</p>
 
<br/> 
 
- function isPermissionToPublish()

<p>Checks if the user currently has permissions to publish.</p>
 
<br/> 
 
- function setResponseForPublisher

<p>Provided answers to return to publisher.</p>
 
<br/> 
 
- function getResponseForPublisher

<p>Returns the response that the publisher will receive.</p>
 
<br/> 
 
- function getPermissionToListener

<p>Refers to the permissions for listeners on this event.</p>
 
<br/> 
 
- function isPermissionToListener()

<p>Checks if the users currently has permissions to listen.</p>

- function setResponseForListeners

<p>Provided answers to return to listeners.</p>
 
<br/> 
 
- function getResponseForListeners

<p>Returns the response that the listeners will receive.</p>
 
<br/> 
 
- function setResponse()

<p>Provided same response to listeners and publishers.</p>
 
<br/> 
 
- function getListeners()

<p>Returns the listeners allowed to listen.</p>
 
<br/> 
 
- function getUser()

<p>Returns the publisher.</p>
