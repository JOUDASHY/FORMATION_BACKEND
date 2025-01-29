<?php
// app/Events/NewFormationEvent.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Ajoutez cette ligne
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewLessonEvent implements ShouldBroadcast // Implémentez ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $apprenant_inscrits;
    public $title;
    public $formateur;
    public $course;
    


    public function __construct($apprenant_inscrits,$title,$formateur,$course)
    
    {
        $this->apprenant_inscrits = $apprenant_inscrits;
        $this->title = $title;
        $this->course = $course;
        
        $this->formateur = $formateur; // Collection d'apprenants
    }

    public function broadcastOn()
    {
        return new PrivateChannel('lessons'); // Changez le nom du canal pour quelque chose de plus significatif
    }
}
