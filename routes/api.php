<?php

use App\Http\Controllers\API\BusinessTransactionController;
use App\Http\Controllers\API\DirectDebitController;
use App\Http\Controllers\API\ReferralSettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ReferralController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\ChargesController;

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
    Route::post('/contact-us', 'SheetController@externalContentPostMethod');
    Route::get('get-all-customers', 'CustomerController@getAllCustomers');
    Route::post('decline-ajo', 'AjoController@declineInvitation');
    Route::post('accept-ajo-invite', 'AjoController@acceptInvitation');
    Route::put('/update-user-email-status', 'AuthController@updateUserEmailStatus');
    Route::post('/agowebhook', 'AjoController@webhookAjoResponse');
    Route::get('get-ajo-user-bank-details/{id}', 'AjoController@getUsersWithBankInfo');
    Route::get('get-ajo-by-id/{ajoId}', 'AjoController@getAjoByIdd');

    Route::post('/process-data', 'SurveyController@handle');
    Route::get('/get-lastUpdated-charges', [ChargesController::class, 'getLastUpdatedCharge']);


    //Complain
    Route::get('/auth', 'AuthController@initiateBvnConsent');
    Route::get('getAllComplains', 'ComplainController@getAllComplains');
    Route::post('makeInquiry', 'ComplainController@makeInquiry');
    Route::get('/inquiry', 'ComplainController@getAllInquiry');



Route::middleware(['auth:api'])->group(function () {

        Route::get('getProfile', 'AuthController@getProfile');
        Route::get('logout', 'AuthController@logout');
        Route::put('updateProfile', 'AuthController@updateProfile');
        Route::post('image', 'AuthController@uploadImage');
        Route::put('updateUsertype', 'AuthController@updateUsertype');
    	Route::get('/getBvnConsent', 'AuthController@getBvnConsent');
	Route::put('get-verifiedd', 'AuthController@getBVNDetails');
	Route::get('get-complains-per-user', 'ComplainController@getComplainsPerUser');
	Route::post('makeComplain', 'ComplainController@makeComplain');

    Route::get('getProfile', 'AuthController@getProfile');
    Route::get('logout', 'AuthController@logout');
    Route::put('updateProfile', 'AuthController@updateProfile');
    Route::post('image', 'AuthController@uploadImage');
    Route::put('updateUsertype', 'AuthController@updateUsertype');
    Route::get('/getBvnConsent', 'AuthController@getBvnConsent');
    Route::put('get-verifiedd', 'AuthController@getBVNDetails');
    Route::get('get-complains-per-user', 'ComplainController@getComplainsPerUser');
    Route::post('makeComplain', 'ComplainController@makeComplain');

	      // Comment
    Route::post('create/comment/{feedbackId}', 'ComplainController@storeComment');
    Route::get('/feedback-by-id/{feedbackId}', 'ComplainController@getFeedbackById');
    Route::get('show/comment/{feedbackId}/{commentId}', 'ComplainController@showComment');
    Route::put('update/comment/{feedbackId}/{commentId}', 'ComplainController@updateComment');
    Route::get('/delete/comment/{feedbackId}/{commentId}', 'ComplainController@destroyComment');

         // Reply
    Route::post('create/reply/{commentId}', 'ComplainController@storeReply');
    Route::put('update/reply/{replyId}/{commentId}', 'ComplainController@updateReply');
    Route::delete('/delete/comment/{replyId}/{commentId}', 'ComplainController@destroyReply');

    Route::get('get-referred-count', [ReferralController::class, 'countReferralPerUser']);

    });

