<?php
// app/Events/NewFormationEvent.php
// app/Events/NewPlanningEvent.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPlanningEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $formationTitle;
    public $formateurId;
    public $apprenants;
    public $start_time;
    public $end_time;
    public $CourseName;
    public $date;
    public $room;
    public $isCancelled;  // Nouveau paramètre pour gérer l'annulation

    public function __construct($formationTitle, $formateurId, $apprenants, $start_time, $end_time, $CourseName, $date, $room, $isCancelled = false)
    {
        $this->formationTitle = $formationTitle;
        $this->formateurId = $formateurId;
        $this->apprenants = $apprenants;
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->CourseName = $CourseName;
        $this->date = $date;
        $this->room = $room;
        $this->isCancelled = $isCancelled; // Initialiser le paramètre
    }

    public function broadcastOn()
    {
        return new PrivateChannel('plannings');
    }
}
