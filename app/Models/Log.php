<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';

    public $incrementing = false;
    protected $keyType = 'string';

    const TYPE_INFO = 'info';
    const TYPE_WARN = 'warn';
    const TYPE_ERROR = 'error';

    const TYPES = [self::TYPE_INFO, self::TYPE_WARN, self::TYPE_ERROR];

    protected $fillable = [
        'time',
        'target_id',
        'target_type',
        'user_id',
        'type',
        'message',
    ];

    protected $casts = [
        'time' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->time)) {
                $model->time = now();
            }
            if (empty($model->type)) {
                $model->type = self::TYPE_INFO;
            }
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
