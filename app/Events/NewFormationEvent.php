<?php
// app/Events/NewFormationEvent.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Ajoutez cette ligne
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewFormationEvent implements ShouldBroadcast // Implémentez ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formationTitle;
    public $apprenants;

    public function __construct($formationTitle, $apprenants)
    {
        $this->formationTitle = $formationTitle;
        $this->apprenants = $apprenants; // Collection d'apprenants
    }

    public function broadcastOn()
    {
        return new PrivateChannel('formations'); // Changez le nom du canal pour quelque chose de plus significatif
    }
}
