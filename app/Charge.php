<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static findOrFail($id)
 * @method static create(array $array)
 */
class Charge extends Model
{
    //

    use SoftDeletes;


    protected $fillable = ['product_affected', 'charges'];

}
