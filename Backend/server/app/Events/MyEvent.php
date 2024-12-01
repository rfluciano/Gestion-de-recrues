<?php
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MyEvent implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

    public $model; // Nom du modèle ou information pertinente sous forme de chaîne
    public $action; // Action effectuée (e.g., "created", "updated", "deleted")

    /**
     * Crée une nouvelle instance de l'événement.
     *
     * @param string $model  Nom du modèle concerné
     * @param string $action Action effectuée sur le modèle
     */
    public function __construct(string $model, string $action)
    {
        $this->model = $model;
        $this->action = $action;
    }

  public function broadcastOn()
  {
      return ['my-channel'];
  }

  public function broadcastAs()
  {
      return 'my-event';
  }
}