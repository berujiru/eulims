<?php

namespace frontend\modules\api\controllers;

use Yii;
use yii\web\Response;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\db\ActiveQuery;
use common\models\referral\Pstc;
use common\models\referral\Pstcrequest;
use common\models\referral\Pstcsample;
use common\models\referral\Lab;
use common\models\referral\Sample;
use common\models\referral\Testname;
use common\models\referral\Testnamemethod;
use common\models\referral\Sampletype;
use common\models\referral\Pstcanalysis;
use common\models\referral\Methodreference;
use common\models\referral\Customer;
use yii\helpers\ArrayHelper;

class RestpstcController extends \yii\rest\Controller
{

    public function behaviors()
	{
		$behaviors = parent::behaviors();
		
		$behaviors['authenticator'] = [
			'class' => \sizeg\jwt\JwtHttpBearerAuth::class,
		];
		// remove authentication filter
		$auth = $behaviors['authenticator'];
		unset($behaviors['authenticator']);
		
		// add CORS filter

		$behaviors['corsFilter'] = [
			'class' => \common\filters\Cors::className(),
			'cors'  => [
				// restrict access to domains:
				'Origin'                           => ['*'],
				'Access-Control-Request-Method'    => ['POST', 'GET', 'OPTIONS'],
				'Access-Control-Allow-Credentials' => true,
				'Access-Control-Max-Age'           => 3600,                 // Cache (seconds)
				'Access-Control-Allow-Headers' => ['authorization','X-Requested-With','content-type', 'some_custom_header','Access-Control-Allow-Origin']
				
				// 'Access-Control-Allow-Headers' => ['Origin','X-Requested-With','content-type', 'Access-Control-Request-Headers','Access-Control-Request-Method','Accept','Access-Control-Allow-Headers']
			],
		];
		
		// re-add authentication filter
		$behaviors['authenticator'] = $auth;
		// avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
		$behaviors['authenticator']['except'] = ['request','requestview','listlab','pstclist', 'customerlist'];

		return $behaviors;
	}

    // ------------------------------------
    // Function for Requests. (Create, Read, Update, Delete)
    // ------------------------------------

    public function actionRequest() //Get pstcrequest with customer relationship
    {
        $request = Yii::$app->request;
        $rstl_id = (int) $request->get('rstl_id');
        $dataSet = [];
        if($rstl_id) {
            
            $datas = Pstcrequest::find()->where(['tbl_pstcrequest.rstl_id'=>$rstl_id])
                ->orderBy('created_at DESC')
                ->asArray()
                ->all();

                foreach($datas as $data){

                    $customer = Customer::find()->where(['local_customer_id' =>  $data['customer_id']])->andWhere(['rstl_id' => $rstl_id])->one();

                    $dataSet[] = [
                        'pstc_request_id'  => $data['pstc_request_id'],
                        'rstl_id'  => $data['pstc_request_id'],
                        'pstc_id'  => $data['pstc_request_id'],
                        'request_ref_num'  => $data['pstc_request_id'],
                        'submitted_by'  => $data['submitted_by'],
                        'received_by'  => $data['received_by'],
                        'user_id'  => $data['user_id'],
                        'status_id'  => $data['status_id'],
                        'accepted'  => $data['accepted'],
                        'duedate' => $data['duedate'],
                        'created_at' => $data['created_at'],
                        'updated_at' => $data['updated_at'],
                        'customer' => $customer['customer_name']
                    ];
                }

            return $dataSet;
            
        }else{
            throw new \yii\web\HttpException(400, 'Please specify RSTL ID. :)');
        }
    }

    public function actionPstcaccepted()
    {
        $pstcId = (int) Yii::$app->request->get('id');
        $data = Pstcrequest::findOne($pstcId);
        $data->accepted = 1;
        $data->save(false);

        return $data;
    
    }

