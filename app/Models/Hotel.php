<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends BaseModel
{
    protected $table = 'hotels';

    use SoftDeletes;

    protected $casts = [
        'agreement_date' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function ($hotel) {
            $client = $hotel->client;
            $client->hotel_num++;
            $client->save();
        });

        static::deleted(function ($hotel) {
            $client = $hotel->client;
            $client->hotel_num--;
            if ($client->hotel_num < 0) {
                $client->hotel_num = 0;
            }
            $client->save();
        });
    }

    protected $fillable = [
        'client_id',
        'name',
        'address',
        'tel',
        'email',
        'person_in_charge',
        'rate_plan_id',
        'logo_img',
        'checkin_start',
        'checkin_end',
        'checkout_end',
        'sync_status',
        'tema_login_id',
        'tema_login_password',
        'is_tax',
        'business_type',
        'bank_code', 
        'branch_code', 
        'deposit_type', 
        'account_number', 
        'recipient_name'
    ];

    const BUSINESS_TYPE_LIST = [
        1 => 'ホテル',
        2 => '塗装業',
        3 => '美容業界',
        4 => 'サウナ・温浴施設',
        5 => '不動産業界'
    ];

    public function client()
    {
        return $this->belongsTo('\App\Models\Client', 'client_id', 'id');
    }

    public function plans()
    {
        return $this->hasMany('\App\Models\Plan');
    }

    public function hotelRoomTypes()
    {
        return $this->hasMany('\App\Models\HotelRoomType');
    }

    public function forms()
    {
        return $this->hasMany('\App\Models\Form');
    }

    public function formItems()
    {
        return $this->hasMany('\App\Models\FormItem');
    }

    public function cancelPolicies()
    {
        return $this->hasMany('\App\Models\CancelPolicy');
    }

    public function hotelHardCategories(): HasMany
    {
        return $this->hasMany('\App\Models\HotelHardCategory');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany('\App\Models\Reservation');
    }

    public function lps(): HasMany
    {
        return $this->hasMany('\App\Models\Lp');
    }

    public function hotelKidsPolicies(): HasMany
    {
        return $this->hasMany('\App\Models\HotelKidsPolicy');
    }

    public function hotelNotes(): HasMany
    {
        return $this->hasMany('\App\Models\HotelNote');
    }

    public function reservationBlocks(): HasMany
    {
        return $this->hasMany('\App\Models\ReservationBlock');
    }

    public function reservationRepeatGroups(): HasMany
    {
        return $this->hasMany('\App\Models\ReservationRepeatGroup');
    }

    public function imageSrc()
    {
        return $this->logo_img ? photoUrl($this->logo_img) : '';
    }

    public function statusDisplayName()
    {
        return self::BUSINESS_TYPE_LIST[$this->business_type] ?? '';
    }


    // Accessors
    public function getTotalAccommodationPriceAttribute() {
        if($this->reservations->count() == 0) {
            return 0;
        }
        return $this->reservations->sum('accommodation_price');
    }

    public function getTotalSalePriceAttribute() {
        return $this->reservations->sum('sale_price');
    }

    public function getTotalAccommodationCommissionAttribute() {
        if($this->reservations->count() == 0) {
            return 0;
        }
        return $this->reservations->sum('accommodation_commission');
    }

    public function getTotalPaymentCommissionAttribute() {
        if($this->reservations->count() == 0) {
            return 0;
        }
        return $this->reservations->sum('payment_commission');
    }

    public function getTransferAmountAttribute() {
        return $this->total_sale_price - ($this->total_accommodation_commission + $this->total_payment_commission);
    }
}
