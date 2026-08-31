<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification
{
    public function __construct(
        public Application $application,
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'nomor_pengajuan' => $this->application->nomor_pengajuan,
            'status' => $this->application->status->value,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url ?? route('dashboard', absolute: false),
        ];
    }
}
