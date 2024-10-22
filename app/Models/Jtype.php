<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jtype extends Model
{
    use HasFactory;
    protected $table = 'jtypes';

    protected $primaryKey = 'id';
    protected $guarded = [];

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
