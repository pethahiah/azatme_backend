<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;


class SendDeletedCustomersEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
     $deletedCustomers = DB::table('customers')
        ->where('flagged', 1)
        ->get();

    // Prepare the email content
    $emailContent = "List of deleted customers:\n";
    foreach ($deletedCustomers as $customer) {
        $emailContent .= "- Name: {$customer->customer_name}, Email: {$customer_email->email}\n";
    }

    // Send the email
    $adminEmail = 'support@pethahiah.com'; 
    Mail::raw($emailContent, function ($message) use ($adminEmail) {
        $message->to($adminEmail)
            ->subject('Deleted Customers List');
    });

    }
}
