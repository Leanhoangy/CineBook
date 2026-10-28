<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use App\Models\Showtime;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Paginator::useBootstrapFive();
        Carbon::setLocale('vi');

        // @money(85000) => 85.000 ₫
        Blade::directive('money', fn ($expression) => "<?php echo number_format((int) ($expression), 0, ',', '.').' ₫'; ?>");

        // Số suất đang trong giờ soát vé, hiện cạnh mục "Soát vé" ở menu admin
        View::composer('layouts.admin', fn ($view) => $view->with(
            'checkInOpenCount',
            Showtime::checkInOpen()->whereHas('confirmedBookings')->count(),
        ));
    }
}