Route::middleware(['auth:api', 'admin'])->group(function () {
        //Admin
    Route::post('/admin/register', 'AdminController@adminRegister');
    Route::get('allExpenses', 'AdminController@getAllExpenses');
    Route::get('allKontributes', 'AdminController@getAllKontribute');
    Route::get('getAllBusiness', 'AdminController@getAllBusiness');
    Route::get('getAllExpensesByUserEmail/{email}', 'AdminController@getAllExpensesByUserEmail');
    Route::get('getAllKontributeByUserEmail/{email}', 'AdminController@getAllKontributeByUserEmail');
    Route::get('getAllBusinessByUserEmail/{email}', 'AdminController@getAllBusinessByUserEmail');
    Route::get('getAllAjoByUserEmail/{email}', 'AdminController@getAllAjoByUserEmail');
    Route::get('countAllAjo', 'AdminController@countAllAjo');
    Route::get('countAllBusiness', 'AdminController@countAllBusiness');
    Route::get('countAllExpenses', 'AdminController@countAllExpenses');
    Route::post('getAllExpenseWithDate', 'ReportingController@getUserExpenseWithDate');
    Route::post('getAllGroupWithDate', 'ReportingController@getUserGroupWithDate');
    Route::post('getUserExpenseWithCategory/{categoryId}', 'ReportingController@getUserExpenseWithCategory');
    Route::post('getUserExpenseWithSubCategory/{sub_categoryId}', 'ReportingController@getUserExpenseWithSubCategory');
    Route::get('get-all-AddedUser-Expenses/{refundmeId}', 'AdminController@getUserAddedToExpense}');
    Route::get('get-all-active-expense/{refundmeId}', 'AdminController@getActiveExpenses');
    Route::get('get-all-AddedUser-Kontribute/{kontributeId}', 'AdminController@getUserAddedToKontribute}');
    Route::get('get-all-active-kontribute/{kontributeId}', 'AdminController@getActiveKontribute');
    Route::get('count-all-added-users-refundme', 'AdminController@countUserAddedToExpense');
    Route::get('count-all-active-users-refundme', 'AdminController@countActiveExpenses');
    Route::get('countAllKontributes', 'AdminController@countAllKontributes');
    Route::get('count-all-added-users-kontribute', 'AdminController@countUserAddedToKontribute');
    Route::get('count-all-active-users-kontributes', 'AdminController@countActiveKontribtes');
    Route::put('/admin/update-feedback/{complain_reference_code}', 'AdminController@updateIssue');

    });


    Route::post('/set-ref', [ReferralSettingController::class, 'createReferral']);
    Route::put('/update-ref/{referralId}', [ReferralSettingController::class, 'updateReferral']);
    Route::get('/get-ref-settings/perAdmin', [ReferralSettingController::class, 'getAllReferralSettings']);
    Route::get('/all-users', [AdminController::class, 'getAllUsers']);
    Route::get('/users/{email}', [AdminController::class, 'getUserById']);
    Route::post('/create-charges', [ChargesController::class, 'createCharges']);
    Route::get('/charges', [ChargesController::class, 'readCharges']);
    Route::put('/charges/{id}', [ChargesController::class, 'updateCharge']);
    Route::delete('/charges/{id}', [ChargesController::class, 'deleteCharge']);
    Route::put('/admin/update-complain/{complainId}', [AdminController::class,'markAsCompleted']);
    });

