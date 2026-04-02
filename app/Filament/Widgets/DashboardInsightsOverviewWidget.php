<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\ConsultationReservations\ConsultationReservationResource;
use App\Models\BlogPost;
use App\Models\ConsultationReservation;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\PortfolioProject;
use App\Models\Service;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardInsightsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Admin Insights';

    protected ?string $description = 'Latest publishing, inbox, and booking signals.';

    protected function getStats(): array
    {
        $publishedBreakdown = [
            'pages' => Page::query()->where('is_published', true)->count(),
            'services' => Service::query()->where('is_published', true)->count(),
            'projects' => PortfolioProject::query()->where('is_published', true)->count(),
            'posts' => BlogPost::query()->where('is_published', true)->count(),
        ];

        $updatedSince = now()->subDays(7);
        $updatedBreakdown = [
            'pages' => Page::query()->where('updated_at', '>=', $updatedSince)->count(),
            'services' => Service::query()->where('updated_at', '>=', $updatedSince)->count(),
            'projects' => PortfolioProject::query()->where('updated_at', '>=', $updatedSince)->count(),
            'posts' => BlogPost::query()->where('updated_at', '>=', $updatedSince)->count(),
        ];

        $latestMessage = ContactMessage::query()->latest()->first();

        $upcomingReservationsQuery = ConsultationReservation::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('date', '>=', today()->toDateString());

        $nextReservation = (clone $upcomingReservationsQuery)
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();

        return [
            Stat::make('Published Content', array_sum($publishedBreakdown))
                ->description($this->formatBreakdown($publishedBreakdown))
                ->descriptionIcon(Heroicon::DocumentText)
                ->color('primary'),
            Stat::make('Updated In 7 Days', array_sum($updatedBreakdown))
                ->description($this->formatBreakdown($updatedBreakdown, 'No recent content updates'))
                ->descriptionIcon(Heroicon::ArrowPath)
                ->color(array_sum($updatedBreakdown) > 0 ? 'info' : 'gray'),
            Stat::make('New Messages', ContactMessage::query()->where('status', 'new')->count())
                ->description($this->formatLatestMessage($latestMessage))
                ->descriptionIcon(Heroicon::Inbox)
                ->color($latestMessage ? 'warning' : 'success')
                ->url(ContactMessageResource::getUrl()),
            Stat::make('Upcoming Reservations', $upcomingReservationsQuery->count())
                ->description($this->formatNextReservation($nextReservation))
                ->descriptionIcon(Heroicon::CalendarDateRange)
                ->color($nextReservation ? 'warning' : 'success')
                ->url(ConsultationReservationResource::getUrl()),
        ];
    }

    /**
     * @param  array<string, int>  $breakdown
     */
    protected function formatBreakdown(array $breakdown, string $emptyLabel = 'Nothing to show yet'): string
    {
        $parts = collect($breakdown)
            ->map(function (int $count, string $label): ?string {
                if ($count === 0) {
                    return null;
                }

                return sprintf('%d %s', $count, $label);
            })
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            return $emptyLabel;
        }

        return $parts->implode(' • ');
    }

    protected function formatLatestMessage(?ContactMessage $message): string
    {
        if (! $message) {
            return 'Inbox is clear';
        }

        return sprintf('%s • %s', $message->name, $message->created_at->diffForHumans());
    }

    protected function formatNextReservation(?ConsultationReservation $reservation): string
    {
        if (! $reservation) {
            return 'No upcoming bookings';
        }

        $dateTime = Carbon::parse("{$reservation->date} {$reservation->start_time}");

        return sprintf('%s • %s', $reservation->name, $dateTime->format('M j, g:i A'));
    }
}
