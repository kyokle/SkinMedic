<?php
namespace App\Helpers;
use Illuminate\Support\Facades\DB;

class NotificationHelper
{
    public static function send(
        int $userId,
        string $title,
        string $message,
        string $type = 'general',
        ?int $referenceId = null
    ): void {
        DB::table('notifications')->insert([
            'user_id'      => $userId,
            'title'        => $title,
            'message'      => $message,
            'type'         => $type,
            'reference_id' => $referenceId,
            'is_read'      => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}