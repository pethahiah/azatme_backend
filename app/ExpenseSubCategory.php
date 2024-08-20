<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//use illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseSubCategory extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
        'name',
        'user_id',
        'category_id'
    ];

    public function ExpenseSubCategory()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
