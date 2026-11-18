<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IncidentReport extends Model
{
    use SoftDeletes;

    protected $table = 'incident_reports';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'location_accuracy' => 'float',
        'reporting_for_other' => 'boolean',
        'victim_contactable' => 'boolean',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'deleted_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $report): void {
            $report->id ??= (string) Str::ulid();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'category_id');
    }

    public function trackingTokens(): HasMany
    {
        return $this->hasMany(ReportTrackingToken::class, 'report_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ReportMessage::class, 'report_id');
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(ReportStatusEvent::class, 'report_id');
    }

    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'report_id');
    }

    public static function nextReferenceCode(): string
    {
        do {
            $code = 'MDR-' . date('Y') . '-' . strtoupper(Str::random(6));
        } while (self::query()->where('reference_code', $code)->exists());

        return $code;
    }
}
