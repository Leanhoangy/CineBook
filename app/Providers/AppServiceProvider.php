<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
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
    }
}
