<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;


class userExpense extends Model
{
    //

    use SoftDeletes;



    protected $fillable = [
        'expense_id',
        'principal_id',
        'user_id',
        'payable',
        'payed',
        'payed_date',
        'status',
        'split_method_id',
        'bankName',
        'bankCode',
        'account_number',
        'percentage',
        'percentage_per_user',
        'actualAmount',
        'email',
        'uique_code',
        'name',
        'description',
        'payThruReference',
        'fiName',
        'status',
        'amount',
        'paymentReference',
        'merchantReference',
        'paymentMethod',
        'commission',
        'residualAmount',
        'resultCode',
        'responseDescription'

        
    ];

    public function UserExpense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

   
    public function Uxerexpense()
    {
        return $this->hasOne(splittingMethod::class, 'split_method_id');
    }


    protected $table = "user_expenses";

    public static function getuserExpense($request)
    {
        $user = Auth::user()->id;
        $dateStart = Carbon::parse($request->startDate)
                             ->toDateTimeString();
                             
        $dateEnd = Carbon::parse($request->endDate)
                             ->toDateTimeString();
                             
                            // return $user;
                             
                           //  return [$dateStart,$dateEnd];
        //$Auth_user = Auth::user()->id;
        $records = userExpense::where('principal_id', $user)->whereBetween('created_at', [$dateStart, $dateEnd])->select('name', 'email', 'description', 'actualAmount', 'payable', 'bankName', 'account_number', 'created_at', 'transactionDate')->get();
        return $records;
    }
}
