<?php

namespace App\Services;

use App\Models\ConsultationAvailabilityOverride;
use App\Models\ConsultationAvailabilityRule;
use App\Models\ConsultationBlockedSlot;
use App\Models\ConsultationReservation;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ConsultationAvailabilityService
{
    /**
     * @return array<int, array{date: string, slot_count: int}>
     */
    public function availabilityForMonth(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endOfMonth = $monthDate->copy()->endOfMonth();
        $days = [];

        while ($monthDate->lessThanOrEqualTo($endOfMonth)) {
            $slots = $this->slotsForDate($monthDate);

            if ($slots !== []) {
                $days[] = [
                    'date' => $monthDate->toDateString(),
                    'slot_count' => count($slots),
                ];
            }

            $monthDate->addDay();
        }

        return $days;
    }

    /**
     * @return array<int, array{start: string, end: string}>
     */
    public function slotsForDate(CarbonInterface $date): array
    {
        $date = Carbon::parse($date)->startOfDay();

        if ($date->isBefore(now()->startOfDay())) {
            return [];
        }

        $rules = ConsultationAvailabilityRule::query()
            ->where('weekday', $date->dayOfWeek)
            ->where('is_enabled', true)
            ->orderBy('start_time')
            ->get();

        $overrides = ConsultationAvailabilityOverride::query()
            ->whereDate('date', $date)
            ->orderBy('start_time')
            ->get();

        if ($overrides->contains(fn (ConsultationAvailabilityOverride $override) => $override->mode === 'close')) {
            return [];
        }

        $windows = $this->buildWindows($rules, $overrides);

        if ($windows->isEmpty()) {
            return [];
        }

        $blockedRanges = ConsultationBlockedSlot::query()
            ->whereDate('date', $date)
            ->get(['start_time', 'end_time']);

        $reservationRanges = ConsultationReservation::query()
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->get(['start_time', 'end_time']);

        $now = now();
        $slots = [];

        foreach ($windows as $window) {
            $cursor = Carbon::parse($date->toDateString().' '.$window['start']);
            $windowEnd = Carbon::parse($date->toDateString().' '.$window['end']);

            while ($cursor->copy()->addMinutes($window['duration'])->lessThanOrEqualTo($windowEnd)) {
                $slotEnd = $cursor->copy()->addMinutes($window['duration']);

                if ($date->isSameDay($now) && $cursor->lessThanOrEqualTo($now)) {
                    $cursor->addMinutes($window['duration'] + $window['buffer']);
                    continue;
                }

                if ($this->overlapsAny($cursor, $slotEnd, $blockedRanges)
                    || $this->overlapsAny($cursor, $slotEnd, $reservationRanges)) {
                    $cursor->addMinutes($window['duration'] + $window['buffer']);
                    continue;
                }

                $slots[] = [
                    'start' => $cursor->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                ];

                $cursor->addMinutes($window['duration'] + $window['buffer']);
            }
        }

        return collect($slots)
            ->unique(fn (array $slot): string => $slot['start'].'-'.$slot['end'])
            ->sortBy('start')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{start: string, end: string, duration: int, buffer: int}>
     */
    protected function buildWindows(Collection $rules, Collection $overrides): Collection
    {
        $baseWindows = $rules->map(fn (ConsultationAvailabilityRule $rule): array => [
            'start' => $rule->start_time,
            'end' => $rule->end_time,
            'duration' => $rule->slot_duration_minutes,
            'buffer' => $rule->buffer_minutes,
        ]);

        $replaceWindows = $overrides
            ->where('mode', 'replace')
            ->filter(fn (ConsultationAvailabilityOverride $override): bool => filled($override->start_time) && filled($override->end_time))
            ->map(fn (ConsultationAvailabilityOverride $override): array => [
                'start' => $override->start_time,
                'end' => $override->end_time,
                'duration' => $override->slot_duration_minutes ?: 60,
                'buffer' => $override->buffer_minutes ?: 0,
            ]);

        $addWindows = $overrides
            ->where('mode', 'add')
            ->filter(fn (ConsultationAvailabilityOverride $override): bool => filled($override->start_time) && filled($override->end_time))
            ->map(fn (ConsultationAvailabilityOverride $override): array => [
                'start' => $override->start_time,
                'end' => $override->end_time,
                'duration' => $override->slot_duration_minutes ?: 60,
                'buffer' => $override->buffer_minutes ?: 0,
            ]);

        return $replaceWindows->isNotEmpty()
            ? $replaceWindows->values()
            : $baseWindows->concat($addWindows)->values();
    }

    protected function overlapsAny(CarbonInterface $start, CarbonInterface $end, Collection $ranges): bool
    {
        return $ranges->contains(function (object $range) use ($start, $end): bool {
            $rangeStart = Carbon::parse($start->toDateString().' '.$range->start_time);
            $rangeEnd = Carbon::parse($start->toDateString().' '.$range->end_time);

            return $start->lt($rangeEnd) && $end->gt($rangeStart);
        });
    }
}
