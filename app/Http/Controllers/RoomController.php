<?php
namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    // Affiche toutes les salles
    public function index()
    {
        $rooms = Room::all(); // Récupère toutes les salles
        return response()->json($rooms);
    }

    // Crée une nouvelle salle
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number',
            'capacity' => 'required|integer|min:1',
        ]);
    
        $room = Room::create($validatedData);
    
        return response()->json($room, 201);
    }
    
    

    // Affiche une salle spécifique par ID
    public function show($id)
    {
        $room = Room::find($id); // Trouve la salle par son ID
        if ($room) {
            return response()->json($room); // Retourne la salle
        }
        return response()->json(['message' => 'Room not found'], 404); // Si la salle n'existe pas
    }

    public function update(Request $request, $id)
    {
        $room = Room::find($id); // Trouve la salle par son ID
    
        if ($room) {
            // Validation des données avec une règle d'unicité sur room_number
            $validatedData = $request->validate([
                'room_number' => 'required|string|max:255|unique:rooms,room_number,' . $id, // Exclut l'ID actuel de la vérification
                'capacity' => 'required|integer|min:1',
            ], [
                'room_number.unique' => 'Ce numéro de salle existe déjà, veuillez choisir un autre numéro.', // Message d'erreur personnalisé
                'room_number.required' => 'Le numéro de salle est obligatoire.',
                'room_number.max' => 'Le numéro de salle ne doit pas dépasser 255 caractères.',
                'capacity.required' => 'La capacité est obligatoire.',
                'capacity.integer' => 'La capacité doit être un nombre entier.',
                'capacity.min' => 'La capacité doit être d\'au moins 1.',
            ]);
    
            $room->update($validatedData); // Met à jour la salle avec les nouvelles données
            return response()->json($room); // Retourne la salle mise à jour
        }
    
        return response()->json(['message' => 'Room not found'], 404); // Si la salle n'existe pas
    }
    
    // Supprime une salle spécifique par ID
    public function destroy($id)
    {
        $room = Room::find($id); // Trouve la salle par son ID
        if ($room) {
            $room->delete(); // Supprime la salle
            return response()->json(['message' => 'Room deleted']); // Message de confirmation
        }

        return response()->json(['message' => 'Room not found'], 404); // Si la salle n'existe pas
    }
}
