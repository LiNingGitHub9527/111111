<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\LayoutRequest;
use App\Models\Component;
use App\Models\Layout;
use App\Services\HtmlService;
use App\Support\Html\CssFileWrap;
use App\Support\Upload\Uploader;
use Illuminate\Http\Request;

class LayoutController extends ApiBaseController
{
    public function list(Request $request)
    {
        $query = Layout::query();
        $layoutId = $request->get('layout_id');
        if (!empty($layoutId)) {
            $query->where('id', $layoutId);
        }

        $list = $query->orderBy('sort_num', 'DESC')->paginate(20);

        $components = Component::options(false);

        $records = [];
        foreach ($list as $layout) {
            $row = [
                'id' => $layout->id,
                'name' => $layout->name,
                'component_name' => $components[$layout->component_id] ?? '',
                'css_file_name' => $layout->css_file_name,
                'js_file_name' => $layout->js_file_name,
                'public_status' => $layout->public_status == 1 ? '公開中' : '非公開',
                'sort_num' => $layout->sort_num,
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

    public function detail($id)
    {
        $layout = Layout::find($id);
        if (!empty($layout)) {
            $components = Component::options(false);
            $detail = [
                'id' => $layout->id,
                'name' => $layout->name,
                'component_name' => $components[$layout->component_id] ?? '',
                'html' => $layout->html,
                'css_file_name' => $layout->css_file_name ?? '',
                'css_path' => $layout->sourceCssFile(),
                'js_file_name' => $layout->js_file_name ?? '',
                'preview_image_path' => $layout->imageSrc(),
                'public_status' => $layout->public_status,
                'sort_num' => $layout->sort_num,
            ];

            $components = Component::options();
            $data = [
                'detail' => $detail,
                'component_id' => $layout->component_id,
                'components' => $components,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function update(LayoutRequest $request)
    {
        $id = $request->get('id');
        $layout = Layout::find($id);
        if (!empty($layout)) {
            $componentId = $request->get('component_id');
            $component = Component::find($componentId);
            if (empty($component)) {
                return $this->error('データが存在しません', 404);
            }

            $data = $request->all();
            unset($data['component_name']);
            $data['render_html'] = HtmlService::instance()->getLayoutRenderHtml($data['html'], $component->html);

            if (!empty($data['css_file_name']) && ($data['css_file_name'] != 'null')) {
                if (isset($data['css_file'])) {
                    unset($data['css_file']);
                }
                unset($data['css_file_name']);
            } else {
                if (isset($data['css_file'])) {
                    $cssFile = $request->file('css_file');
                    if (!empty($cssFile)) {
                        $data['css_file_name'] = $cssFile->getClientOriginalName();
                        $data['css_file'] = Uploader::instance()->uploadFile($cssFile, 'css/0');
                    }
                } else {
                    $data['css_file_name'] = '';
                    $data['css_file'] = '';
                }
            }

            if (!empty($data['js_file_name']) && ($data['js_file_name'] != 'null')) {
                if (isset($data['js_file'])) {
                    unset($data['js_file']);
                }
                unset($data['js_file_name']);
            } else {
                if (isset($data['js_file'])) {
                    $jsFile = $request->file('js_file');
                    if (!empty($jsFile)) {
                        $data['js_file_name'] = $jsFile->getClientOriginalName();
                        $data['js_file'] = Uploader::instance()->uploadFile($jsFile, 'js/0');
                    }
                } else {
                    $data['js_file_name'] = '';
                    $data['js_file'] = '';
                }
            }

            $image = $request->file('preview_image');
            if (!empty($image)) {
                $data['preview_image'] = Uploader::instance()->uploadImg($image, '0');
            }

            $layout->fill($data);
            if ($layout->save()) {
                if (!empty($data['css_file'])) {
                    $id = $layout->id;
                    $cssContent = file_get_contents($cssFile);
                    $mark = '.nocode-layout-' . $id;
                    $wrapContent = CssFileWrap::prepend($mark, $cssContent);
                    Uploader::instance()->uploadFileWithPathAndContent($data['css_file'], $id, $wrapContent);

                    $editContent = CssFileWrap::prepend4Media('.desktop-mode', $wrapContent);
                    Uploader::instance()->uploadFileWithPathAndContent($data['css_file'], $id . '_edit', $editContent);
                }
            }

            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function create(LayoutRequest $request)
    {
        $data = $request->all();
        $component = Component::find($data['component_id']);
        if (empty($component)) {
            return $this->error('データが存在しません', 404);
        }
        $data['render_html'] = HtmlService::instance()->getLayoutRenderHtml($data['html'], $component->html);

        $cssFile = $request->file('css_file');
        $jsFile = $request->file('js_file');

        if (!empty($cssFile)) {
            $data['css_file_name'] = $cssFile->getClientOriginalName();
            $data['css_file'] = Uploader::instance()->uploadFile($cssFile, 'css/0');
        }
        if (!empty($jsFile)) {
            $data['js_file_name'] = $jsFile->getClientOriginalName();
            $data['js_file'] = Uploader::instance()->uploadFile($jsFile, 'js/0');
        }

        $image = $request->file('preview_image');
        if (!empty($image)) {
            $data['preview_image'] = Uploader::instance()->uploadImg($image, '0');
        }

        $layout = new Layout;
        $layout->fill($data);
        if ($layout->save()) {
            if (!empty($data['css_file'])) {
                $id = $layout->id;
                $cssContent = file_get_contents($cssFile);
                $mark = '.nocode-layout-' . $id;
                $wrapContent = CssFileWrap::prepend($mark, $cssContent);
                Uploader::instance()->uploadFileWithPathAndContent($data['css_file'], $id, $wrapContent);

                $editContent = CssFileWrap::prepend4Media('.desktop-mode', $wrapContent);
                Uploader::instance()->uploadFileWithPathAndContent($data['css_file'], $id . '_edit', $editContent);
            }
        }

        return $this->success();
    }

    public function delete($id)
    {
        $layout = Layout::find($id);
        if (!empty($layout)) {
            $layout->delete();
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function initData()
    {
        $components = Component::options();

        $data = [
            'components' => $components,
        ];
        return $this->success($data);
    }

    public function search(Request $request)
    {
        $records = [];
        $q = $request->get('q');
        if (!empty($q)) {
            $list = Layout::where('name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                $components = Component::options(false);
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->name,
                        'description' => $item->public_status == 1 ? '公開中' : '非公開',
                        'price' => $components[$item->component_id] ?? '',
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }

    public function check($id)
    {
        return $this->success(Layout::beUsed($id));
    }

}
