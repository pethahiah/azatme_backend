<?php

namespace App\bulkImport;

use App\userExpense;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class FacilityDataImport implements ToModel, WithUpserts, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable, SkipsFailures, SkipsErrors;

    public function model(array $row)
    {
        return new userExpense([

          'expense_id
          description
          principal_id
          payable
          split_method_id
          email
          user_id'
       
            
         

            'email' => $row = $this->userEmailToId($userid)['email'],

            'author_id' => 1,
        ]);
    }

    public function uniqueBy()
    {
        return 'email';
    }

    public function rules(): array
    {
        return [
           
            
            'email' => [
                'email',
                'exists:users,email',
            ],
        ];
    }
   

    function userEmailToId($id){
        $user = User::where('id',$id)->first();
         return $user->id;
    }
}
