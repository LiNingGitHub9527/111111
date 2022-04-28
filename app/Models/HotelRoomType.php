<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelRoomType extends BaseModel
{
    protected $table = 'hotel_room_types';

    use SoftDeletes;

    protected $fillable = [
        'name',
        'hotel_id',
        'room_num',
        'adult_num',
        'child_num',
        'room_size',
        'sort_num'
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($hotelRoomType) {
            $hotelRoomType->hotelRoomTypeBeds()->get()->each->delete();
            $hotelRoomType->hotelRoomTypeImages()->get()->each->delete();
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo('App\Models\Hotel');
    }

    public function hotelRoomTypeBeds(): HasMany
    {
        return $this->hasMany('App\Models\HotelRoomTypeBed', 'room_type_id', 'id');
    }

    public function hotelRoomTypeImages(): HasMany
    {
        return $this->hasMany('App\Models\HotelRoomTypeImage', 'room_type_id', 'id');
    }

    public function roomStocks(): HasMany
    {
        return $this->hasMany('App\Models\RoomStock', 'hotel_room_type_id', 'id');
    }

    public function roomRates(): HasMany
    {
        return $this->hasMany('App\Models\PlanRoomTypeRate', 'room_type_id', 'id');
    }

    public static function getNames($room_type_ids)
    {
        if (empty($room_type_ids) || count($room_type_ids) == 0) {
            return '';
        }
        $names = HotelRoomType::find($room_type_ids)->pluck('name')->toArray();
        return implode('、', $names);
    }

    public static function beUsed($id)
    {
        $beUsed = false;
        $hotelHardItem = HotelHardItem::whereRaw("JSON_CONTAINS(room_type_ids,'[" . $id . "]')")->first();
        if (!empty($hotelHardItem)) {
            $beUsed = true;
        }

        $hotelKidsPolicy = HotelKidsPolicy::whereRaw("JSON_CONTAINS(room_type_ids,'[" . $id . "]')")->first();
        if (!empty($hotelKidsPolicy)) {
            $beUsed = true;
        }

        $plan = Plan::whereRaw("JSON_CONTAINS(room_type_ids,'[" . $id . "]')")->first();
        if (!empty($plan)) {
            $beUsed = true;
        }

        $form = Form::whereRaw("JSON_CONTAINS(room_type_ids,'[" . $id . "]')")->first();
        if (!empty($form)) {
            $beUsed = true;
        }

        $reservationBranch = ReservationBranch::where('room_type_id', $id)->first();
        if (!empty($reservationBranch)) {
            $beUsed = true;
        }

        return $beUsed;
    }
}
