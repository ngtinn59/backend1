<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jlevel extends Model
{
    use HasFactory;
    protected $table = 'jlevels';

    protected $primaryKey = 'id';
    protected $guarded = [];
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
