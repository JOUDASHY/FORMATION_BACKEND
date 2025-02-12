const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const { Server: WebSocketServer } = require('ws');
const cors = require('cors');
const bodyParser = require('body-parser');

const app = express();
const server = http.createServer(app);
const PORT = 80;

// Middleware
app.use(cors());
app.use(express.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

// WebSocket pour notifications
const wssNotif = new WebSocketServer({ noServer: true });
const clients = {};

wssNotif.on('connection', (ws, req) => {
    const userId = req.headers['sec-websocket-protocol'];
    if (!userId) {
        ws.close();
        console.error('User ID not provided, connection closed.');
        return;
    }
    clients[userId] = ws;
    console.log(`User connected to notif: ${userId}`);

    ws.on('close', () => {
        delete clients[userId];
        console.log(`User disconnected from notif: ${userId}`);
    });

    ws.on('error', (error) => {
        console.error(`WebSocket error for user ${userId}:`, error);
    });
});

// WebSocket pour suivi des utilisateurs en ligne
const wssOnline = new WebSocketServer({ server });
let onlineUsers = new Map();

wssOnline.on('connection', (ws) => {
    console.log('Client connected to online users');

    ws.on('message', (message) => {
        const { userId } = JSON.parse(message);
        if (userId) {
            onlineUsers.set(userId, ws);
            broadcastOnlineUsers();
            console.log(`${userId} is online`);
        }
    });

    ws.on('close', () => {
        const userId = [...onlineUsers.entries()].find(([, client]) => client === ws)?.[0];
        if (userId) {
            onlineUsers.delete(userId);
            broadcastOnlineUsers();
        }
        console.log('Client disconnected from online users');
    });
});

function broadcastOnlineUsers() {
    const userIds = Array.from(onlineUsers.keys());
    const message = JSON.stringify({ type: 'onlineUsers', users: userIds });

    onlineUsers.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
}

// Gestion de l'upgrade WebSocket pour `/notif`
server.on('upgrade', (request, socket, head) => {
    if (request.url === '/notif') {
        wssNotif.handleUpgrade(request, socket, head, (ws) => {
            wssNotif.emit('connection', ws, request);
        });
    } else {
        socket.destroy();
    }
});

// Endpoint pour envoyer des notifications
app.post('/broadcast', (req, res) => {
    const { sender_id, receiver_id, message, attachment, created_at } = req.body;

    const fullMessage = JSON.stringify({
        sender_id,
        receiver_id,
        message,
        created_at,
        attachment,
    });

    console.log('Broadcasting message:', fullMessage);

    if (clients[receiver_id]) {
        clients[receiver_id].send(fullMessage);
        console.log(`Message sent to user: ${receiver_id}`);
    } else {
        console.log(`Receiver ${receiver_id} not connected`);
    }

    res.status(200).send('Message broadcasted');
});

// Socket.io pour gestion de conférence WebRTC
const io = new Server(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST'],
    },
});

let activeConference = false;
let rooms = {};

io.on('connection', (socket) => {
    console.log('User connected:', socket.id);

    socket.on('checkConferenceStatus', (callback) => {
        callback(activeConference);
    });

    socket.on('checkRoomAvailability', (roomName, callback) => {
        callback(!rooms[roomName]);
    });

    socket.on('broadcaster', (roomName) => {
        if (!roomName || rooms[roomName]) return;
        rooms[roomName] = { broadcaster: socket.id, watchers: [] };
        socket.join(roomName);
        io.to(roomName).emit('broadcaster', roomName);
        activeConference = true;
    });

    socket.on('watcher', (roomName) => {
        const room = rooms[roomName];
        if (room && room.broadcaster) {
            room.watchers.push(socket.id);
            socket.join(roomName);
            socket.to(room.broadcaster).emit('watcher', socket.id);
        }
    });

    socket.on('offer', (id, message) => {
        socket.to(id).emit('offer', socket.id, message);
    });

    socket.on('answer', (id, message) => {
        socket.to(id).emit('answer', socket.id, message);
    });

    socket.on('candidate', (id, message) => {
        socket.to(id).emit('candidate', socket.id, message);
    });

    socket.on('endConference', (roomName) => {
        delete rooms[roomName];
        io.to(roomName).emit('endConference');
        activeConference = false;
    });

    socket.on('disconnect', () => {
        for (const roomName in rooms) {
            const room = rooms[roomName];
            if (room.watchers.includes(socket.id)) {
                room.watchers = room.watchers.filter(id => id !== socket.id);
                socket.to(room.broadcaster).emit('disconnectPeer', socket.id);
            }
            if (room.broadcaster === socket.id) {
                delete rooms[roomName];
                io.to(roomName).emit('endConference');
                activeConference = false;
            }
        }
    });
});

server.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
