<?php

use App\Models\ConsultationAvailabilityRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ConsultationAvailabilityRule::query()
            ->where('weekday', 5)
            ->get()
            ->each(function (ConsultationAvailabilityRule $rule): void {
                ConsultationAvailabilityRule::query()->updateOrCreate(
                    [
                        'weekday' => 0,
                        'start_time' => $rule->start_time,
                        'end_time' => $rule->end_time,
                    ],
                    [
                        'slot_duration_minutes' => $rule->slot_duration_minutes,
                        'buffer_minutes' => $rule->buffer_minutes,
                        'is_enabled' => $rule->is_enabled,
                    ],
                );

                $rule->delete();
            });
    }

    public function down(): void
    {
        ConsultationAvailabilityRule::query()
            ->where('weekday', 0)
            ->get()
            ->each(function (ConsultationAvailabilityRule $rule): void {
                ConsultationAvailabilityRule::query()->updateOrCreate(
                    [
                        'weekday' => 5,
                        'start_time' => $rule->start_time,
                        'end_time' => $rule->end_time,
                    ],
                    [
                        'slot_duration_minutes' => $rule->slot_duration_minutes,
                        'buffer_minutes' => $rule->buffer_minutes,
                        'is_enabled' => $rule->is_enabled,
                    ],
                );

                $rule->delete();
            });
    }
};
