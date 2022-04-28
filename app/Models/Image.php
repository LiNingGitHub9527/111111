<?php

namespace App\Models;

class Image extends BaseModel
{
    protected $table = 'images';

    protected $fillable = [
        'name'
    ];

    public function src()
    {
    	return $this->path ? photoUrl($this->path) : '';
    }

    public function thumbnail()
    {
    	return $this->path ? photoUrl($this->path) : '';
    }
}
