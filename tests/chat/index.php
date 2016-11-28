<?php

require_once '../../EasyIo.php';

$easyIo = new EasyIo();
$easyIo->eventListeners(['userAuthentication', 'userSendMessage', 'userDisconnected']);
$easyIo->run('localhost', 11347); // To personalize

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <title>Easy Io Bêta 0.1 CHAT</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css"/>
</head>
<body class="container-fluid">
<h1>Easy Io Bêta 0.1 CHAT</h1>
<div id="content_auth" hidden>
    <form id="auth" class="col-md-12">
        <div class="row">
            <input id="auth_username" type="text" placeholder="Username" class="col-md-offset-3 col-md-6 col-sm-offset-2 col-sm-8 well"/>
        </div>
        <div class="row">
            <input id="auth_password" type="password" placeholder="Password" class="col-md-offset-3 col-md-6 col-sm-offset-2 col-sm-8 well"/>
        </div>
        <button type="submit" class="btn btn-primary">Connexion</button>
    </form>
</div>
<div id="content_chat" hidden>
    <div id="list_user" class="col-sm-2">
        <div class="well">
            <ul>
            </ul>
        </div>
    </div>
    <div id="chat" class="col-sm-10">
        <div class="well">
            <ul>
            </ul>
        </div>
    </div>
    <form id="message" class="col-sm-12">
        <input type="text" placeholder="Message" class="col-sm-12 well"/>
    </form>
</div>
<script  type="text/javascript"  src="jquery-3.1.1.min.js"></script>
<script  type="text/javascript"  src="easyIo-1.0.min.js"></script>
<script  type="text/javascript" >

    $(function () {

        /**
         *  PASSWORD : toto
         **/

        var easyIo = new EasyIo('localhost', 11347); // To personalize

        easyIo.ready(function () {

            console.log('easyIo is Ready !');

            var scroll = $('#chat').find('div:first-child');

            var messageChat = function (msg) {
                $('#chat').find('ul').append('<li>' + msg + '</li>');
                var height = scroll.prop('scrollHeight');
                scroll.scrollTop(height);
            };

            var addUserList = function (user) {
                $('#list_user').find('ul').append('<li data-user="' + user.id + '">' + user.username + '</li>');
            };

            var removeUserList = function (user) {
                $('#list_user').find('[data-user="' + user.id + '"]').remove();
                messageChat('<span class="text-red"><strong>' + user.username + '</strong> has just disconnected.</span>');
            };

            /*******************************************************
             *                          Bind                       *
             *******************************************************/

            easyIo.on('userAuthentication', {
                publisher: function (response) {
                    if (response.data.accessChat) {
                        var data = {
                            'username': $('#auth_username').val(),
                            'password': $('#auth_password').val()
                        }
                        if (!sessionStorage.getItem('easyIoLogin')) sessionStorage.setItem('easyIoLogin', JSON.stringify(data));

                        var userslist = response.data.userslist;
                        for (var index in userslist) {
                            addUserList(userslist[index]);
                        }

                        messageChat('<span class="text-green">You are connected.</span>');
                        $('#content_auth').hide();
                        $('#content_chat').show();
                    }
                },
                listener: function (response) {
                    messageChat('<span class="text-purple"><strong>' + response.data.userConnected.username + '</strong> has just logged in.</span>');
                    addUserList(response.data.userConnected);
                }
            });

            easyIo.on('userSendMessage', {
                listener: function (response) {
                    messageChat('<span><strong>' + response.data.username + '</strong>  : ' + response.data.message + '</span>');
                }
            });

            easyIo.on('userDisconnected', {
                listener: function (response) {
                    removeUserList(response.data.userDisconnected);
                }
            });

            /*******************************************************
             *                          Auth                       *
             *******************************************************/

            if (sessionStorage.getItem('easyIoLogin')) easyIo.send('userAuthentication', JSON.parse(sessionStorage.getItem('easyIoLogin')));
            else $('#content_auth').show();

            $('#auth').submit(function (event) {
                event.preventDefault();
                sessionStorage.removeItem('easyIoLogin');
                var username = $('#auth_username').val();
                var data = {'username': username, 'password': $('#auth_password').val()};
                easyIo.send('userAuthentication', data);
            });

            /*******************************************************
             *                       Message                       *
             *******************************************************/

            $('#message').submit(function (event) {
                event.preventDefault();
                var msg = $('#message').find('input').val();
                messageChat('<span class="text-blue"><strong>Vous</strong> : ' + msg + ' </span>');
                easyIo.send('userSendMessage', {'message': msg});
                msg = null;
                $('#message').find('input').val(msg);
            });
        });
    });
</script>
</body>
</html>
