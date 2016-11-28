function EasyIo(host, port) {

    var self = this;
    this.socket;
    this.eventListeners = [];

    this.init = function (host, port) {
        self.socket = new WebSocket('ws://' + host + ':' + port + '');
    };

    this.init(host, port);

    this.ready = function (callback) {
        self.ready = callback;
    };

    this.error = function (callback) {
        self.error = callback;
    };

    this.close = function (callback) {
        self.close = callback;
    };

    this.on = function (event, action) {
        if (!self.eventListeners[event]) self.eventListeners[event + 'EasyIo'] = [];
        self.eventListeners[event + 'EasyIo'].push(action);
        console.log(self.eventListeners[event + 'EasyIo']);
    };

    this.off = function (event) {
        delete self.eventListeners[event + 'EasyIo'];
    };

    this.send = function (event, data) {
        self.socket.send(JSON.stringify({event: event, data: data}));
    };

    this.socket.onmessage = function (response) {
        var data = JSON.parse(response.data);
        var f = (data.forPublisher) ? 'publisher' : 'listener';
        if (self.eventListeners[data.event]) {
            for (var index in self.eventListeners[data.event]) {
                if (self.eventListeners[data.event][index][f]) self.eventListeners[data.event][index][f](data);
            }
        }
    };

    this.socket.onopen = function (e) {
        self.ready();
    };

    this.socket.onclose = function (e) {
        self.close();
    };

    this.socket.onerror = function (e) {
        self.error();
    };

}