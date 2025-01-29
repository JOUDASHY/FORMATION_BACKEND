<?php
// app/Events/NewCertificationEvent.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Ajoutez cette ligne
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCertificationEvent implements ShouldBroadcast // Implémentez ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formationTitle;
    public $apprenants;
    public $obtention_date;

    public function __construct($formationTitle, $apprenants,$obtention_date)
    {
        $this->obtention_date = $obtention_date;
        $this->formationTitle = $formationTitle;
        $this->apprenants = $apprenants; // Collection d'apprenants
    }

    public function broadcastOn()
    {
        return new PrivateChannel('certifications'); // Changez le nom du canal pour quelque chose de plus significatif
    }
}
