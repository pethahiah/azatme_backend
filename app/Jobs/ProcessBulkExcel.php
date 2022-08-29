<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\bulkImport\userExpenseImport;
use Illuminate\Support\Facades\Mail;

class ProcessBulkExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file_name, $expense, $auth_user_id;

    public function __construct($file_name, $expense, $auth_user_id)
    {
        $this->file_name = $file_name;
        $this->expense = $expense;
        $this->auth_user_id = $auth_user_id;
    }

    public function handle()
    {
        if (Storage::disk('local')->exists('excel bulk import/'.$this->file_name)) {
            $import = new userExpenseImport($this->expense, $this->auth_user_id);
            $import->import('excel bulk import/'.$this->file_name );
            $import_failures = collect();
            // if(!empty($import->failures()->toArray()))
            // {
            //     foreach ($import->failures() as $failure) {
            //         $data = [
            //             'row' => $failure->row(),
            //             'attribute' => $failure->attribute(),
            //             'errors' => $failure->errors(),
            //             'values' => $failure->values(),
            //         ];
            //         $import_failures->push($data);
            //     }
            //     // send Error Email notification
            // }else {
            //     //send Success Email notification
            // }
            return response()->json(['message' => 'Data imported successfully'], 200);    
        }
        return false;
    }

    public function failed(Throwable $exception)
    {
        DB::rollback();
        //send failed Email Notification if entire process fails
        $user = Auth::user();
        Mail::send('Email.userInvite', [], function ($message) use ($user) {
            $message->to($user);
            $message->subject('AzatMe: BULK UPLOADS FAILS');
        });
    }
}
