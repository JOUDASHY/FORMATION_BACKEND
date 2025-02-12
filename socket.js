/**
 * Serveur fusionné :
 * - Express pour les endpoints HTTP
 * - ws pour la messagerie et le suivi des utilisateurs en ligne
 * - Socket.IO pour la visioconférence
 */

const express = require('express');
const http = require('http');
const bodyParser = require('body-parser');
const cors = require('cors');
const WebSocket = require('ws');
const { Server: SocketIOServer } = require('socket.io');

const app = express();
const PORT = 8110; // Change ce port si nécessaire

// Middleware Express
app.use(cors());
app.use(express.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

// Création du serveur HTTP
const server = http.createServer(app);

/* --------------------------------------------------------------------------
   Partie 1 : Serveur WebSocket (ws) pour messagerie et suivi des utilisateurs
-------------------------------------------------------------------------- */

// Map pour stocker les connexions WebSocket par userId
const wsClients = new Map();

// Fonction pour diffuser la liste des utilisateurs en ligne à tous les clients WS
function broadcastOnlineUsers() {
  const onlineUserIds = Array.from(wsClients.keys());
  const msg = JSON.stringify({ type: 'onlineUsers', users: onlineUserIds });
  wsClients.forEach((client) => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(msg);
    }
  });
}

// Création du serveur WebSocket attaché au serveur HTTP
const wss = new WebSocket.Server({ server });

// Lorsqu'un client se connecte via WebSocket
wss.on('connection', (ws, req) => {
  console.log('Nouvelle connexion WS');

  // On tente de récupérer l'ID utilisateur depuis l'en-tête 'sec-websocket-protocol'
  let userId = req.headers['sec-websocket-protocol'];

  // Si aucun userId n'est fourni dans l'en-tête, on attend qu'une première
  // transmission JSON contenant { "userId": "..." } soit envoyée par le client
  if (!userId) {
    ws.once('message', (data) => {
      try {
        const parsed = JSON.parse(data);
        if (parsed.userId) {
          userId = parsed.userId;
          wsClients.set(userId, ws);
          console.log(`Utilisateur enregistré via message : ${userId}`);
          broadcastOnlineUsers();
        }
      } catch (err) {
        console.error('Erreur lors du parsing du message de registration :', err);
      }
    });
  } else {
    wsClients.set(userId, ws);
    console.log(`Utilisateur connecté via header : ${userId}`);
    broadcastOnlineUsers();
  }

  // Traitement d'autres messages éventuels
  ws.on('message', (data) => {
    try {
      const parsed = JSON.parse(data);
      // Ici, vous pouvez traiter d'autres types de messages si besoin.
      // Par exemple, si parsed.type === 'message', etc.
    } catch (err) {
      console.error('Erreur lors du parsing d’un message WS :', err);
    }
  });

  // À la déconnexion du client, on le retire de la map et on met à jour la liste en ligne
  ws.on('close', () => {
    if (userId && wsClients.has(userId)) {
      wsClients.delete(userId);
      console.log(`Connexion WS fermée pour l’utilisateur : ${userId}`);
      broadcastOnlineUsers();
    }
  });

  ws.on('error', (error) => {
    console.error(`Erreur WS pour l’utilisateur ${userId} :`, error);
  });
});

/* --------------------------------------------------------------------------
   Endpoint Express pour diffuser un message (utilisé par Laravel via cURL)
-------------------------------------------------------------------------- */

app.post('/broadcast', (req, res) => {
  const { sender_id, receiver_id, message, attachment, created_at } = req.body;

  const fullMessage = {
    sender_id,
    receiver_id,
    message,
    attachment,
    created_at: created_at || new Date().toISOString(),
  };

  console.log('Broadcasting message:', JSON.stringify(fullMessage));

  if (wsClients.has(receiver_id)) {
    wsClients.get(receiver_id).send(JSON.stringify(fullMessage));
    console.log(`Message sent to user: ${receiver_id}`);
    res.status(200).send({ status: 'Message broadcasted' });
  } else {
    console.log(`Receiver ${receiver_id} not connected`);
    res.status(404).send({ status: 'User not connected' });
  }
});

/* --------------------------------------------------------------------------
   Partie 2 : Serveur Socket.IO pour visioconférence
-------------------------------------------------------------------------- */

const io = new SocketIOServer(server, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST'],
  },
});

let activeConference = false;
let rooms = {};

io.on('connection', (socket) => {
  console.log(`Socket.IO : Utilisateur connecté : ${socket.id}`);

  socket.on('checkConferenceStatus', (callback) => {
    console.log("Socket.IO : Vérification du statut de la conférence...");
    callback(activeConference);
  });

  socket.on('checkRoomAvailability', (roomName, callback) => {
    if (rooms[roomName]) {
      callback(false);
    } else {
      callback(true);
    }
  });

  socket.on('broadcaster', (roomName) => {
    if (!roomName) {
      console.log("Socket.IO : Nom de salle invalide pour broadcaster");
      return;
    }
    if (!rooms[roomName]) {
      rooms[roomName] = { broadcaster: socket.id, watchers: [] };
      socket.join(roomName);
      io.to(roomName).emit('broadcaster', roomName);
      console.log(`Socket.IO : Broadcaster lancé dans la salle : ${roomName}`);
      if (!activeConference) {
        activeConference = true;
        console.log("Socket.IO : Une conférence a démarré.");
      }
    } else {
      console.log(`Socket.IO : La salle ${roomName} est déjà occupée`);
    }
  });

  socket.on('watcher', (roomName) => {
    if (!roomName) {
      console.log("Socket.IO : Nom de salle invalide pour watcher");
      return;
    }
    const room = rooms[roomName];
    if (room && room.broadcaster) {
      room.watchers.push(socket.id);
      socket.join(roomName);
      socket.to(room.broadcaster).emit('watcher', socket.id);
      console.log(`Socket.IO : Watcher ${socket.id} a rejoint la salle : ${roomName}`);
    } else {
      console.log(`Socket.IO : La salle ${roomName} n’est pas disponible pour les watchers`);
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
    if (rooms[roomName]) {
      delete rooms[roomName];
      io.to(roomName).emit('endConference');
      console.log(`Socket.IO : La salle ${roomName} est maintenant libre`);
      activeConference = false;
      console.log("Socket.IO : La conférence est terminée.");
    } else {
      console.log(`Socket.IO : La salle ${roomName} n'existe pas`);
    }
  });

  socket.on('disconnect', () => {
    console.log(`Socket.IO : Utilisateur déconnecté : ${socket.id}`);
    // Nettoyer les rooms si nécessaire
    Object.keys(rooms).forEach((roomName) => {
      const room = rooms[roomName];
      if (room.watchers.includes(socket.id)) {
        room.watchers = room.watchers.filter((id) => id !== socket.id);
        socket.to(room.broadcaster).emit('disconnectPeer', socket.id);
      }
      if (room.broadcaster === socket.id) {
        delete rooms[roomName];
        io.to(roomName).emit('endConference');
        console.log(`Socket.IO : La salle ${roomName} est fermée car le broadcaster s'est déconnecté`);
        activeConference = false;
        console.log("Socket.IO : La conférence est terminée suite à la déconnexion du broadcaster.");
      }
    });
  });
});

/* --------------------------------------------------------------------------
   Lancement du serveur HTTP
-------------------------------------------------------------------------- */

server.listen(PORT, () => {
  console.log(`Serveur lancé sur le port ${PORT}`);
});
