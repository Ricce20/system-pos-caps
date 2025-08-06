<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\LeastSoldProductsTable;
use App\Filament\Widgets\LocationSalesChart;
use App\Filament\Widgets\LowStockProductsTable;
use App\Filament\Widgets\ProductSalesChart;
use App\Filament\Widgets\ProductStatsWidget;
use App\Filament\Widgets\SalesStatsWidget;
use App\Filament\Widgets\TopProductsTable;
use App\Http\Middleware\IsAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{ 
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandLogo(asset('images/lbeige.png'))
            ->brandLogoHeight('6rem')
            ->favicon(asset('images/lbeige.png'))

            ->profile(isSimple:false)
            ->colors([
                'primary' => [
                    50 => '243, 242, 242',
                    100 => '224, 220, 220',
                    200 => '185, 178, 178',
                    300 => '147, 137, 137',
                    400 => '108, 95, 95',
                    500 => '46, 38, 38',      // #2E2626
                    600 => '40, 33, 33',
                    700 => '32, 27, 27',
                    800 => '24, 20, 20',
                    900 => '16, 14, 14',
                    950 => '8, 7, 7',
                ],
                'secondary' => [
                    50 => '252, 241, 239',
                    100 => '247, 221, 216',
                    200 => '238, 182, 174',
                    300 => '227, 143, 131',
                    400 => '211, 102, 85',
                    500 => '170, 72, 55',     // #AA4837
                    600 => '140, 58, 44',
                    700 => '110, 46, 35',
                    800 => '82, 34, 26',
                    900 => '56, 23, 17',
                    950 => '36, 15, 11',
                ],
                'info' => [
                    50 => '245, 247, 244',
                    100 => '232, 235, 229',
                    200 => '203, 211, 199',
                    300 => '173, 187, 169',
                    400 => '149, 162, 138',   // #95A28A
                    500 => '128, 139, 118',
                    600 => '102, 111, 95',
                    700 => '76, 82, 71',
                    800 => '53, 57, 49',
                    900 => '33, 36, 31',
                    950 => '20, 22, 19',
                ],
                'success' => [
                    50 => '242, 243, 241',
                    100 => '226, 228, 224',
                    200 => '197, 200, 194',
                    300 => '168, 171, 165',
                    400 => '130, 138, 122',
                    500 => '77, 86, 69',      // #4D5645
                    600 => '62, 69, 55',
                    700 => '50, 56, 44',
                    800 => '37, 42, 33',
                    900 => '25, 28, 22',
                    950 => '15, 17, 13',
                ],
                'warning' => [
                    50 => '250, 246, 243',
                    100 => '243, 237, 232',
                    200 => '234, 221, 210',
                    300 => '223, 201, 187',
                    400 => '209, 179, 161',
                    500 => '145, 124, 109',    // #917C6D
                    600 => '116, 99, 87',
                    700 => '87, 74, 65',
                    800 => '60, 51, 45',
                    900 => '36, 30, 27',
                    950 => '22, 18, 16',
                ],
                'danger' => [
                    50 => '252, 241, 239',
                    100 => '247, 221, 216',
                    200 => '238, 182, 174',
                    300 => '227, 143, 131',
                    400 => '211, 102, 85',
                    500 => '170, 72, 55',     // puedes reutilizar el terracota si prefieres
                    600 => '140, 58, 44',
                    700 => '110, 46, 35',
                    800 => '82, 34, 26',
                    900 => '56, 23, 17',
                    950 => '36, 15, 11',
                ],
                'gray' => [
                    50 => '253, 251, 250',
                    100 => '250, 247, 245',
                    200 => '245, 239, 235',
                    300 => '240, 229, 223',
                    400 => '233, 216, 199',   // #E8D6C7
                    500 => '204, 188, 174',
                    600 => '163, 150, 139',
                    700 => '122, 113, 104',
                    800 => '84, 77, 71',
                    900 => '50, 46, 42',
                    950 => '31, 28, 26',
                ],

            ])
            // ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                SalesStatsWidget::class,
                LocationSalesChart::class,
                TopProductsTable::class,
                LeastSoldProductsTable::class,
                LowStockProductsTable::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                IsAdmin::class,
            ],isPersistent:true);
    }
}
