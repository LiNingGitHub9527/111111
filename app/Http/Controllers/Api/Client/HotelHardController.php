<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\HotelHardRequest;
use App\Models\Hotel;
use App\Models\HotelHardItem;
use App\Models\OriginalHotelHardCategory;
use Illuminate\Support\Facades\Log;

class HotelHardController extends ApiBaseController
{

    public function init($id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $originalHotelHardCategoryList = OriginalHotelHardCategory::with('originalHotelHardItems')->orderBy('sort_num', 'ASC')->get();

        $originalHotelHardCategories = [];
        foreach ($originalHotelHardCategoryList as $originalHotelHardCategory) {
            $originalHotelHardItems = [];
            $originalHotelHardItemList = $originalHotelHardCategory->originalHotelHardItems;
            foreach ($originalHotelHardItemList as $originalHotelHardItem) {
                $hotelHardItem = HotelHardItem::where('hotel_id', $id)->where('original_hotel_hard_item_id', $originalHotelHardItem->id)->first();
                $item = [
                    'id' => $originalHotelHardItem->id,
                    'name' => $originalHotelHardItem->name,
                    'is_all_room' => empty($hotelHardItem) ? 2 : $hotelHardItem->is_all_room,
                    'room_type_ids' => empty($hotelHardItem) ? [] : $hotelHardItem->room_type_ids
                ];
                $originalHotelHardItems[] = $item;
            }
            $row = [
                'id' => $originalHotelHardCategory->id,
                'name' => $originalHotelHardCategory->name,
                'originalHotelHardItems' => $originalHotelHardItems
            ];
            $originalHotelHardCategories[] = $row;
        }

        $hotelRoomTypeList = $hotel->hotelRoomTypes;
        $hotelRoomTypes = [];
        foreach ($hotelRoomTypeList as $hotelRoomType) {
            $item = [
                'id' => $hotelRoomType->id,
                'name' => $hotelRoomType->name
            ];
            $hotelRoomTypes[] = $item;
        }
        return $this->success([
            'originalHotelHardCategories' => $originalHotelHardCategories,
            'hotelRoomTypes' => $hotelRoomTypes,
            'hotel_name' => $hotel->name
        ]);
    }

    public function save(HotelHardRequest $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $originalHotelHardCategories = $request->get('originalHotelHardCategories');
        if (empty($originalHotelHardCategories) || count($originalHotelHardCategories) == 0) {
            return $this->error('データが存在しません', 404);
        }

        try {
            \DB::transaction(function () use ($originalHotelHardCategories, $hotelId) {
                foreach ($originalHotelHardCategories as $originalHotelHardCategory) {
                    foreach ($originalHotelHardCategory['originalHotelHardItems'] as $originalHotelHardItem) {

                        $hotelHardItem = HotelHardItem::where('hotel_id', $hotelId)->where('original_hotel_hard_item_id', $originalHotelHardItem['id'])->first();

                        if ($originalHotelHardItem['is_all_room'] == 2) {
                            if (!empty($hotelHardItem)) {
                                $hotelHardItem->delete();
                            }
                            continue;
                        }

                        if (empty($hotelHardItem)) {
                            $hotelHardItem = new HotelHardItem();
                            $hotelHardItem->hotel_id = $hotelId;
                            $hotelHardItem->original_hotel_hard_item_id = $originalHotelHardItem['id'];
                        }
                        $hotelHardItem->fill($originalHotelHardItem);

                        $hotelHardItem->save();

                    }
                }
            });
        } catch (\Exception $e) {
            Log::info('save hotel_hard failed :' . $e);
            return $this->error();
        }

        return $this->success();

    }


}
