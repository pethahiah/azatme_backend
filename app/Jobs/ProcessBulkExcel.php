<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\userExpense;
use Illuminate\Support\Facades\Mail;

class ProcessBulkExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file_name;

    public function __construct($file_name)
    {
        $this->file_name = $file_name;
    }

    public function handle()
    {
        if (Storage::disk('local')->exists('excel import/'.$this->file_name)) {
            $import = new userExpense();
            $import->import('excel import/'.$this->file_name);
            $import_failures = collect();
            if(!empty($import->failures()->toArray()))
            {
                foreach ($import->failures() as $failure) {
                    $data = [
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors(),
                        'values' => $failure->values(),
                    ];
                    $import_failures->push($data);
                }
                // send Error Email notification
            }else {
                //send Success Email notification
            }
            return true;
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
