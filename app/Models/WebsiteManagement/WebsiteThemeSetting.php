<?php

namespace App\Models\WebsiteManagement;

use Illuminate\Database\Eloquent\Model;

class WebsiteThemeSetting extends Model
{
    protected $table = 'website_theme_settings';

    protected $fillable = ['key', 'value', 'group'];
}
