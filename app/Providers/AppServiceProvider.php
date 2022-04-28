<?php

namespace App\Providers;

use App\Rules\RequiredReplaceSpace;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Concerns\ValidatesAttributes;
use Schema;
use URL;

class AppServiceProvider extends ServiceProvider
{
    use ValidatesAttributes;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // common
        $this->app->bind('ReserveSearchService', 'App\Services\commonUseCase\Reservation\ReserveSearchService');
        $this->app->bind('FormSearchService', 'App\Services\commonUseCase\Reservation\FormSearchService');
        $this->app->bind('ConvertCancelPolicyService', 'App\Services\commonUseCase\CancelPolicy\ConvertCancelPolicyService');
        $this->app->bind('CalcCancelPolicyService', 'App\Services\commonUseCase\CancelPolicy\CalcCancelPolicyService');
        $this->app->bind('CancelPolicyService', 'App\Services\commonUseCase\CancelPolicy\CancelPolicyService');
        $this->app->bind('S3Service', 'App\Services\S3Service');
        $this->app->bind('KidsPolicyService', 'App\Services\commonUseCase\KidsPolicy\KidsPolicyService');
        $this->app->bind('ReserveService', 'App\Services\commonUseCase\Reservation\ReserveService');
        $this->app->bind('CalcPlanAmountService', 'App\Services\commonUseCase\Reservation\CalcPlanAmountService');
        $this->app->bind('CalcFormAmountService', 'App\Services\commonUseCase\Reservation\CalcFormAmountService');
        $this->app->bind('CalcOtherFormAmountService', 'App\Services\commonUseCase\Reservation\CalcOtherFormAmountService');
        $this->app->bind('StripeService', 'App\Services\StripeService');
        $this->app->bind('HardItemService', 'App\Services\commonUseCase\HardItem\HardItemService');
        $this->app->bind('CommonRoomStockService', 'App\Services\commonUseCase\RoomStock\CommonService');
        $this->app->bind('CommonRoomRateService', 'App\Services\commonUseCase\RoomRate\CommonService');

        $this->app->bind('OtherReserveService', 'App\Services\commonUseCase\Reservation\Other\OtherReserveService');

        // pms api
        $this->app->bind('LineReserveService', 'App\Services\PmsApi\User\LineReserveService');
        $this->app->bind('LineDayuseReserveService', 'App\Services\PmsApi\User\LineDayuseReserveService');
        $this->app->bind('ApiCallService', 'App\Services\PmsApi\ApiCallService');

        // browser reserve
        $this->app->bind('BrowserReserveService', 'App\Services\Browser\User\BrowserReserveService');
        $this->app->bind('ReserveSessionService', 'App\Services\Browser\User\ReserveSessionService');
        $this->app->bind('ReserveChangeService', 'App\Services\Browser\User\ReserveChangeService');

        // api
        $this->app->bind('ApiPlanService', 'App\Services\Api\Client\Plan\PlanService');
        $this->app->bind('ApiHotelFormService', 'App\Services\Api\Client\Form\HotelFormService');
        $this->app->bind('ApiOtherFormService', 'App\Services\Api\Client\Form\OtherFormService');
        $this->app->bind('ApiHotelHomeService', 'App\Services\Api\Client\Home\HotelHomeService');
        $this->app->bind('ApiOtherHomeService', 'App\Services\Api\Client\Home\OtherHomeService');
        $this->app->bind('ApiCommonReservationService', 'App\Services\Api\Client\Reservation\CommonReservationService');
        $this->app->bind('ApiHotelReservationService', 'App\Services\Api\Client\Reservation\HotelReservationService');
        $this->app->bind('ApiOtherReservationService', 'App\Services\Api\Client\Reservation\OtherReservationService');
        $this->app->bind('ApiReservationScheduleService', 'App\Services\Api\Client\Reservation\ReservationScheduleService');

        // temairazu api
        $this->app->bind('TemairazuService', 'App\Services\ScEndPoint\Temairazu\TemairazuService');

        $this->app->bind('ReceiptService', 'App\Services\commonUseCase\Reservation\ReceiptService');

        $this->app->bind('CommonHotelService', 'App\Services\commonUseCase\Hotel\HotelService');
        $this->app->bind('HotelEmailService', 'App\Services\commonUseCase\Reservation\HotelEmailService');
        $this->app->bind('ReservationStatusService', 'App\Services\commonUseCase\Reservation\ReservationStatusService');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.ssl')) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        if (config('app.sql_debug')) {
            DB::listen(
                function ($sql) {
                    foreach ($sql->bindings as $i => $binding) {
                        if ($binding instanceof \DateTime) {
                            $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                        } else {
                            if (is_string($binding)) {
                                $sql->bindings[$i] = "'$binding'";
                            }
                        }
                    }

                    // Insert bindings into query
                    $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);

                    $query = vsprintf($query, $sql->bindings);

                    // Save the query to file
                    $logFile = fopen(
                        storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_query.log'),
                        'a+'
                    );
                    fwrite($logFile, date('Y-m-d H:i:s') . ': ' . $query . PHP_EOL);
                    fclose($logFile);
                }
            );
        }

        // validation

        Validator::extend('check_numeric', function ($attribute, $value, $parameters) {
            $reg = '/^([0-9]+)*$/';
            return preg_match($reg, $value);
        });

        Validator::extend('required_not_empty', function ($attribute, $value, $parameters) {
            return $this->validateRequired($attribute, str_replace(["\r\n", "\r", "\n", "　"], "", $value));
        }, trans('validation.required'));

    }
}
