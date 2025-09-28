<?php

namespace App\Exports;

use App\userExpense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExpenseExport implements FromCollection,WithHeadings
{

    protected $userExpense;

    public function __construct($userExpense)
    {
        $this->userExpense = $userExpense;
    }

    public function headings():array
    {
        return[
        
            'name', 
            'email',
            'description', 
            'actualAmount', 
            'payable', 
            'bankName', 
            'account_number', 
            'created_at', 
            'transactionDate'
        ];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
         return $this->userExpense;
    }
}
