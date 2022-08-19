<?php

namespace App\bulkImport;

use App\User;
use App\userExpense;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class UserExpenseImport implements ToModel, WithUpserts, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable, SkipsFailures, SkipsErrors;

    protected $expense, $auth_user_id;

    public function __construct($expense, $auth_user_id)
    {
        $this->expense = $expense;
        $this->auth_user_id = $auth_user_id;
    }

    public function model(array $row)
    {
        return new userExpense([
            'name' => $this->$this->expense->name,
            'expense_id' => $this->expense->id,
            'description' =>$this->expense->description,
            'principal_id' => $this->auth_user_id,
            'payable' => $this->expense->amount,
            'split_method_id' => $row['split_method_id'],
            'user_id' => $this->userEmailToId($row['email']),
        ]);
    }

    public function uniqueBy()
    {
        return 'email';
    }

    public function rules(): array
    {
        return [
            'expense_id' => [
                'required',
                'integer'
            ],
            'description' => [
                'string'
            ],
            'principal_id' => [
                'integer'
            ],
            'payable' => [
                'boolean'
            ],
            'split_method_id' => [
                'integer'
            ],
            'email' => [
                'email',
                'exists:users,email',
            ],
            'user_id' => [
                'nullable',
                'integer'
            ],
        ];
    }

    private function userEmailToId($email){
        return User::select('id')->where('email',$email)->first()->value('id');
    }
}
