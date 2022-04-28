<?php

use App\Models\Component;
use App\Models\Image;
use App\Models\Layout;
use App\Services\HtmlService;
use Illuminate\Database\Seeder;

class LayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('migrate.seeds.skip_layout')) {
            return;
        }

        //add test 30 original layouts
        $components = Component::where('public_status', 1)->whereIn('type', [1, 2])->limit(30)->get();
        $images = Image::where('client_id', 0)->limit(30)->get();
        factory(Layout::class, 30)->make()->each(function ($layout) use ($components, $images) {
            $component = $components->random();
            $layout->component_id = $component->id;
            if ($component->typeName() == 'normal') {
                $html = $this->createNormalHtml($images);
            } else {
                $html = $this->createPopuplHtml($images);
            }
            $layout->html = $html;
            $layout->render_html = HtmlService::instance()->getLayoutRenderHtml($html, $component->html);
            $layout->preview_image = $images->random()->src();
            $layout->save();
        });
    }

    public function createNormalHtml($images)
    {
        $index = rand(1, 5);
        $html = '';
        switch ($index) {
            case 1:
                $html = $this->editTextHtml();
                break;

            case 2:
                $html = $this->editTextHtml();
                break;

            case 3:
                $html = $this->editImageHtml($images->random());
                break;

            case 4:
                $html = $this->editBackgroundHtml();
                break;

            case 5:
                $html = $this->editActionsHtml();
                break;

            default:
                # code...
                break;
        }

        return $html;
    }

    public function createPopuplHtml($images)
    {
        $index = rand(1, 3);
        $html = '';
        switch ($index) {
            case 1:
                $html = $this->popupHtml1();
                break;

            case 2:
                $html = $this->popupHtml2($images->random());
                break;

            case 3:
                $html = $this->popupHtml3();
                break;

            default:
                # code...
                break;
        }

        return $html;
    }

    public function editTextHtml()
    {
        $html = '<div class="layout-test"><span nocode-edit-type="text">this text can edit</span></div>';

        return $html;
    }

    public function editLinkHtml()
    {
        $html = '<div class="layout-test"><a nocode-edit-type="link" href="https://www.yahoo.co.jp/">Yahoo!Japan</a></div>';

        return $html;
    }

    public function editImageHtml($image)
    {
        $html = '<div class="layout-test"><img nocode-edit-type="image delete copy" src="' . $image->src() . '" /></div>';

        return $html;
    }

    public function editBackgroundHtml()
    {
        $html = '<div class="layout-test" nocode-edit-type="background" style="background-color: #c6c6c6;"><span>this background can be edit, this background can be edit, this background can be edit, this background can be edit, this background can be edit, this background can be edit, this background can be edit, this background can be edit, this background can be edit, this background can be edit, this background can be edit, this background can be edit</span></div>';

        return $html;
    }

    public function editActionsHtml()
    {
        $html = '<div class="layout-test"><span>text05</span><div nocode-edit-type="text copy delete">this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete</div></div>';

        return $html;
    }

    public function popupHtml1()
    {
        $html = '<div class="layout-test"><span>this content popup</span><span nocode-edit-type="text">this text can edit</span><div nocode-edit-type="copy delete">this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete, this content can copy and delete</div></div>';

        return $html;
    }

    public function popupHtml2($image)
    {
        $html = '<div class="layout-test"><span>this content popup</span><img nocode-edit-type="image delete copy" src="' . $image->src() . '" /></div>';

        return $html;
    }

    public function popupHtml3()
    {
        $html = '<div class="layout-test"><span>this content popup</span><a nocode-edit-type="link" href="https://www.yahoo.co.jp/">Yahoo!Japan</a><div nocode-edit-type="text copy delete">long text long text long text long text, long text long text long text long text, long text long text long text long text</div></div>';

        return $html;
    }

}
