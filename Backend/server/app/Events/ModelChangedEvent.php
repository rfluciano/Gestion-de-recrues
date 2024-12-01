<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModelChangedEvent implements ShouldBroadcast
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

    /**
     * Détermine sur quel canal l'événement sera diffusé.
     *
     * @return Channel|Channel[]
     */
    public function broadcastOn()
    {
        return new Channel('models-channel'); // Nom du canal de diffusion
    }

    /**
     * Détermine le nom de l'événement côté client.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'ModelChangedEvent'; // Nom de l'événement côté frontend
    }
}