    public function actionUpdateref() //Create or Update pstc request
    {
        $id = (int) Yii::$app->request->post('id');
        $reference = Yii::$app->request->post('reference');
        $due = Yii::$app->request->post('due');

        $data = Pstcrequest::find()->where(['pstc_request_id' => $id])->one() ;
        $data->request_ref_num = $reference;
        $data->duedate= $due;
        $data->save(false);

        return $data;
    }

    public function actionRequestcreate() //Create or Update pstc request
    {
        $request = Yii::$app->request;
        $data = ($request->isPut) ? Pstcrequest::find()->where(['id' => $id])->one() : new Pstcrequest;
        $data->rstl_id = (int) Yii::$app->request->post('rstl_id');
        $data->pstc_id = (int) Yii::$app->request->post('pstc_id');
        $data->customer_id = (int) Yii::$app->request->post('customer_id');
        $data->submitted_by=  ucwords(strtolower(Yii::$app->request->post('submitted')));
        $data->received_by = ucwords(strtolower(Yii::$app->request->post('received')));
        $data->user_id = (int) Yii::$app->request->post('user_id');
        $data->status_id = 1;
        $data->created_at = date('Y-m-d H:i:s');
        $data->updated_at = date('Y-m-d H:i:s');
        $data->save(false);

        return $data;
    }

    public function actionRequestview()  //View a specific request record.
    {
        $getrequest = Yii::$app->request;

        $rstl_id = (int) $getrequest->get('rstl_id');
        $request_id = (int) $getrequest->get('request_id');
        $pstc_id = (int) $getrequest->get('pstc_id');

        $request = []; $samples = []; $respond = []; $customer = []; $attachment = []; $pstc = [];
        $subtotal = 0; $discount = 0; $total = 0; $analysis = [];

        if(!empty($rstl_id) && !empty($request_id) && !empty($pstc_id)){
            
            $checkAgency = $this->checkAgency($request_id,$rstl_id);

            if($checkAgency > 0)
            {
                $request = Pstcrequest::findOne($request_id);
               
                //return $request->samples[0]->analysis;
                foreach($request->samples as $sample)
                {   
                    if(count($sample->analysis) > 0){
                        foreach($sample->analysis as $an){
                            $analysis[] = $an;
                            $subtotal =  $subtotal + $an->fee;
                        }
                    }
                }
                $discount = $subtotal * (0/100);
                $total = $subtotal - $discount;

                $customer_id = $request->customer_id;
                $customer = Customer::find()->where(['local_customer_id' =>  $customer_id])->andWhere(['rstl_id' => $rstl_id])->one();
            }
            
            return [
                'request_data'=>$request,
                'sample_data'=>$request->samples,
                'analysis_data'=>$analysis,
                'respond_data'=>$request->respond,
                'pstc_data'=>$request->pstc,
                'customer_data'=>$customer,
                'attachment_data' => ($request->attachment == null) ? $attachment = [] : $request->attachment,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
            ];
            

        }else{
            throw new \yii\web\HttpException(400, 'Please specify parameters correctly. :)');
        }
    }


    public function actionUpdatepstc(){
        
    }

    // ------------------------------------
    // Function for samples. (Create, Update, Delete)
    // ------------------------------------

    public function actionSample()
    {   
        (Yii::$app->request->post('pstc_request_id')) ? $id = Yii::$app->request->post('pstc_request_id') : '';

        $qnty = Yii::$app->request->post('qnty');
        
        for($x = 0; $x < $qnty; $x++)
        {
            $data = (Yii::$app->request->isPut) ? Psctsample::findOne($id) : new Pstcsample; 
            $data->sample_name = ucwords(strtolower(Yii::$app->request->post('sample_name')));
            $data->sample_description = ucfirst(Yii::$app->request->post('sample_description'));
            $data->rstl_id = 11;
            $data->pstc_id = 113;
            $data->pstc_request_id = (int) Yii::$app->request->post('pstc_request_id');
            $data->sampletype_id = 0;
            $data->processed_old = 0;
            $data->created_at = date('Y-m-d H:i:s');
            $data->updated_at = date('Y-m-d H:i:s');
            $data->save(false);
        }
        return $data;
    }

