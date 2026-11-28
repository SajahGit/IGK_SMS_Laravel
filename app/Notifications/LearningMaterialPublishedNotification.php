<?php

namespace App\Notifications;

use App\Models\LearningMaterial;
use Illuminate\Notifications\Notification;

class LearningMaterialPublishedNotification extends Notification
{
    public function __construct(public LearningMaterial $material) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $typeLabel = $this->material->isVideo() ? 'Video' : 'PDF';

        return [
            'title' => "New {$typeLabel} material: {$this->material->title}",
            'body' => 'A new learning material is now available in the Common Learning Materials Library.',
            'material_id' => $this->material->id,
            'material_type' => $this->material->material_type,
            'url' => route('learning-materials.view', $this->material, false),
        ];
    }
}
