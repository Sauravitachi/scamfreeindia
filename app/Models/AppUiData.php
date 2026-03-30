<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUiData extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'app_ui_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
    ];
}
