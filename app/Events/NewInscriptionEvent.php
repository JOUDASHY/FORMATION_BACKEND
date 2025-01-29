<?php
// app/Events/NewFormationEvent.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Ajoutez cette ligne
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewInscriptionEvent implements ShouldBroadcast // Implémentez ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formationTitle;
    public $apprenant;
    public $admins;
    public $payed;
    public $action;
    public function __construct($formationTitle, $admins, $apprenant, $payed, $action)
    {
        $this->formationTitle = $formationTitle;
        $this->apprenant = $apprenant;
        $this->payed = $payed;
        $this->action = $action;  // Enregistrez l'action (created ou deleted)

        // Sérialiser les admins sous forme d'un tableau (par exemple, les noms ou les IDs)
        $this->admins = $admins;//->pluck('name');
    }

    public function broadcastOn()
    {
        return new PrivateChannel('inscriptions'); // Changez le nom du canal pour quelque chose de plus significatif
    }
}
