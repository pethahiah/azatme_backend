<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::namespace('API')->group(function () {
    Route::post('AttemptLogin', 'AuthController@AttemptLogin');
    Route::post('register', 'AuthController@register');
    Route::post('loginViaOtp', 'AuthController@loginViaOtp');
    Route::post('forgot', 'ForgotController@forgot');
    Route::post('reset', 'ForgotController@reset');
    Route::get('getAllUser', 'AuthController@getAllUser');
    Route::get('getBanks', 'BankController@ngnBanksApiList');
    Route::get('getToken', 'AuthController@signin');
    Route::post('createparam', 'SettingController@param');
    Route::get('token', 'SettingController@returnToken');
    Route::post('/updateStatus', 'ExpenseController@webhookExpenseResponse');
    Route::post('/kontributewebhook', 'GroupController@webhookGroupResponse');
    Route::post('/businesswebhook', 'BusinessTransactionController@webhookBusinessResponse');
    Route::post('/kontributewebhook', 'GroupController@groupSettlementWebhookResponse');
    Route::post('/businesswebhook', 'BusinessTransactionController@businessSettlementWebhookResponse');
    Route::post('/updateStatus', 'ExpenseController@refundmeSettlementWebhookResponse');
    Route::post('/contact-us', 'SheetController@externalContentPostMethod');
    
    
     //NQR  Aggr Merchant Services
    Route::post('nqr-merchant-registration', 'NQRController@NqrMerchantRegistration');
    Route::post('create-merchant-collection-account', 'NQRController@merchantCollectionAccount');
    Route::get('get-merchant-number/{merchantNumber}', 'NQRController@getMerchantNumber');
    Route::post('create-sub-merchant', 'NQRController@createSubMerchant');
    Route::get('get-all-submerchant-under-merchant/{id}', 'NQRController@getSubMerchantUnderAllMerchant');
    Route::post('get-specific-submerchnat-under-merchant/{id}', 'NQRController@getSpecificSubMerchantUnderAMerchant');
    Route::get('get-specific-merchant-info/{merchantNumber}', 'NQRController@getSpecificSubMerchantInfo');
    Route::post('get-merchant-trans-report/{merchantNumber}', 'NQRController@getMerchantTransactionReport');
    Route::post('generate-dynamic-qrcode/{merchantNumber}', 'NQRController@generateDynamicQrCode');
    Route::post('get-merchant-transaction-status', 'NQRController@merchantTransactionStatus');

    //NQR  Store Merchant Services
    Route::post('store-generate-dyanmic-qrcode', 'NqrStoreController@storeGenerateDyanmicQrCode');
    Route::post('get-store-trans-report', 'NqrStoreController@getStoreTransactionReport');
    Route::post('get-store-trans-status', 'NqrStoreController@storeTransactionStatus');
    Route::get('get-store', 'NqrStoreController@getStore');
    Route::post('create-store', 'NqrStoreController@createStore');
    Route::get('get-specific-submerchant-list-Instore/{id}', 'NqrStoreController@getListSpecificSubMerchantInStore');
    

Route::middleware(['auth:api'])->group(function () {
    // User Update
    Route::get('getProfile', 'AuthController@getProfile');
    Route::get('logout', 'AuthController@logout');
    Route::put('updateProfile', 'AuthController@updateProfile');
    Route::post('image', 'AuthController@uploadImage');
    Route::put('updateUsertype', 'AuthController@updateUsertype');
    Route::post('category', 'ExpenseCategoryController@category');
    
    
     //Mobile Verification
    Route::post('verify-phone-number', 'MobileVerificationController@verifyPhone');
    Route::post('check-mobile-number', 'MobileVerificationController@checkOtp');
    Route::post('send-otp', 'MobileVerificationController@EmailVerification');
    Route::put('confirm-email', 'MobileVerificationController@ConfirmEmailViaOtp');
    Route::get('send-otp-mobile/{username}', 'ExpenseController@sendSmsMessage');
    
    
    
    
     //Buisness
    Route::post('createBusiness', 'BusinessController@createBusiness');
    Route::get('list-all-business-users', 'AuthController@listAllBusinessUsers');
    Route::put('update-business/{id}', 'BusinessController@updateBusiness');
    Route::post('create-business', 'BusinessController@createBusiness');
    Route::get('get-business-under-a-owner/{owner_id}', 'BusinessController@getAllBusiness');
    Route::get('get-a-single-business-under-owner/{business_code}', 'BusinessController@getABusiness');
    Route::delete('delete-a-business/{id}', 'BusinessController@deleteABusiness');
    Route::get('gac-under-a-specific-business/{customer_code}', 'BusinessController@getAllCustomersUnderABusiness');
    
    
    

    //B2B Transactions
    Route::post('create-product', 'BusinessTransactionController@creatProduct');
    Route::get('all-product', 'BusinessTransactionController@getAllProductsPerBusinessMerchant');
    Route::get('product-per-business/{businessCode}', 'BusinessTransactionController@getProductsPerBusiness');
    Route::post('initiate-business-transaction/{product_id}', 'BusinessTransactionController@startBusinessTransaction');
    Route::post('create-option', 'MotoController@moto');
    Route::get('get-option', 'MotoController@getMotoMethod');
    Route::post('create-vat', 'VatController@createVat');
    Route::get('all-invoices-created-by-business-owner', 'BusinessTransactionController@getAllInvoiceByABusinessOwner');
    Route::get('count-all-invoices-created-by-business-owner', 'BusinessTransactionController@countAllInvoiceByABusinessOwner');
     Route::get('get-all-invoices-received-by-customer', 'BusinessTransactionController@getAllInvoiceRecievedByACutomer');
      Route::get('count-all-invoices-recieved-by-business', 'BusinessTransactionController@countAllInvoiceRecievedByACutomer');
      Route::post('business-settlements/{BusinessTransactionId}', 'BusinessTransactionController@AzatBusinessCollection');
       Route::get('customer-invoice/{customerEmail}', 'BusinessTransactionController@getAllInvoiceSentToAParticularCustomer');
       Route::get('get-all-transactions-created-by-a-specific-business/{business_code}', 'BusinessTransactionController@getAllTransactionsByASpecificBusiness');
       Route::get('get-all-customers-under-a-specific-business/{business_code}', 'BusinessTransactionController@getAllCustomersUnderASpecificBusiness');
       
      
    
    //Email Template for Business
    
     Route::post('send-notification/{id}', 'MailTemplateController@mailNotification');
    Route::get('get-customer-mails', 'MailTemplateController@getAllMails');
    

    //Customer
    Route::post('create-customer/{business_code}', 'CustomerController@createCustomer');
    Route::put('update-customer/{id}', 'CustomerController@updateCustomer');
    Route::get('get-customers-under-a business/{owner_id}', 'CustomerController@listAllCustomer');
    Route::delete('delete-a-customer/{id}', 'CustomerController@deleteACustomer');
    Route::get('gac-under-a-specific-business/{customer_code}', 'CustomerController@getAllCustomersUnderABusiness');
     
    
    //Expense
    
    Route::post('createExpense', 'ExpenseController@createExpense');
    Route::post('userExpense/{expenseUniqueCode}', 'ExpenseController@inviteUserToExpense');
    Route::put('updateExpense/{id}', 'ExpenseController@updateExpense');
    Route::get('getAllExpenses', 'ExpenseController@getAllExpenses');
    Route::get('getRandomUserExpense/{email}', 'ExpenseController@getRandomUserExpense');
    Route::delete('deleteInvitedExpenseUser/{user_id}', 'ExpenseController@deleteInvitedExpenseUser');
    Route::delete('deleteExpense/{id}', 'ExpenseController@deleteExpense');
    Route::get('getUserDeletedExpense', 'ExpenseController@getUserDeletedExpense');
    Route::get('getUserExpense', 'ExpenseController@getUserExpense');
    Route::get('getUserDeletedExpenseInvite', 'ExpenseController@getUserDeletedExpenseInvite');
    Route::get('getAllMemebersOfAnExpense/{expenseId}', 'ExpenseController@getAllMemebersOfAnExpense');
    Route::get('getAmountsPaidPerExpense/{expenseId}', 'ExpenseController@getUserAmountsPaidPerExpense');
    Route::get('getTotalNumberOfPaidUsersPerExpense/{expenseId}', 'ExpenseController@getTotalNumberOfPaidUsersPerExpense');
    Route::post('/export-excel', 'ExpenseController@exportExpenseToExcel');
    Route::post('/export-csv', 'ExpenseController@exportExpenseToCsv');
    Route::post('collection/{transactionId}', 'ExpenseController@AzatIndividualCollection');
    Route::get('get-transaction-status', 'ExpenseController@getStatus');
    Route::post('verify-account', 'ExpenseController@accountVerification');
    Route::get('getResponse', 'ExpenseController@getStatus');
    Route::put('update-payback-transaction/{transactionId}', 'ExpenseController@UpdateTransactionRequest');
    
    
    
    // User Group
    Route::post('createGroup', 'GroupController@createGroup');
    Route::put('updateGroup/{id}', 'GroupController@updateGroup');
    Route::post('inviteUsersToGroup/{groupId}', 'GroupController@inviteUsersToGroup');
    Route::get('countAllGroupsPerUser', 'GroupController@countAllGroupsPerUser');
    Route::get('getAllGroupsPerUser', 'GroupController@getAllGroupsPerUser');
    Route::get('getRandomUserGroup/{email}', 'GroupController@getRandomUserGroup');
    Route::delete('deleteInvitedGoupUser/{user_id}', 'GroupController@deleteInvitedGroupUser');
    Route::delete('deleteGroup/{id}', 'GroupController@deleteGroup');
    Route::get('getAllMemebersOfAGroup/{groupId}', 'GroupController@getAllMemebersOfAGroup');
    Route::get('list-users-per-Group/{groupId}', 'GroupController@getUserAmountsPaidPerGroup');
    Route::get('getUserGroup', 'GroupController@getUserGroup');
    Route::post('group-settlement/{transactionId}', 'GroupController@AzatGroupCollection');
    Route::put('update-kontribute-transaction/{transactionId}', 'GroupController@UpdateTransactionGroupRequest');
    
   
    
    
    
    //Bank
    Route::put('updateBank/{bankid}', 'BankController@updateBank');
    Route::post('addBank', 'BankController@addBank');
    Route::get('getBankPerUser', 'BankController@getBankPerUser');
    Route::delete('bank/{id}', 'BankController@bank');
    
   
    
    
    //Sub Category
    Route::put('updateSubCategory/{id}', 'ExpenseSubCategoryController@updateSubCategory');
    Route::post('SubCategory', 'ExpenseSubCategoryController@SubCategory');
    Route::get('getSubCateListPerCategory/{category_id}', 'ExpenseSubCategoryController@getSubCateListPerCategory');
    Route::delete('deleteExpenseSubCategory/{id}', 'ExpenseSubCategoryController@deleteExpenseSubCategory');
    
    
    
    //Category
    Route::put('updateCategory/{id}', 'ExpenseCategoryController@updateCategory');
    Route::get('allCategoriesPerUser', 'ExpenseCategoryController@allCategoriesPerUser');
    Route::get('getCateList', 'ExpenseCategoryController@getCateList');
    Route::delete('deleteExpenseCategory/{id}', 'ExpenseCategoryController@deleteExpenseCategory');
    
    
    // Wallet
    Route::get('get-ledger', 'WalletController@createWallet');
     
    //Reporting
    Route::get('allExpensesPerUser', 'ExpenseController@allExpensesPerUser');
    Route::get('countExpensesPerUser', 'ExpenseController@countExpensesPerUser');
    Route::post('getUserExpenseWithDate', 'ExpenseController@getUserExpenseWithDate');
    Route::post('getUserGroupWithDate', 'ExpenseController@getUserGroupWithDate');
    Route::post('getUserExpenseWithCategory/{categoryId}', 'ReportingController@getUserExpenseWithCategory');
    Route::post('getUserExpenseWithSubCategory/{sub_categoryId}', 'ReportingController@getUserExpenseWithSubCategory');
    
    //Splitting Methods
    Route::post('splitingMethod', 'PaymentSplittingController@splitingMethod');
    Route::get('getSplittingMethods', 'PaymentSplittingController@getSplittingMethods');

    //Complain 
    Route::post('makeComplain', 'ComplainController@makeComplain');
    Route::get('getAllComplains', 'ComplainController@getAllComplains');

   
    
       });

    }); 
       
