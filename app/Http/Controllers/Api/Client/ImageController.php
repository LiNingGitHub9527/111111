<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\ImageRequest;
use App\Models\Image;
use App\Support\Upload\Uploader;
use Illuminate\Http\Request;

class ImageController extends ApiBaseController
{
    public function list(Request $request)
    {
        $client_id = $this->user()->id;
        $query = Image::where('client_id', $client_id);
        $imageId = $request->get('image_id');
        if (!empty($imageId)) {
            $query->where('id', $imageId);
        }

        $pageSize = (int)$request->get('pagesize', 20);
        if ($pageSize == 0) {
            $pageSize = 20;
        }

        $hotelId = $request->get('hotelId');
        if (!empty($hotelId)) {
            $query->where(function($q) use($hotelId){
                $q->where('hotel_id', $hotelId)->orWhere('hotel_id', 0);
            });
        }

        $list = $query->orderBy('id', 'DESC')->paginate($pageSize);

        $records = [];
        foreach ($list as $item) {
            $row = [
                'id' => $item->id,
                'name' => $item->name,
                'src' => $item->src(),
                'thumbnail' => $item->thumbnail(),
                'created' => $item->created_at->format('Y-m-d H:i'),
            ];
            $records[] = $row;
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
        ];
        return $this->success($data);
    }

    public function update(ImageRequest $request)
    {
        $id = $request->get('id');
        $image = Image::find($id);
        if (!empty($image)) {
            $data = $request->all();
            $image->fill($data);
            $image->save();
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function delete($id)
    {
        $image = Image::find($id);
        if (!empty($image)) {
            $image->delete();
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function search(Request $request)
    {
        $client_id = $this->user()->id;
        $query = Image::query();
        $records = [];
        $q = $request->get('q');
        $hotelId = $request->get('hotelId');
        if (!empty($hotelId)) {
            $query->where(function($q) use($hotelId){
                $q->where('hotel_id', $hotelId)->orWhere('hotel_id', 0);
            });
        }
        if (!empty($q)) {
            $list = $query->where('name', 'like', '%' . $q . '%')->where('client_id', $client_id)->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                        'description' => $item->created_at->format('Y-m-d H:i'),
                        'image' => $item->thumbnail(),
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }

    public function upload(Request $request)
    {
        $client_id = $this->user()->id;
        $images = $request->file('images');
        $hotelId = $request->id;
        foreach ($images as $img) {
            $name = $img->getClientOriginalName();
            $path = Uploader::instance()->uploadImg($img, $client_id);
            if ($path) {
                $image = new Image;
                $image->name = $name;
                $image->client_id = $client_id;
                $image->path = $path;
                $image->hotel_id = $hotelId;
                $image->save();
            }

        }
        return $this->success();
    }
}
