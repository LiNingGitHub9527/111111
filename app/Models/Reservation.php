<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends BaseModel
{
    use SoftDeletes;

    const STATUS_LIST = [
        0 => '予約中',
        1 => 'キャンセル済み',
        2 => 'ノーショー',
        3 => '予約中(変更済み)',
        4 => 'キャンセル',
        5 => 'キャンセル（ノーショー）'
    ];

    const STATUS_RESERVATION = [
        0 => 'ご予約キャンセル',
        1 => '新規ご予約',
        2 => 'ご予約変更',
    ];

    protected $table = 'reservations';

    protected $fillable = ['adult_num', 'child_num', 'reservation_status', 'name',
        'last_name', 'first_name', 'name_kana', 'first_name_kana', 'last_name_kana',
        'checkin_start', 'checkin_end', 'checkout_end',
        'checkout_time', 'checkin_time', 'email', 'tel', 'address',
        'room_num', 'accommodation_price', 'commission_price',
        'payment_commission_price', 'cancel_date_time', 'change_date_time',
        'cancel_fee', 'special_request', 'stripe_customer_id', 'stripe_payment_id',
        'payment_status'];

    protected $casts = [
        'checkin_start' => 'datetime',
        'checkin_end' => 'datetime',
        'checkout_end' => 'datetime',
        'reservation_date' => 'datetime',
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
        'cancel_date_time' => 'datetime',
//        'accommodation_price_detail' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::updated(function ($reservation) {
            $reservationStatus = $reservation->reservation_status;
            if ($reservationStatus == 1 || $reservationStatus == 2) {
                $reservationBranches = $reservation->reservationBranches;
                foreach ($reservationBranches as $reservationBranch) {
                    if ($reservationBranch->reservation_status != 0) {
                        continue;
                    }
                    $reservationBranch->reservation_status = $reservationStatus;
                    $reservationBranch->cancel_date_time = time();
                    $reservationBranch->save();
                }
            }
        });
    }

    public function statusDisplayName()
    {
        return self::STATUS_LIST[$this->reservation_status] ?? 'UNKONWN';
    }

    public function statusReservation($state)
    {
        return self::STATUS_RESERVATION[$state] ?? 'UNKONWN';
    }

    public function reservationPlans(): HasMany
    {
        return $this->hasMany('\App\Models\ReservationPlan', 'reservation_id', 'id')->with('kidsPolicies');
        ;
    }

    public function reservationCaptured()
    {
        return $this->hasMany('\App\Models\ReservationCaptured', 'reservation_id', 'id');
    }

    public function reservationPayment()
    {
        return $this->hasMany('\App\Models\ReservationPayment', 'reservation_id', 'id');
    }

    public function reservationCancelPolicies()
    {
        return $this->hasMany('\App\Models\ReservationCancelPolicy', 'reservation_id', 'id');
    }

    public function reservationCancelPolicy()
    {
        return $this->hasOne('\App\Models\ReservationCancelPolicy', 'reservation_id', 'id');
    }

    public function reservationPlanFormItems(): HasMany
    {
        return $this->hasMany('\App\Models\ReservationPlanFormItem', 'reservation_id', 'id');
    }

    public function reservationBranches(): HasMany
    {
        return $this->hasMany('\App\Models\ReservationBranch', 'reservation_id', 'id')->with('roomType', 'plan');
    }

    public function reservedBlocks(): HasMany
    {
        return $this->hasMany('\App\Models\ReservedReservationBlock', 'reservation_id', 'id');
    }

    public function baseCustomerItemValues(): HasMany
    {
        return $this->hasMany('\App\Models\BaseCustomerItemValue', 'reservation_id', 'id');
    }

    function accommodationDay()
    {
        $checkinStart = Carbon::parse($this->checkin_start)->startOfDay();
        $checkoutEnd = Carbon::parse($this->checkout_end)->startOfDay();
        return $checkoutEnd->diffInDays($checkinStart);
    }

    function mdwFormat($date)
    {
        $dt = Carbon::parse($date);
        if ($dt->dayOfWeek == Carbon::MONDAY) {
            $dayOfWeek = '月';
        } elseif ($dt->dayOfWeek == Carbon::TUESDAY) {
            $dayOfWeek = '火';
        } elseif ($dt->dayOfWeek == Carbon::WEDNESDAY) {
            $dayOfWeek = '水';
        } elseif ($dt->dayOfWeek == Carbon::THURSDAY) {
            $dayOfWeek = '木';
        } elseif ($dt->dayOfWeek == Carbon::FRIDAY) {
            $dayOfWeek = '金';
        } elseif ($dt->dayOfWeek == Carbon::SATURDAY) {
            $dayOfWeek = '土';
        } else {
            $dayOfWeek = '日';
        }
        return $date->format('m月d日') . '(' . $dayOfWeek . ')';
    }

    public function checkinDisplay()
    {
        return $this->mdwFormat($this->checkin_start);
    }

    public function checkoutDisplay()
    {
        return $this->mdwFormat($this->checkout_end);
    }

    public function checkinDisplayDate()
    {
        return $this->checkin_start->format('Y年m月d日');
    }

    public function checkoutDisplayDate()
    {
        return $this->checkout_end->format('Y年m月d日');
    }

    public function checkinDisplayDateTime()
    {
        return $this->checkin_start->format('Y年m月d日 H時i分');
    }

    public function checkoutDisplayDateTime()
    {
        return $this->checkout_end->format('Y年m月d日 H時i分');
    }

    public function checkinDisplayTime()
    {
        return $this->checkin_time->format('H時i分');
    }

    public function checkoutDisplayTime()
    {
        return $this->checkout_time->format('H時i分');
    }

    public function reservationDisplayDate()
    {
        return $this->reservation_date->format('Y年m月d日');
    }

    private function calculateCommission($rate)
    {
        $reserveService = app()->make('ReserveService');
        return $reserveService->calcCommission($this->sale_price, $rate);
    }

    public function hotel()
    {
        return $this->belongsTo('\App\Models\Hotel');
    }

    public static function isNoShow($id)
    {
        $isNoShow = false;
        $message = null;
        $reservation = Reservation::where('id', $id)->first();
        if (Carbon::now()->lt(Carbon::parse($reservation->checkin_time)->endOfDay())) {
            $isNoShow = true;
            $message = 'チェックイン日を過ぎていないので、ノーショーに変更できません。';
        }

        if (Carbon::now()->gt(Carbon::parse($reservation->checkin_time)->addDays(2)->endOfDay())) {
            $isNoShow = true;
            $message = 'チェックイン日より二日間が過ぎましたので、ノーショーとして登録できません。';
        }
        return ['isNoShow' => $isNoShow, 'message' => $message];
    }

    public function getSalePriceAttribute()
    {
        if ($this->payment_status == 1) {
            return ($this->reservation_status == 0) ? $this->accommodation_price : $this->cancel_fee;
        }
        return 0;
    }

    public function getAccommodationCommissionAttribute()
    {
        $accomodationCommisionPercentage = config('prepay.commission_rate');
        return $this->calculateCommission($accomodationCommisionPercentage);
    }

    public function getPaymentCommissionAttribute()
    {
        if ($this->payment_method != 1) {
            return 0;
        }
        $paymentCommissionPercentage = config('prepay.payment_commission_rate');
        return $this->calculateCommission($paymentCommissionPercentage);
    }
}
