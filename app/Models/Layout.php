<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Layout extends BaseModel
{
    use SoftDeletes;
    
    protected $table = 'layouts';

    protected $fillable = [
        'name', 'component_id', 'html', 'render_html', 'css_file_name', 'js_file_name', 'css_file', 'js_file', 'preview_image', 'public_status', 'sort_num'
    ];

    public function component()
    {
        return $this->belongsTo('\App\Models\Component', 'component_id', 'id');
    }

    public function sourceCssFile()
    {
        return $this->css_file ? fileUrl($this->css_file) : '';
    }

    public function parsedCssFile()
    {
        if (!empty($this->css_file)) {
            $pathInfo = pathinfo($this->css_file);
            $file = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $this->id . '.' . $pathInfo['extension'];
            return fileUrl($file);
        }

        return '';
    }

    public function jsFile()
    {
        return $this->js_file ? fileUrl($this->js_file) : '';
    }

    public function imageSrc()
    {
        return $this->preview_image ? photoUrl($this->preview_image) : '';
    }

    public static function beUsed($id)
    {
        $beUsed = false;
        $lpLayout = LpLayout::where("layout_id", $id)->first();
        if (!empty($lpLayout)) {
            $beUsed = true;
        }

        $originalLpLayout = OriginalLpLayout::where("layout_id", $id)->first();
        if (!empty($originalLpLayout)) {
            $beUsed = true;
        }
        return $beUsed;
    }

}
