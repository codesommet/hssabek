<?php

namespace App\Notifications;

use App\Models\System\Announcement;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    public function __construct(
        public readonly Announcement $announcement,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        $colorMap = [
            'info' => 'info',
            'warning' => 'warning',
            'success' => 'success',
            'danger' => 'danger',
        ];

        $iconMap = [
            'info' => 'info-circle',
            'warning' => 'warning-2',
            'success' => 'tick-circle',
            'danger' => 'danger',
        ];

        return [
            'title' => $this->announcement->title,
            'message' => \Illuminate\Support\Str::limit($this->announcement->content, 150),
            'color' => $colorMap[$this->announcement->type] ?? 'info',
            'icon' => $iconMap[$this->announcement->type] ?? 'notification',
            'announcement_id' => $this->announcement->id,
        ];
    }
}
