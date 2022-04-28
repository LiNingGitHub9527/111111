<?php
namespace App\Services\commonUseCase\HardItem;

use Carbon\Carbon;
use DB;
use App\Models\HotelHardItem;

class HardItemService
{

    public function __construct()
    {
        
    }

    public function getRoomHardItem($roomTypeId, $hotelId)
    {
        $hardItems = HotelHardItem::select('name')
                                    ->join('original_hotel_hard_items', 'original_hotel_hard_items.id', '=', 'hotel_hard_items.original_hotel_hard_item_id')
                                    ->where('hotel_id', $hotelId)
                                    ->where('hotel_hard_items.is_all_room', 1)
                                    ->orWhereRaw("JSON_CONTAINS(hotel_hard_items.room_type_ids, '[" . $roomTypeId . "]')")
                                    ->get()
                                    ->pluck('name')
                                    ->toArray();
        return $hardItems;
    }
}