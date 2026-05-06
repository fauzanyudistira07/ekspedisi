<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(Model $auditable, string $event, string $summary, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::create([
            'actor_id' => Auth::id(),
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'summary' => $summary,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