    public function actionSampledelete()
    {
        $data = Psctsample::find()->where(['id' => $id])->one();
        return $data->delete();
    }

    // ------------------------------------
    // Function for Analysis. (Create, Update, Delete)
    // ------------------------------------

    public function actionAnalysis()
    {   
        $method_id = (int) Yii::$app->request->post('method_id');
        $method = Methodreference::findOne($method_id);

        $testname_id = (int) Yii::$app->request->post('testname');
        $testname = Testname::findOne($testname_id);

        $data = (Yii::$app->request->isPut) ? Psctanalysis::findOne($id)  : new Pstcanalysis; 
        $data->rstl_id = Yii::$app->request->post('rstl_id');
        $data->pstc_id = Yii::$app->request->post('pstc_id');
        $data->pstc_sample_id = Yii::$app->request->post('sample_id');
        $data->testname_id = 0;
        $data->testname = $testname->test_name;
        $data->method_id = $method_id;
        $data->method = $method->method;
        $data->reference = $method->reference;
        $data->fee = $method->fee   ;
        $data->quantity = 1;
        $data->package_old =0;
        $data->deleted_old = 0;
        $data->taggingId_old = 0;
        $data->testId_old = 0;
        $data->user_id = 0;
        $data->updated_at = date('Y-m-d H:i:s');
        $data->created_at = date('Y-m-d H:i:s');
        $data->save(false);

        return $data;
    }

    public function actionAnalysisdelete()
    {
        $data = Psctanalysis::find()->where(['id' => $id])->one();
        return $data->delete();
    }

    // ------------------------------------
    // Protected function use for filtering
    // ------------------------------------

    protected function checkAgency($request_id,$rstl_id) //Check if the agency is the owner of the Request.
	{
        $check = Pstcrequest::find()
            ->where('pstc_request_id =:requestId', [':requestId' => $request_id])
            ->andWhere('rstl_id =:rstl_id', [':rstl_id' => $rstl_id])
            ->count();

        return ($check > 0) ? $status = 1 : $status = 0;
    }
    
    protected function checkAccepted($request_id,$rstl_id,$pstc_id) // Check if request is already accepted.
	{
        $check = Pstcrequest::find()
            ->joinWith('respond',true,'INNER JOIN')
            ->where('tbl_pstcrespond.pstc_request_id =:requestId AND accepted =:accepted AND !ISNULL(request_ref_num)', [':requestId' => $request_id,':accepted'=>1])
            ->andWhere('tbl_pstcrequest.rstl_id =:rstlId', [':rstlId' => $rstl_id])
            ->andWhere('tbl_pstcrequest.pstc_id =:pstcId', [':pstcId' => $pstc_id])
            ->count();
            
        return ($check > 0) ? $status = 1 : $status = 0;
    }

    // ------------------------------------
    // Function for Syncing testmethod..
    // ------------------------------------
    
    public function actionCheckmethod($id) //CHECK IF TESTMETHOD IS ALREADY SYNC
    { 
        $data = Methodreference::find()->where(['sync_id' => $id])->one();
        $count = count($data);

        if($count == 0){
            return 'Not Synced';
        }else{
            return 'Synced';
        }
    }

