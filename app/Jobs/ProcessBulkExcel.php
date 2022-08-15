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

class ProcessBulkExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
   

    protected $file_name;
    public function __construct($file_name)
    {
        $this->file_name = $file_name;
    }

    

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): bool
    {
        //

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
             //   Notification::send($company, new AFIEWEN($company, $import_failures->toJson(), $import->errors()->toJson()));
            }else {
              //  Notification::send($company, new AFIEN($company));
            }
            return true;
        }
       return false;
    }


    public function failed(Throwable $exception)
    {
        DB::rollback();
        $user = Auth::user();
        $expenseUser = userExpense::firstOrFail($user->id);
         //send email
         Mail::send('Email.userInvite', [], function ($message) use ($user) {
             $message->to($user);
             $message->subject('AzatMe: BULK UPLOADS FAILS');
         });
       
    }
    
}
