<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\HotelRoomTypeRequest;
use App\Models\Hotel;
use App\Models\HotelRoomType;
use App\Models\HotelRoomTypeBed;
use App\Models\HotelRoomTypeImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HotelRoomTypeController extends ApiBaseController
{

    public function list($id, Request $request)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $query = HotelRoomType::query();
        $hotelRoomTypeId = $request->get('hotel_room_type_id');
        if (!empty($hotelRoomTypeId)) {
            $query->where('id', $hotelRoomTypeId);
        }

        $list = $query->where('hotel_id', $id)->orderBy('sort_num', 'Asc')->paginate(20);
        $records = [];
        foreach ($list as $item) {
            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'adult_num' => $item->adult_num,
                'child_num' => $item->child_num,
                'room_size' => $item->room_size,
                'sort_num' => $item->sort_num
            ];
            $records[] = $row;
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
            ]
        ];
        return $this->success($data);
    }

    public function search(Request $request)
    {
        $records = [];
        $q = $request->get('q');
        $hotelId = $request->get('hotel_id');
        if (!empty($q) && !empty($hotelId)) {
            $hotelId = $request->get('hotel_id');
            $query = HotelRoomType::query();
            $list = $query->where('hotel_id', $hotelId)->where('name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }

    public function init($id)
    {
        $hotel = Hotel::where('id', $id)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        return $this->success($this->getRelatedData($hotel));
    }

    public function detail($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $hotelRoomType = $hotel->hotelRoomTypes->find($id);
        if (empty($hotelRoomType)) {
            return $this->error('データが存在しません', 404);
        }
        $hotelRoomTypeBeds = [];
        $hotelRoomTypeBedList = $hotelRoomType->hotelRoomTypeBeds;
        foreach ($hotelRoomTypeBedList as $item) {
            $hotelRoomTypeBed = [
                'id' => $item->id,
                'bed_size' => $item->bed_size,
                'bed_num' => $item->bed_num,
            ];
            $hotelRoomTypeBeds[] = $hotelRoomTypeBed;
        }


        $hotelRoomTypeImages = [];
        $hotelRoomTypeImageList = $hotelRoomType->hotelRoomTypeImages;
        foreach ($hotelRoomTypeImageList as $item) {
            $hotelRoomTypeImage = [
                'id' => $item->id,
                'image' => $item->imageSrc(),
            ];
            $hotelRoomTypeImages[] = $hotelRoomTypeImage;
        }

        $detail = [
            'id' => $hotelRoomType->id,
            'hotel_id' => $hotelRoomType->hotel_id,
            'name' => $hotelRoomType->name,
            'room_num' => $hotelRoomType->room_num,
            'adult_num' => $hotelRoomType->adult_num,
            'child_num' => $hotelRoomType->child_num,
            'room_size' => $hotelRoomType->room_size,
            'sort_num' => $hotelRoomType->sort_num,
            'hotelRoomTypeBeds' => $hotelRoomTypeBeds,
            'hotelRoomTypeImages' => $hotelRoomTypeImages,
        ];
        $data = $this->getRelatedData($hotelRoomType->hotel);
        $data['detail'] = $detail;
        return $this->success($data);
    }

    function getRelatedData($hotel)
    {
        $bedTypes = config('bed.bed_types');
        $bedSizeOptions = [];
        $bedSizeOptions[] = ['text' => '選択してください', 'value' => 0];
        foreach ($bedTypes as $key => $value) {
            $bedSizeOptions[] = ['text' => $value, 'value' => $key];
        }
        return [
            'bedSizeOptions' => $bedSizeOptions,
            'hotel_name' => $hotel->name,
        ];
    }

    public function save(HotelRoomTypeRequest $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::find($hotelId);
        $doNotSaveBedsData = $hotel->business_type == 1 ? TRUE: FALSE;
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $id = $request->get('id');
        if (empty($id)) {
            $hotelRoomType = new HotelRoomType();
            $hotelRoomType->room_num = 0;
            $maxNum = HotelRoomType::where('hotel_id', $hotelId)->max('sort_num');
            $hotelRoomType->sort_num = empty($maxNum) ? 0 : $maxNum + 1;
        } else {
            $hotelRoomType = $hotel->hotelRoomTypes->find($id);
            if (empty($hotelRoomType)) {
                return $this->error('データが存在しません', 404);
            }
        }
        if ($hotel->business_type == 1) {
            $data = $request->all();
        } else {
            $data = $request->only(['name', 'adult_num', 'hotelRoomTypeImages', 'hotel_id', 'room_num']);
            $data['child_num'] = 0;
            $data['room_size'] = 0;
        }
        try {
            \DB::transaction(function () use ($id, $data, $hotelRoomType, $doNotSaveBedsData) {

                $hotelRoomType->fill($data);
                $hotelRoomType->save();

                $roomTypeId = $hotelRoomType->id;

                $hotelRoomType->hotelRoomTypeImages()->get()->each->delete();

                if ($doNotSaveBedsData) {
                    $hotelRoomType->hotelRoomTypeBeds()->get()->each->delete();
                    foreach ($data['hotelRoomTypeBeds'] as $bed) {
                        $hotelRoomTypeBed = new HotelRoomTypeBed();
                        $hotelRoomTypeBed->room_type_id = $roomTypeId;
                        $hotelRoomTypeBed->fill($bed);
                        $hotelRoomTypeBed->save();
                    }
                }

                foreach ($data['hotelRoomTypeImages'] as $image) {
                    $hotelRoomTypeImage = new HotelRoomTypeImage();
                    $hotelRoomTypeImage->room_type_id = $roomTypeId;
                    $hotelRoomTypeImage->image = str_replace(config('aws.url') . '/', '', $image['image']);
                    $hotelRoomTypeImage->save();
                }
            });
        } catch (\Exception $e) {
            Log::info('save failed :' . $e);
            return $this->error();
        }
        return $this->success();
    }

    public function delete($id, Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $hotel = Hotel::where('id', $hotelId)->where('client_id', $this->user()->id)->first();
        if (empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }

        $hotelRoomType = $hotel->hotelRoomTypes->find($id);
        if (empty($hotelRoomType)) {
            return $this->error('データが存在しません', 404);
        }

        $beUsed = HotelRoomType::beUsed($id);
        if ($beUsed) {
            return $this->error('部屋タイプは利用されていますので、削除できません', 1006);
        }
        try {
            \DB::transaction(function () use ($hotelRoomType) {
                $hotelRoomType->delete();
            });
        } catch (\Exception $e) {
            Log::info('delete failed:' . $e);
            return $this->error('delete failed', 500);
        }
        return $this->success();
    }

    public function check($id)
    {
        return $this->success(HotelRoomType::beUsed($id));
    }

    public function sort(Request $request)
    {
        $ids = $request->get('ids');
        try {
            \DB::transaction(function () use ($ids) {
                foreach ($ids as $index => $id) {
                    HotelRoomType::find($id)->update(['sort_num' => $index]);
                }
            });
        } catch (\Exception $e) {
            Log::info('sort failed :' . $e);
            return $this->error();
        }

        return $this->success();
    }


}