Route::middleware(['auth:api', 'user.status'])->group(function () {
    // User Update
    Route::post('category', 'ExpenseCategoryController@category');


     //Mobile Verification
    Route::post('verify-phone-number', 'MobileVerificationController@verifyPhone');
    Route::post('check-mobile-number', 'MobileVerificationController@checkOtp');
    Route::post('send-otp', 'MobileVerificationController@EmailVerification');
    Route::put('confirm-email', 'MobileVerificationController@ConfirmEmailViaOtp');
    Route::get('send-otp-mobile/{username}', 'ExpenseController@sendSmsMessage');




     //Business
    Route::post('createBusiness', 'BusinessController@createBusiness');
    Route::get('list-all-business-users', 'AuthController@listAllBusinessUsers');
    Route::put('update-business/{id}', 'BusinessController@updateBusiness');
    Route::post('create-business', 'BusinessController@createBusiness');
    Route::get('get-business-under-a-owner/{owner_id}', 'BusinessController@getAllBusiness');
    Route::get('get-a-single-business-under-owner/{business_code}', 'BusinessController@getABusiness');
    Route::delete('delete-a-business/{id}', 'BusinessController@deleteABusiness');
    Route::get('gac-under-a-specific-business/{customer_code}', 'BusinessController@getAllCustomersUnderABusiness');
    Route::post('/mpos-payment/{business_code}', [BusinessTransactionController::class, 'mposPay']);
    Route::post('/mpos-payment-option', [BusinessTransactionController::class, 'mposOneTimePay']);

    Route::get('/get-mpos-payment-history/{business_code}', [BusinessTransactionController::class, 'getMposPerBusiness']);
    Route::get('/get-mpos-payment-byReference/{paymentReference}', [BusinessTransactionController::class, 'getMposPerPaymentReference']);




    //B2B Transactions
    Route::post('create-product', 'BusinessTransactionController@creatProduct');
    Route::get('all-product', 'BusinessTransactionController@getAllProductsPerBusinessMerchant');
    Route::get('product-per-business/{businessCode}', 'BusinessTransactionController@getProductsPerBusiness');
//    Route::post('initiate-business-transaction/{product_id}/{business_code}', 'BusinessTransactionController@startBusinessTransaction');
    Route::post('initiate-business-transaction/{business_code}', 'BusinessTransactionController@startBusinessTransaction');
    Route::post('create-option', 'MotoController@moto');
    Route::get('get-option', 'MotoController@getMotoMethod');
    Route::post('create-vat', 'VatController@createVat');
    Route::get('all-invoices-created-by-business-owner', 'BusinessTransactionController@getAllInvoiceByABusinessOwner');
    Route::get('count-all-invoices-created-by-business-owner', 'BusinessTransactionController@countAllInvoiceByABusinessOwner');
    Route::get('get-all-invoices-received-by-customer', 'BusinessTransactionController@getAllInvoiceRecievedByACutomer');
    Route::get('count-all-invoices-recieved-by-business', 'BusinessTransactionController@countAllInvoiceRecievedByACutomer');
    Route::post('business-settlements', 'BusinessTransactionController@AzatBusinessCollection');
    Route::get('customer-invoice/{customerEmail}', 'BusinessTransactionController@getAllInvoiceSentToAParticularCustomer');
    Route::get('get-all-transactions-created-by-a-specific-business/{business_code}', 'BusinessTransactionController@getAllTransactionsByASpecificBusiness');
    Route::get('get-all-customers-under-a-specific-business/{business_code}', 'BusinessTransactionController@getAllCustomersUnderASpecificBusiness');
    Route::get('get-withdrawal-response', 'BusinessTransactionController@getBusinessWithdrawalTransaction');
    Route::get('get-invoice/{businessCode}', 'BusinessTransactionController@getAllInvoiceByBusiness');
    Route::get('get-link/{businessCode}', 'BusinessTransactionController@getAllIinkByBusiness');
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
    Route::post('create-customer/{business_code}', 'CustomerController@createCustomer');
    Route::get('getUserExpense', 'ExpenseController@getUserExpense');
    Route::get('getUserDeletedExpenseInvite', 'ExpenseController@getUserDeletedExpenseInvite');
    Route::get('getAllMemebersOfAnExpense/{expenseId}', 'ExpenseController@getAllMemebersOfAnExpense');
    Route::get('getAmountsPaidPerExpense/{expenseId}', 'ExpenseController@getUserAmountsPaidPerExpense');
    Route::get('getTotalNumberOfPaidUsersPerExpense/{expenseId}', 'ExpenseController@getTotalNumberOfPaidUsersPerExpense');
    Route::post('/export-excel', 'ExpenseController@exportExpenseToExcel');
    Route::post('/export-csv', 'ExpenseController@exportExpenseToCsv');
    Route::post('collection', 'ExpenseController@AzatIndividualCollection');
    Route::get('get-transaction-status', 'ExpenseController@getStatus');
    Route::post('verify-account', 'ExpenseController@accountVerification');
    Route::get('getResponse', 'ExpenseController@getStatus');
    Route::put('update-payback-transaction/{transactionId}', 'ExpenseController@UpdateTransactionRequest');
    Route::get('get-invited-users', 'ExpenseController@getInvitedUsers');
    Route::get('recreate-underpaid-transactions/{expenseId}/{id}', 'ExpenseController@reinitiateTransaction');
    Route::get('get-unpaid-balance/{expenseId}', 'ExpenseController@checkResidual');
    Route::get('get-refund-withdrawal-response', 'ExpenseController@getExpenseWithdrawalTransaction');
    Route::get('get-draft-refundme', 'ExpenseController@getAllRefundMeCreatedt');
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
    Route::post('group-settlement', 'GroupController@AzatGroupCollection');
    Route::put('update-kontribute-transaction/{transactionId}', 'GroupController@UpdateTransactionGroupRequest');
    Route::get('re-initiate-transaction/{groupId}/{id}', 'GroupController@reinitiateTransactionToGroup');
    Route::get('get-kontribute-withdrawal-response', 'GroupController@getWithdrawalTransaction');
    Route::get('get-openLink-transactions', 'GroupController@getOpenKontributions');
    Route::get('get-openLink-transactions-by-id/{id}', 'GroupController@getOpenKontributionsById');
    Route::get('get-draft-kontribute', 'GroupController@getAllKontributeCreatedt');
    Route::get('get-donor/{transactionReference}', 'GroupController@getFundDonor');
    Route::get('get-funds', 'GroupController@getFunds');
   Route::get('get-fundss', 'GroupController@getFundss');
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
    Route::post('getUserExpenseWithDate', 'ReportingController@getUserExpenseWithDate');
    Route::post('getUserGroupWithDate', 'ReportingController@getUserGroupWithDate');
    Route::post('getUserExpenseWithCategory/{categoryId}', 'ReportingController@getUserExpenseWithCategory');
    Route::post('getUserExpenseWithSubCategory/{sub_categoryId}', 'ReportingController@getUserExpenseWithSubCategory');
    Route::get('getExpenseReport', 'ReportingController@getExpenseReport');
    Route::get('getKontributeReport', 'ReportingController@getKontributeReport');
    Route::get('getBusinessReport/{businessCode}', 'ReportingController@getBusinessReport');

    //Splitting Methods
    Route::post('splitingMethod', 'PaymentSplittingController@splitingMethod');
    Route::get('getSplittingMethods', 'PaymentSplittingController@getSplittingMethods');

   //Invited users
   Route::post('add-users', 'ExpenseController@add');
//   Route::get('update-balance-residual', 'BalanceUpdateController@updateBalanceResidual');

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
    Route::get('get-all-merchants', 'NQRController@getAllMerchant');

    //NQR  Store Merchant Services
    Route::post('store-generate-dyanmic-qrcode', 'NqrStoreController@storeGenerateDyanmicQrCode');
    Route::post('get-store-trans-report', 'NqrStoreController@getStoreTransactionReport');
    Route::post('get-store-trans-status', 'NqrStoreController@storeTransactionStatus');
    Route::get('get-store', 'NqrStoreController@getStore');
    Route::post('create-store', 'NqrStoreController@createStore');
    Route::get('get-specific-submerchant-list-Instore/{id}', 'NqrStoreController@getListSpecificSubMerchantInStore');

   // Ajo
    Route::post('/create-ajo', 'AjoController@createAjo');
    Route::post('invitation/{ajoId}', 'AjoController@inviteUserToAjo');
    Route::post('invitations/{ajoId}', 'AjoController@inviteUserToAjoh');
    Route::get('get-ajo-per-user', 'AjoController@getAllAjoCreatedPerUser');
    Route::get('get-invitation', 'AjoController@getAllAjoInvitationCreatedPerUser');
    Route::get('get-ajo-by-id/{id}', 'AjoController@getAjoById');
    Route::get('get-unpaid-users/{id}', 'AjoController@getUnpaidAjoUsers');
    Route::post('ajo-payout', 'AjoController@AjoPayout');
    Route::get('/ajo/collector/{ajoId}/{invitationId?}/{currentPosition?}', 'AjoController@getAjoCollector');
  //  Route::get('get-ajo-by-id/{ajoId}', 'AjoController@getAjoByIdd');
    Route::get('get-ajo-contributors/{ajo_id}', 'AjoController@getAjoContributors');
    Route::get('get-ajo-contributor/{transactionReference}/{email}', 'AjoController@getTransactionData');
    Route::get('get-ajo-withdrawal', 'AjoController@getAjoWithdrawalTransaction');
    Route::post('test-auto', 'AjoController@sendPaymentLinkToUsers');


    // Referrals
    Route::get('/generate-link', [ReferralController::class, 'generateReferralUrl']);
    Route::get('/get-refPoint-per-user', [ReferralController::class, 'getAllReferral']);

    // Direct Debit
   // Route::post('/create-mandate-product', 'DirectDebitController@addProduct');

    Route::post('/create-mandate-product', [DirectDebitController::class, 'addProduct']);
    Route::post('/create-dd-mandate/{ajoId}', [DirectDebitController::class, 'createMandate']);
    Route::get('/get-dd-bankList', [DirectDebitController::class, 'getDDBankList']);
    Route::get('/get-product-list', [DirectDebitController::class, 'productList']);
    Route::post('/update-mandate', [DirectDebitController::class, 'updateMandate']);


});




