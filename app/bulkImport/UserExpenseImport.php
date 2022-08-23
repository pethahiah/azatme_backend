<?php

namespace App\bulkImport;

use App\User;
use App\userExpense;
use App\splittingMethod;
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

class UserExpenseImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    protected $expense, $auth_user_id;

    public function __construct($expense, $auth_user_id)
    {
        $this->expense = $expense;
        $this->auth_user_id = $auth_user_id;
    }

    public function model(array $row)
    {
        return new userExpense([
            'name' => $this->expense->name,
            'expense_id' => $this->expense->id,
            'description' =>$this->expense->description,
            'principal_id' => $this->auth_user_id,
            'payable' => $this->expense->amount,
            'split_method_id' => $this->splitMethodToSplitId($row['split_method']),
            'user_id' => $this->userEmailToId($row['email']),
        ]);
    }

    // public function uniqueBy()
    // {
    //     return 'email';
    // }

    public function rules(): array
    {
        return [
            'split_method_id' => [
                'integer'
            ],
            'email' => [
                'email',
                'exists:users,email',
            ],
            // 'email' => function($attribute, $value, $onFailure) {
            //     if ($value !== User::select('email')->where('email',$value)->first()->value('email')) {
            //          $onFailure($this->sendEmail($value));
            //     }
            // }
        ];
    }

    private function userEmailToId($email){
        return User::select('id')->where('email',$email)->first()->value('id');  
    }

    private function splitMethodToSplitId($split_method){
        return splittingMethod::select('id')->where('split',$split_method)->first()->value('id');
    }

    private function sendEmail($email)
    {
        //send email
            Mail::send('Email.userInvite', [], function ($message) use ($email) {
                $message->to($email);
                $message->subject('AzatMe: Send expense invite');
            });
    }
}
