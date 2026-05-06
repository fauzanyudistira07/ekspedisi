<?php

namespace App\Providers;

use App\Models\ShipmentTracking;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        View::composer('fe.master', function ($view): void {
            if (!Auth::guard('customer')->check()) {
                $view->with('trackingNotificationCount', 0)
                    ->with('trackingNotifications', collect());

                return;
            }

            $customer = Auth::guard('customer')->user();
            $seenAt = $customer->last_tracking_seen_at;

            $notificationsQuery = ShipmentTracking::with('shipment')
                ->whereHas('shipment', function ($query) use ($customer) {
                    $query->where('sender_id', $customer->id)
                        ->orWhere('receiver_id', $customer->id);
                });

            $unreadCount = (clone $notificationsQuery)
                ->when($seenAt, fn ($query) => $query->where('tracked_at', '>', $seenAt))
                ->count();

            $notifications = $notificationsQuery
                ->latest('tracked_at')
                ->limit(5)
                ->get();

            $view->with('trackingNotificationCount', $unreadCount)
                ->with('trackingNotifications', $notifications);
        });
    }
}
