<?php
namespace frontend\modules\api\controllers;
use Yii;
use common\models\system\LoginForm;
use common\models\system\Profile;
use common\models\system\User;
use common\models\lab\Customer;
use common\models\lab\Customeraccount;
use common\models\lab\LogincForm;
use common\models\lab\Request;
use common\models\finance\Customerwallet;
use common\models\finance\Customertransaction;
use common\models\lab\Booking;
use common\components\Functions;
use common\models\system\Rstl;
use common\models\lab\Sample;
use common\models\lab\Sampletype;
use common\models\lab\Purpose;
use common\models\lab\Modeofrelease;
use common\models\lab\Testnamemethod;
use common\models\lab\Testname;
use common\models\lab\Lab;
use common\models\lab\Quotation;
use yii\helpers\Json;
use common\models\finance\Epayment;


class RestcustomerController extends \yii\rest\Controller
{
	public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \sizeg\jwt\JwtHttpBearerAuth::class,
            'except' => ['login','server','codevalid','mailcode','register','epayment'], //all the other
            'user'=> \Yii::$app->customeraccount
        ];

        $behaviors['corsFilter'] = [
            'class' => \common\filters\Cors::className(),
            'cors'  => [
                // restrict access to domains:
                'Origin'                           => ['*'],
                'Access-Control-Request-Method'    => ['POST', 'GET', 'OPTIONS'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age'           => 3600,                 // Cache (seconds)
                'Access-Control-Allow-Headers' => ['authorization','X-Requested-With','content-type', 'some_custom_header']
                // 'Access-Control-Allow-Headers' => ['Origin','X-Requested-With','content-type', 'Access-Control-Request-Headers','Access-Control-Request-Method','Accept','Access-Control-Allow-Headers']
            ],
        ];

        return $behaviors;
    }

     protected function verbs(){
        return [
            'login' => ['POST'],
            // 'user' => ['GET'],
            // 'samplecode' => ['GET'],
           // 'server' => ['GET'],
             'mailcode' => ['GET'],
             'confirmaccount' => ['POST'],
        ];
    }

     public function actionIndex(){
        return "Index";
     }

     /**
     * @return \yii\web\Response
     */
    public function actionLogin()
    {
        $my_var = \Yii::$app->request->post();

        $email = $my_var['email'];
        $password = $my_var['password'];

        $user = Customer::find()->where(['email'=>$email])->one();

        if($user){
            $model = new LogincForm();
            $my_var = \Yii::$app->request->post();
            $model->customer_id = $user->customer_id;
            $model->password = $password;

            if ($model->login()) {
                    $signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
                    /** @var Jwt $jwt */
                    $jwt = \Yii::$app->jwt;
                    $token = $jwt->getBuilder()
                        ->setIssuer('http://example.com')// Configures the issuer (iss claim)
                        ->setAudience('http://example.org')// Configures the audience (aud claim)
                        ->setId('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
                        ->setIssuedAt(time())// Configures the time that the token was issue (iat claim)
                        ->setExpiration(time() + 3600 * 24)// Configures the expiration time of the token (exp claim)
                        ->set('uid', \Yii::$app->customeraccount->identity->customer_id)// Configures a new claim, called "uid"claim,
                        //->set('username', \Yii::$app->user->identity->username)// Configures a new claim, called "uid"
                        ->sign($signer, $jwt->key)// creates a signature using [[Jwt::$key]]
                        ->getToken(); // Retrieves the generated token

                    $customer = Customer::findOne(\Yii::$app->customeraccount->identity->customer_id);

                    return $this->asJson([
                        'token' => (string)$token,
                        'email'=>$customer->email,
                        'fullname' => $customer->customer_name,
                        'type' => "customer",
                        'address' => $customer->address,
                        'tel' => $customer->tel,
                        'customerid' => $customer->customer_id,
                        'rstld' => $customer->rstl_id,
                        'nature' => $customer->businessNature?$customer->businessNature->nature:"none",
                        'typeindustry' => $customer->industrytype?$customer->industrytype->industry:"none",
                        'typecustomer' => $customer->customerType?$customer->customerType->type:"none",
                    ]);  
            } else {
                //check if the user account is not activated
                $chkaccount = Customeraccount::find()->where(['customer_id'=>$user->customer_id])->one();
                if($chkaccount){
                    if($chkaccount->status==0){
                        return $this->asJson([
                        'success' => false,
                        'activated'=>false,
                        'message' => 'Account not activated',
                    ]);
                    }
                }

                return $this->asJson([
                        'success' => false,
                        'message' => 'Email and Password didn\'t match',
                    ]);
            }
        }else{  
            return $this->asJson([
                    'success' => false,
                    'message' => 'Email is not a valid customer',
                ]);
        }
        
    }


    public function actionUser(){
        $signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
        /** @var Jwt $jwt */
        $jwt = \Yii::$app->jwt;
        $token = $jwt->getBuilder()
            ->setIssuer('http://example.com')// Configures the issuer (iss claim)
            ->setAudience('http://example.org')// Configures the audience (aud claim)
            ->setId('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
            ->setIssuedAt(time())// Configures the time that the token was issue (iat claim)
            ->setExpiration(time() + 3600 * 24)// Configures the expiration time of the token (exp claim)
            ->set('uid', \Yii::$app->customeraccount->identity->customer_id)// Configures a new claim, called "uid"claim,
            //->set('username', \Yii::$app->user->identity->username)// Configures a new claim, called "uid"
            ->sign($signer, $jwt->key)// creates a signature using [[Jwt::$key]]
            ->getToken(); // Retrieves the generated token

        $customer = Customer::findOne(\Yii::$app->customeraccount->identity->customer_id);

        return $this->asJson([
            'token' => (string)$token,
            'user'=> (['email'=>$customer->email,
                        'fullname' => $customer->customer_name,
                        'type' => "customer",]),
                        'customer_id'=>\Yii::$app->customeraccount->identity->customer_id
        ]);  
    }

     /**
     * @return \yii\web\Response
     */
    public function actionData()
    {
        return $this->getuserid();
    }

    function getuserid(){
        $myvar = \Yii::$app->request->headers->get('Authorization');

        $rawToken = explode("Bearer ", $myvar);
        $rawToken = $rawToken[1];
        $token = \Yii::$app->jwt->getParser()->parse((string) $rawToken);
        return $token->getClaim('uid');
    }

     //************************************************
     public function actionServer(){

        $server = $_SERVER['SERVER_NAME'];
        if(!$sock = @fsockopen($server, 80))
            {
                $data = array("status" => "offline");
            }
            else{
                $data = array("status" => "online");
            }
  
        return $this->asJson($data);   
    }

    public function actionGetcustonreq(){
        $model = Request::find()->select(['request_id','request_ref_num','request_datetime', 'status_id'])->where(['customer_id'=>$this->getuserid()])->orderBy('request_id DESC')->all();

        if($model){
            return $this->asJson(
                $model
            ); 
        }else{
            return $this->asJson([
                'success' => false,
                'message' => 'No Request Found',
            ]); 
        }
    }

    public function actionGetcustcomreq(){
        $model = Request::find()->select(['request_id','request_ref_num','request_datetime'])->where(['customer_id'=>$this->getuserid(), 'status_id'=>2])->orderBy('request_id DESC')->all();

        if($model){
            return $this->asJson(
                $model
            ); 
        }else{
            return $this->asJson([
                'success' => false,
                'message' => 'No Request Found',
            ]); 
        }
    }

    public function actionGetcustomerwallet(){
        $transactions = Customerwallet::find()->where(['customer_id'=>$this->getuserid()])->one();
        if($transactions){
            return $this->asJson(
                $transactions
            ); 
        }else{
            return $this->asJson([
                'success' => false,
                'message' => '0.00',
            ]); 
        }
    }

     public function actionGetwallettransaction($id){
        $transactions = Customertransaction::find()->where(['customerwallet_id'=>$id])->orderby('date DESC')->all();
        return $this->asJson(
                $transactions
            ); 
    }
    //************************************************

    public function actionSetbooking(){ //create booking for customers
        $my_var = \Yii::$app->request->post();


       if(!$my_var){
            return $this->asJson([
                'success' => false,
                'message' => 'POST empty',
            ]); 
       }
        //attributes Purpose, Sample Quantity, Sample type, Sample Name and Description, schedule date and datecreated
        $bookling = new Booking;
        //$bookling->scheduled_date = $my_var['Schedule Date'];
        //$bookling->booking_reference = '34ertgdsg'; //reference how to generate? is it before save? or 
        $bookling->rstl_id = $my_var['Lab'];
        $bookling->date_created = $my_var['Datecreated'];
        $bookling->qty_sample = $my_var['SampleQuantity'];
        $bookling->scheduled_date = $my_var['Scheduleddate'];
        $bookling->description=$my_var['Description'];
        $bookling->samplename=$my_var['SampleName'];
        $bookling->sampletype_id=$my_var['Sampletype'];
        $bookling->customer_id = $this->getuserid();
        $bookling->booking_status = 0;
        $bookling->purpose = $my_var['Purpose'];
        $bookling->customerstat = 1;

        if($bookling->save(false)){
            return $this->asJson([
                'success' => true,
                'message' => 'You have booked successfully',
            ]); 
        }
        else{
            return $this->asJson([
                'success' => false,
                'message' => 'Booking Failed',
            ]); 
        }
    }
    public function actionListsampletypes(){
        $model = Sampletype::find()->select(['sampletype_id','type','status_id'])->where(['status_id'=>1])->orderBy('type ASC')->all();

        if($model){
            return $this->asJson(
                $model
            ); 
        }else{
            return $this->asJson([
                'success' => false,
                'message' => 'No data Found',
            ]); 
        }
    }

    public function actionListpurpose(){
        $model = Purpose::find()->select(['purpose_id','name','active'])->where(['active'=>1])->orderBy('name ASC')->all();

        if($model){
            return $this->asJson(
                $model
            ); 
        }else{
            return $this->asJson([
                'success' => false,
                'message' => 'No data Found',
            ]); 
        }
    }
    /*public function actionListmode(){
        $model = Modeofrelease::find()->select(['modeofrelease_id','mode','status'])->where(['status'=>1])->orderBy('mode ASC')->all();

        if($model){
            return $this->asJson(
                $model
            ); 
        }else{
            return $this->asJson([
                'success' => false,
                'message' => 'No data Found',
            ]); 
        }
    }*/

    public function actionGetbookings(){
        $my_var = Booking::find()->where(['customer_id'=>$this->getuserid()])->orderby('scheduled_date DESC')->all();
        return $this->asJson(
            $my_var
        );    
    }

    public function actionGetbookingdetails(){
        $purposeqry = Purpose::find()->select(['purpose_id','name','active'])->where(['active'=>1])->orderBy('name ASC')->all();
        $modeofreleaseqry = Modeofrelease::find()->select(['modeofrelease_id','mode','status'])->where(['status'=>1])->orderBy('mode ASC')->all();

         $my_var = Booking::find()
         ->select(['booking_id','scheduled_date','booking_reference', 'description', 'rstl_id', 'date_created', 'qty_sample', 'customer_id', 'booking_status', 'samplename', 'reason','modeofrelease_ids'=> 'tbl_modeofrelease.mode', 'purpose'=>'tbl_purpose.name', 'sampletype_id' =>'tbl_sampletype.type'])
         ->where(['customer_id'=>$this->getuserid()])
         ->joinWith(['modeofrelease'])
         ->joinWith(['purpose'])
         ->joinWith(['sampletype'])
         ->orderby('scheduled_date DESC')
         ->all();

         return $this->asJson(
            $my_var
        );  
    }

    public function actionMailcode($email){

        //sends a code to a customer for account verification purpose

        //generate random strings
        $code = \Yii::$app->security->generateRandomString(5);
        //get the customer profile using the email
        $customer = Customer::find()->where(['email'=>$email])->one();
        $accountcustom = Customeraccount::find()->where(['customer_id'=>$customer->customer_id, 'status'=>0])->one();
        if ($accountcustom){
            if($customer){
            //check if the customer has an account already
                $account = Customeraccount::find()->where(['customer_id'=>$customer->customer_id])->one();
                if($account){
                    //update the verify code
                    $account->verifycode = $code;
                    $account->status=0;
                    $account->save();
                } else{
                    //create account with the verify code
                    $new = new Customeraccount;
                    $new->customer_id=$customer->customer_id;
                    $new->setPassword('12345');
                    $new->generateAuthKey();
                    $new->verifycode = $code;
                    $new->save();
                }
            //contruct the html content to be mailed to the customer
            $content ="
            <h1>Good day! $customer->customer_name</h1>

            <h2>Account code : $code</h2>
            <p>Thank you for choosing the Onelab, to be able to provide a quality service to our beloved customer, we are giving this account code above which you may use to activate your account if ever you want to use the mobile app version, below are the following features that you may found useful. Available for Android smart devices.</p>
            
            <p><h3>Download the app by visiting the link below.<h3><br>
            https://play.google.com/store/apps/details?id=com.dostonelab.customer
            </p>

            <ul><b>Here are the following steps to register using the Mobile App:</b>
                <li>Copy the Account code above</li>
                <li>Downlad and open the OneLab Customer Mobile</li>
                <li>Click Register</li>
                <li>Paste the Account code in the Verification code text box</li>
                <li>Set your password. It mas be atleast 6 characters</li>
                <li>Agree on the Terms and condition.</li>
                <li>Enjoy using the OneLab Customer Mobile</li>
            </ul>

            <ul><b>Features</b>
                <li>Request and Result Tracking</li>
                <li>Request Transaction History</li>
                <li>Wallet Transations and History</li>
                <li>Bookings</li>
                <li>Sample Quotation</li>
                <li>User Profile</li>
            </ul>
            <br>
            <p>Truly yours,</p>
            <h4>Onelab Team</h4>
            ";

            //email the customer now
            //send the code to the customer's email
            \Yii::$app->mailer->compose()
            ->setFrom('eulims.onelab@gmail.com')
            ->setTo($email)
            ->setSubject('Eulims Mobile App')
            ->setTextBody('Plain text content')
            ->setHtmlBody($content)
            ->send();

            return $this->asJson([
                'success' => true,
                'message' => 'Code successfully sent to customer\'s email',
            ]); 
            }
            else{
                return $this->asJson([
                    'success' => false,
                    'message' => 'Email is not a valid customer',
                ]); 
                }
        }
        else{
            return $this->asJson([
                'success' => false,
                'message' => 'Email is in active status',
            ]); 
            } 
    }

    public function actionRegister(){
        //set null for coulmn verifycode after register, 
        //to eliminate the process of inputing of email add.
        $my_var = \Yii::$app->request->post();

        $code = $my_var['code'];
        $password = $my_var['password'];

        $account = Customeraccount::find()->where(['verifycode'=>$code])->one();
        if($account){
            $account->status=1;
            $account->setPassword($password);
            $account->generateAuthKey();
            $account->verifycode=Null;
            if($account->save()){
                return $this->asJson([
                    'success' => true,
                    'message' => 'It is great to have you with us. Our warmest welcome from OneLab Team.'
                ]);
            }else{
                return $this->asJson([
                    'success' => false,
                    'message' => 'Invalid activation'
                ]);
            }
        }else{
            return $this->asJson([
                'success' => false,
                'message' => 'Invalid code'
            ]);
        }
    }

    public function actionCodevalid(){
        //validate the code sent by the customer
        $my_var = \Yii::$app->request->post();

        $code = $my_var['code'];
        $account = Customeraccount::find()->where(['verifycode'=>$code])->one();
        if($account){
            return $this->asJson([
                'success'=> true,
                'message'=> 'Valid code'
            ]);
        }
        else{
            return $this->asJson([
                'success'=> false,
                'message'=> 'Sorry your code is invalid, Please try again.'
            ]);
        }
    }

    public function actionLogout(){
        \Yii::$app->customeraccount->logout();
        return "Logout";
    }

    public function actionGetrstl(){
        $model = Rstl::find()->all();
        if($model){
            return $this->asJson(
                $model
            ); 
        }
    }

    public function actionGetsamples($id){
        $model = Sample::find()->select(['sample_code','samplename','completed'])->where(['request_id'=>$id])->all();
        if($model){
            return $this->asJson(
                $model
            ); 
        }
    }
    /*public function actionGetquotation($keyword){
        $model = Testnamemethod::find()
        ->select(['testname_method_id','testname_id'=> 'tbl_testname.testName', 'method_id'=> 'tbl_methodreference.fee', 'workflow'=> 'tbl_methodreference.method', 'lab_id'=> 'tbl_lab.labname'])
        ->joinWith(['testname'])
        ->joinWith(['method'])
        ->joinWith(['lab'])
        ->orderby(['lab_id'=> SORT_ASC,'testname_id'=> SORT_ASC])
        ->all();
        if($model){
            return $this->asJson(
                $model
            ); 
        }
    }*/

    public function actionGettestnames($keyword){
        $model= Testname::find()
        ->select([
            'testname_id',
            'testName'
        ])->all();

        if($model){
            return $this->asJson(
                $model
            ); 
        }
    }

    public function actionGettestnamemethod($testname_id){
        $testnamemethods = Testnamemethod::find()
        ->with('method')
        ->where([
            'tbl_testname_method.testname_id'=>$testname_id,
        ])
        ->all();
        $methodfee = [];
        foreach ($testnamemethods as $model) {
            $methodfee [] = [
                'tm_id' => $model->testname_method_id ,
                'method' => $model->method->method,
                'reference' => $model->method->reference,
                'fee' => $model->method->fee,
            ];
        }
        return $this->asJson(
            $methodfee
        ); 
    
    }

    public function actionGetcustomerquotation(){
        $query = Yii::$app->referraldb->createCommand("SELECT b.testname_id, b.test_name, d.labname, a.lab_id
                                                  FROM tbl_testname_method AS a
                                                  INNER JOIN tbl_testname AS b ON a.testname_id = b.testname_id
                                                  INNER JOIN tbl_lab AS d ON a.lab_id = d.lab_id
                                                  GROUP BY b.testname_id, b.test_name, d.labname, a.lab_id
                                                  ORDER BY a.lab_id, b.test_name") ->queryAll();
        $arrayTestname =array();
        foreach ($query as $eachRow)
        {
            $recData=array();

            $recData['testname_id'] = $eachRow['testname_id'];
            $recData['test_name'] = $eachRow['test_name'];
            $recData['labname'] = $eachRow['labname'];
            $recData['lab_id'] = $eachRow['lab_id'];
            array_push($arrayTestname,$recData);
        };

         return $this->asJson($arrayTestname);
    }

        //Save customer Quotation
        public function actionSetquotation(){
           $my_var = \Yii::$app->request->post();


       if(!$my_var){
            return $this->asJson([
                'success' => false,
                'message' => 'POST empty',
            ]); 
       }

        $quot = new Quotation;
        
        $quot->customer_id = $my_var['customer_id'];
        $quot->content = $my_var['content'];
        $quot->status_id = $my_var['status_id'];
        $quot->qty = $my_var['qty'];
        $quot->sampletype = $my_var['sampletype'];
        $quot->sampledescription = $my_var['sampledescription'];
        $quot->sendcopy = $my_var['sendcopy'];
        $quot->remarks = $my_var['remarks'];
        $quot->rstl_id = $my_var['rstl_id'];
        $quot->attachment = $my_var['attachment'];

        if($quot->save()){
            return $this->asJson([
                'success' => true,
                'message' => 'You have Request successfully',
            ]); 
        }
        else{
            return $this->asJson([
                'success' => false,
                'message' => 'Request Failed',
                'data' => null,
            ]); 
        }
    }

    public function actionLaboratorylist(){
        $model = Lab::find()->orderby(['lab_id'=>SORT_ASC])->all();
        return $this->asJson($model);
    }

    public function actionGetquotationsave($id){
        $model = Quotation::find()->select(['quotation_id', 'customer_id', 'status_id', 'qty', 'sampletype', 'sampledescription', 'sendcopy', 'remarks', 'rstl_id', 'attachment', 'create_time'])
                                  ->where(['customer_id'=>$id])
                                  ->orderby(['create_time'=>SORT_DESC])
                                  ->all();
        return $this->asJson($model);
    }
    
    public function actionGetquotationlist($id, $qid){
        $model = Quotation::find()->where(['customer_id'=>$id, 'quotation_id'=>$qid])->orderby(['create_time'=>SORT_DESC])->one();


         // return $this->asJson([
         //    Json::decode($model->content)
         // ]);
        $james = Json::decode($model->content);
        $newarr = [];
        foreach ($james as $jame) {
            //query
            $testn = \common\models\referral\Testname::findOne($jame['test']);
            if($testn)
                $newarr[] = ['test'=> $testn->test_name, 'method'=>$jame['method']];
            else
                $newarr[] = ['test'=> $jame['test'], 'method'=>$jame['method']];
        }
        return $this->asJson([
            $newarr
         ]);
    }

    //this function will return list of sampletype using the the labid , btc
    //cann trigger this during lab dropdown on change event
   /* public function actionSampletypebylab($lab_id){
        $model = Testnamemethod::find()
            ->joinWith('sampletype')
            ->select(['tbl_testname_method.sampletype_id','tbl_sampletype.type'])
            ->where(['lab_id'=>$lab_id])
            ->groupby('sampletype_id')
            ->asArray()
            ->all();

        return $this->asJson($model);
    }

    public function actionTestnamebysampletypeandlab($lab_id,$sampletype_id){
        $model = Testnamemethod::find()
            ->joinWith('testname')
            ->select(['tbl_testname_method.testname_id','tbl_testname.testName'])
            ->where(['lab_id'=>$lab_id,'sampletype_id'=>$sampletype_id])
            ->groupby('testname_id')
            ->asArray()
            ->all();

        return $this->asJson($model);
    }

    public function actionMethodbylabsampletypeandtestname($lab_id,$sampletype_id,$testname_id){
         $model = Testnamemethod::find()
            ->joinWith('method')
            ->select(['testname_method_id','method_id','methodnamefee'=>'CONCAT(tbl_methodreference.method,tbl_methodreference.fee)'])
            ->where(['lab_id'=>$lab_id,'sampletype_id'=>$sampletype_id,'tbl_testname_method.testname_id'=>$testname_id])
            ->groupby('testname_method_id')
            ->asArray()
            ->all();

        return $this->asJson($model);
    }*/
    //for the OP sent from epayment
    public function actionEpayment(){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        $my_var = \Yii::$app->request->post();
        if(!$my_var)
            return '010';

        if(!isset($my_var['merchant_reference_number']))
            return '011'; //no MRN parameter
        if(!str_replace(" ","",$my_var['merchant_reference_number']))
            return '011'; //no MRN parameter


        if(!isset($my_var['epp']))
            return '012'; //no epp parameter
        if(!str_replace(" ","",$my_var['epp']))
            return '012'; //no MRN parameter


        // if(!isset($my_var['status_code']))
        //     return '013'; //no epp parameter
        // if(!str_replace(" ","",$my_var['status_code']))
        //     return '013'; //no MRN parameter


        $merchant_reference_number = $my_var['merchant_reference_number'];
        $epp = $my_var['epp'];
        // $status_code = $my_var['status_code'];
        //find the mrn
        $epayment = Epayment::find()->where(['mrn'=>$merchant_reference_number])->one();
        if(!$epayment)
            return '020'; //no MRN found in the database

        if($epayment->epp)
            return '501';

        $epayment->epp = $epp;
        $epayment->status_code = $status_code;

        if($epayment->save())
            return '100';
        else
            return '500';
    }
}
