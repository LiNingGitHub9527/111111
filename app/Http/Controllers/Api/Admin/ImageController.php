<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Image;
use App\Http\Requests\Api\ImageRequest;
use App\Support\Upload\Uploader;

class ImageController extends ApiBaseController
{
    public function list(Request $request)
    {
        $query = Image::query();
        $imageId = $request->get('image_id');
        if (!empty($imageId)) {
            $query->where('id', $imageId);
        }

        $pageSize = (int)$request->get('pagesize', 20);
        if ($pageSize == 0) {
            $pageSize = 20;
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
        $records = [];
        $q = $request->get('q');
        if (!empty($q)) {
            $list = Image::where('name', 'like', '%'.$q.'%')->limit(10)->get();
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
        $images = $request->file('images');
        foreach ($images as $img) {
            $name = $img->getClientOriginalName();
            $path = Uploader::instance()->uploadImg($img, '0');
            if ($path) {
                $image = new Image;
                $image->name = $name;
                $image->client_id = 0;
                $image->hotel_id = 0;
                $image->path = $path;
                $image->save();
            }

        }
        return $this->success();
    }
}
