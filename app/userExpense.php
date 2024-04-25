<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Expense;
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
        'responseDescription',
	'uidd',
	'first_name',
	'last_name',
	'minus_residual',
	'has_updated_minus_rsidual',
	'stat',
	'providedEmail',
	'providedName',
	'remarks',
	'negative_amount'
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
//        $user = Auth::user()->id;
  //      $dateStart = Carbon::parse($request->startDate)
    //                         ->toDateTimeString();

      //  $dateEnd = Carbon::parse($request->endDate)
        //                     ->toDateTimeString();

    $getAuthUser = Auth::user();
    $startDate = $request->start_date; // Specify your start date
    $endDate = $request->end_date;   // Specify your end date

    // Fetch expense IDs within the date range
//    $expenseIds = DB::table('expenses')
  //      ->join('user_expenses', function ($join) {
    //        $join->on('expenses.user_id', '=', 'user_expenses.principal_id')
      //          ->on('expenses.id', '=', 'user_expenses.expense_id');
       // })
       // ->select('expenses.id')
      //  ->where('expenses.user_id', '=', $getAuthUser->id)
      //  ->whereNotNull('expenses.subcategory_id')
       // ->whereBetween('expenses.created_at', [$startDate, $endDate])
       // ->groupBy('expenses.id')
       // ->pluck('expenses.id');

    // Fetch expenses within the date range
   // $records = DB::table('expenses')
     //   ->join('user_expenses', function ($join) {
       //     $join->on('expenses.user_id', '=', 'user_expenses.principal_id')
         //       ->on('expenses.id', '=', 'user_expenses.expense_id');
      //  })
//        ->select('expenses.*', DB::raw('SUM(user_expenses.residualAmount) as total_paid'))
//->select('expenses.*', 'user_expenses.*')
  //      ->whereIn('expenses.id', $expenseIds)
    //    ->whereBetween('expenses.created_at', [$startDate, $endDate])
      //  ->get();
                            // return $user;

                           //  return [$dateStart,$dateEnd];
        //$Auth_user = Auth::user()->id;
$expenseIds = DB::table('expenses')
        ->where('user_id', $getAuthUser->id)
        ->whereNotNull('subcategory_id')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->pluck('id');

    // Fetch expenses within the date range
    $expenses = DB::table('expenses')
        ->whereIn('id', $expenseIds)
        ->get();

    // Fetch user expenses within the date range
    $records = DB::table('user_expenses')
        ->whereIn('expense_id', $expenseIds)
        ->get();
      //  $records = userExpense::where('principal_id', $user)->whereBetween('created_at', [$dateStart, $dateEnd])->select('name', 'email', 'description', 'actualAmount', 'payable', 'bankName', 'account_number', 'created_at', 'transactionDate')->get();
        return ($records);
    }
}