    public function actionSyncmethod() // SYNC TESTMETHOD TO THE MAIN SERVER
    { 
        $data = new Methodreference; 
        $data->method = Yii::$app->request->post('method');
        $data->reference = Yii::$app->request->post('reference');
        $data->fee = Yii::$app->request->post('fee');
        $data->sync_id = Yii::$app->request->post('sync_id');
        $data->create_time = date('Y-m-d H:i:s');
        $data->update_time = date('Y-m-d H:i:s');

        if($data->save(false))
        {
            $test = new Testnamemethod; 
            $test->testname_id = Yii::$app->request->post('testname_id');
            $test->methodreference_id = $data->methodreference_id;
            $test->lab_id = Yii::$app->request->post('lab_id');
            $test->sampletype_id = Yii::$app->request->post('sampletype_id');
            $test->create_time = date('Y-m-d H:i:s');
            $test->update_time = date('Y-m-d H:i:s');
            $test->save(false);
        }
        return $test;
    }

    public function actionPstclist($agency){

        $pstcs = ArrayHelper::map(Pstc::find()->where(['agency_id' => $agency])->all(), 'pstc_id','name');
        return $pstcs;
        
    }

    public function actionCustomerlist($agency){

        // $pstcs = ArrayHelper::map(Pstc::find()->where(['rsl_id' => $agency])->all(), 'pstc_id','name');
        // return $pstcs;

        $customer = ArrayHelper::map(Customer::find()->where('rstl_id =:rstlId',[':rstlId'=>$agency])->all(), 'local_customer_id',
            function($customer, $defaultValue) {
                return $customer->customer_name;
        });

        return $customer;  
    }

    public function actionListlab() // GET LISTS OF LAB, SAMPLE TYPE AND TESTNAME IN THE MAIN SERVER
    {
        // $testnamelist = ArrayHelper::map(Customer::find()->all(), 'testname_id', 
        //     function($testnamelist, $defaultValue) {
        //         return $testnamelist->test_name;
        // });
        
        $labs = ArrayHelper::map(Lab::find()->all(), 'lab_id', 
            function($laboratory, $defaultValue) {
                return $laboratory->labname;
        });

        $sampletypes = ArrayHelper::map(Sampletype::find()->all(), 'sampletype_id', 
            function($sampletypes, $defaultValue) {
                return $sampletypes->type;
        });

        $testnamelist = ArrayHelper::map(Testname::find()->all(), 'testname_id', 
            function($testnamelist, $defaultValue) {
                return $testnamelist->test_name;
        });

        $customers = ArrayHelper::map(Customer::find()->all(), 'customer_id', 
            function($customerlist, $defaultValue) {
                return $customerlist->customer_name;
        });
        
        $lists = array(
            'labs' => $labs,
            'sampletypes' => $sampletypes,
            'testnamelist' => $testnamelist,
            'customers' => $customers
            // 'sampletemplates' => $sampletemplates
        );

        return $lists;
    }

    public function actionTestnamemethods(){

        $getrequest = Yii::$app->request;
        $id = (int) $getrequest->get('id');
        $testnamemethods = Testnamemethod::find()->with('testname')->where(['sampletype_id'=>$id])->groupBy('testname_id')->asArray()->all();

        return $testnamemethods;
    }

    public function actionMethodreference($id){

        $data = Methodreference::find()->where(['methodreference_id' => $id])->one();
        return $data;
    }

    public function actionTestnamemethod(){

        $getrequest = Yii::$app->request;
        $testname_id = (int) $getrequest->get('testname_id');
        $sampletype_id = (int) $getrequest->get('sampletype_id');
        $rstl_id = (int) $getrequest->get('rstl_id');

        $testnamemethod = Testnamemethod::find()
        ->innerJoinWith('methodreference', 'tbl_testname_method.methodreference_id = tbl.methodreference.methodreference_id')
        ->where(['tbl_testname_method.testname_id'=>$testname_id,'tbl_testname_method.sampletype_id'=>$sampletype_id])
        ->andWhere(['like','sync_id',$rstl_id."-%",false])
        ->all();
     

        return $testnamemethod;
    }

    public function actionSampletest(){

        $getrequest = Yii::$app->request;
        $id = (int) $getrequest->get('id');

        $sample = Sample::findOne($id);
        return $sample;
    }

}
