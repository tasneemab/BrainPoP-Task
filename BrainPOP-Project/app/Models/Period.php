<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;


class Period extends Model
{
    use HasFactory;
    use softDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'teacher_id',
    ];

    /**
    * Get the teacher that owns the period.
    */
    public function teacher()
    {
      return $this->belongsTo('App\Models\teacher');
    }

    /**
     * Get the students for the period.
     */
    public function students()
    {
      return $this->belongsToMany('App\Models\student');
    }
}
