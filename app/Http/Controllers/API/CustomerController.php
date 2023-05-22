<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CustomerRequest;
use App\Customer;
use App\Business;
use Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    //

    public function createCustomer(Request $request, $business_code)
    {

        $business_code = Business::where('business_code', $business_code)->select('business_code')->first();
       // return $business_code;
        $customer = new Customer([
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'customer_code' => $business_code->business_code,
            'owner_id' => Auth::user()->id,
        ]);

        

        $cus = Customer::where('customer_email', $request->customer_email)->get();
        $num = 1;
        if(sizeof($cus) > 0){
            // tell business not to duplicate same email
            $customerFlagged = Customer::where(['customer_name' => $request->customer_name, 'customer_email' => $request->customer_email, 'customer_phone' => $request->customer_phone])->update([
                'flagged' => $num,
            ]);
        }
        $customer->save();
        return response()->json($customer);

    }

    public function updateCustomer(Request $request, $id)
    {
        return response()->json($request->input());
        $getAdmin = Auth::user();
        $getAd = $getAdmin -> usertype;
            if($getAd === 'merchant')
            {
                $user=Customer::find($id);
        
        $name = $request->customer_name;
        //return $name;
        DB::table('businesses')
                ->where('id',$id)
                ->update([
                    'customer_name'=>$name,
                ]);
                // $update = Customer::find($id);
                // $update->customer_name = $request->input('customer_name');
                // $update->customer_phone = $request->input('customer_phone');
                // $update->update();
            return response()->json($name);
            }
            else{
            return response()->json('You are not authorize to perform this action');
            }

    }
    
   public function getAllCustomersUnderABusiness($business_code)
    {
       
        $getAllCustomer = Customer::where('customer_code', $business_code)->latest()->get();
        return response()->json($getAllCustomer);

    }

    
    public function listAllCustomer($owner_id)
    {
       
        $owner = Business::where('owner_id', $owner_id)->select('owner_id')->first()->owner_id;
        $getAllCustomer = Customer::where('owner_id', $owner)->latest()->get();
        return response()->json($getAllCustomer);

    }
    
     public function deleteACustomer($id)
        {
        $deleteCustomer = Customer::findOrFail($id);
        $getAdmin = Auth::user();
        $getAd = $getAdmin -> usertype;
            if($getAd === 'merchant')
            {
            $deleteCustomer->delete();
            }else{
            return response()->json('You are not authorize to perform this action');
            }

        }


   public function getAllCustomers()
    {
        $getAllCustomer = Customer::get();
        return response()->json($getAllCustomer);

    }


}
