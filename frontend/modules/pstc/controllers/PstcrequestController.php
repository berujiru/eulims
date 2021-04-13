<?php

namespace frontend\modules\pstc\controllers;

use Yii;
use yii\data\ActiveDataProvider;    
use common\models\referral\Pstcrequest;
use common\models\referral\Pstcsample;
use common\models\referral\Pstcanalysis;
use common\models\lab\Package;
use common\models\lab\Testnamemethod;
use common\models\lab\Methodreference;
use common\models\lab\Testname;
use common\models\lab\Packagelist;
use common\models\lab\PackagelistSearch;
use common\models\lab\Request;
use common\models\lab\Sample;
use common\models\lab\Analysis;
use common\models\lab\Customer;
use common\models\lab\Requestcode;
use common\models\lab\Discount;
use common\models\lab\Labsampletype;
use common\models\lab\Sampletype;
use common\models\lab\SampleName;
use common\models\lab\Lab;
use common\models\lab\Testcategory;
use frontend\modules\lab\components\eRequest;
use DateTime;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\data\ArrayDataProvider;
use common\components\PstcComponent;
use common\models\system\Profile;
use linslin\yii2\curl;
use yii\helpers\Json;
use common\models\system\LoginForm;

class PstcrequestController extends Controller
{
    public function beforeAction($action)
    {
        if(parent::beforeAction($action)) 
        {
            if(!isset($_SESSION['usertoken'])){
                return $this->redirect('/pstc')->send();
            }

            if(!isset(Yii::$app->user->identity->profile->rstl_id)){
                return $this->redirect('/site/login')->send();
            }            

            return true;
        }
    }

    public function actionIndex()
    {
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
        $pstcId = $_SESSION['pstcid'];
        $pstcComponent = new PstcComponent();
        $referrals = json_decode($pstcComponent->getAll($rstlId),true);
        
        if((int) $referrals == 0){
            $referralDataprovider = new ArrayDataProvider([
                'allModels' => [],
                'pagination'=> ['pageSize' => 10],
            ]);
        } else {
            $referralDataprovider = new ArrayDataProvider([
                'allModels' => $referrals,
                'pagination'=> ['pageSize' => 10],
            ]);
        }

        return $this->render('index', [
            'dataProvider' => $referralDataprovider,
        ]);
    } 

    public function actionView()
    {
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
        $requestId = (int) Yii::$app->request->get('request_id');
        $pstcId = (int) Yii::$app->request->get('pstc_id');

        if($rstlId > 0 && $requestId > 0 && $pstcId > 0)
        {
            $function = new PstcComponent();
            $details = json_decode($function->getViewRequest($requestId,$rstlId,$pstcId),true);

            $request = $details['request_data'];
            $samples = $details['sample_data'];
            $analysis = $details['analysis_data'];
            $respond = $details['respond_data'];
            $pstc = $details['pstc_data'];
            $customer = $details['customer_data'];
            $attachment = $details['attachment_data'];
            $subtotal = $details['subtotal'];
            $discount = $details['discount'];
            $total = $details['total'];

            $sampleDataProvider = new ArrayDataProvider([
                'allModels' => $samples,
                'pagination'=>false,
            ]);

            $analysisDataProvider = new ArrayDataProvider([
                'allModels' => $analysis,
                'pagination'=>false,
            ]);

            $attachmentDataprovider = new ArrayDataProvider([
                'allModels' => $attachment,
                'pagination' => false,
            ]);

            return $this->render('view', [
                'model' => new Request(), 
                'request' => $request,
                'customer' => $customer,
                'sample' => $samples,
                'sampleDataProvider' => $sampleDataProvider,
                'analysisDataProvider'=> $analysisDataProvider,
                'attachmentDataprovider'=> $attachmentDataprovider,
                'respond' => $respond,
                'pstc' => $pstc,
                'subtotal' => $subtotal,
                'discounted' => $discount,
                'total' => $total,
                'countSample' => count($samples),
                'countAnalysis' => count($analysis),
            ]);
        }else{
            Yii::$app->session->setFlash('error', "Invalid request!");
            return $this->redirect(['/pstc/pstcrequest/accepted']);
        }
    }

    public function actionCreate()
    {
        $model = new Pstcrequest;
        $rstl_id = (int) Yii::$app->user->identity->profile->rstl_id;
        $mi = !empty(Yii::$app->user->identity->profile->middleinitial) ? " ".substr(Yii::$app->user->identity->profile->middleinitial, 0, 1).". " : " ";
        $user_fullname = Yii::$app->user->identity->profile->firstname.$mi.Yii::$app->user->identity->profile->lastname;

        $customers = $this->listCustomers($rstl_id);
        
        if($user_fullname){
            $model->received_by = $user_fullname;
        } else {
            $model->received_by = "";
        }

        if(Yii::$app->request->post()) 
        {
            $post = Yii::$app->request->post('Pstcrequest');

            $testarray = [
                'rstl_id' => $rstl_id,
                'customer_id' => (int) $post['customer_id'],
                'user_id' => (int) Yii::$app->user->identity->profile->user_id,
                'submitted' => $post['submitted_by'],
                'received' => $user_fullname,
                'pstcid' => $_SESSION['pstcid']
            ];

            $function = new PstcComponent();
            $data = json_decode($function->getRequestcreate($testarray),true);
       
            if($data){
                Yii::$app->session->setFlash('success', 'Request successfully saved!');
                return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$data['pstc_request_id'],'pstc_id'=>$data['pstc_id']]);
            }else{
                Yii::$app->session->setFlash('error', 'Failed!');
                return $this->redirect(['/lab/request/view','request_id'=>$data['pstc_request_id'],'pstc_id' => $data['pstc_id']]);
            }
        }else{
            if(\Yii::$app->request->isAjax){
                return $this->renderAjax('create', [
                    'model' => $model,
                    'customers' => $customers,
                ]);
            }else{
                return $this->renderAjax('create', [
                    'model' => $model,
                    'customers' => $customers,
                ]);
            }
        }
    }
    
    public function actionCreatesample()
    {
        $model = new Pstcsample;

        if(isset($_POST['qnty']))
        {
            $post = Yii::$app->request->post('Pstcsample');
            $testarray = [
                'qnty' => $_POST['qnty'],
                'pstc_request_id' => (int) $_POST['pstc_request_id'],
                'sample_name' => $post['sample_name'],
                'sample_description' => $post['sample_description'],
            ];
            // var_dump($testarray); exit();   
            $function = new PstcComponent();
            $data = json_decode($function->getSamplecreate($testarray),true);
            
            if($data){
                Yii::$app->session->setFlash('success', 'Request successfully saved!');
                return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$data['pstc_request_id'],'pstc_id'=>$data['pstc_id']]);
            }else{
                Yii::$app->session->setFlash('error', 'Failed!');
                return $this->redirect(['/lab/request/view','request_id'=>$data['pstc_request_id'],'pstc_id' => $data['pstc_id']]);
            }
        }

        return $this->renderAjax('createsample', [
            'model' => $model,
            'request_id' => (int) Yii::$app->request->get('request_id'),
            'sampletemplates' =>$this->listSampletemplate(),
        ]);
    }

    public function actionCreateanalysis()
    {
        $request_id = (int) Yii::$app->request->get('request_id');
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
        $pstc_id = (int) Yii::$app->request->get('pstc_id');

        $model = new Pstcanalysis;

        if(isset($_POST['sampletype']))
        {
            $request_id = (int) $_POST['request_id'];
            $samples = Yii::$app->request->post('base_samples');
            foreach($samples as $sample){
                $testarray = [
                    'sample_id' => $sample,
                    'method_id' => (int) $_POST['method_id'],
                    'rstl_id' => $rstlId,
                    'pstc_id' => (int) $_POST['pstc_id'],
                    'testname' =>(int) $_POST['testname'],
                ];

                $function = new PstcComponent();
                $data = json_decode($function->getAnalysiscreate($testarray),true);
            }
            if($data){
                Yii::$app->session->setFlash('success', 'Test Name Added!');
                return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$request_id,'pstc_id'=>$_POST['pstc_id']]);
            }else{
                Yii::$app->session->setFlash('error', 'Failed!');
                return $this->redirect(['/lab/request/view','request_id'=>$data['pstc_request_id'],'pstc_id' => $_POST['pstc_id']]);
            }
        }

        $function = new PstcComponent();
        $details = json_decode($function->getViewRequest($request_id,$rstlId,$pstc_id),true);
        $samples = $details['sample_data'];
        $lists = json_decode($function->listLab(),true);

        return $this->renderAjax('createanalysis', [
            'model' => $model,
            'request_id' => $request_id,
            'pstc_id' =>  $pstc_id,
            'base_sample' => $samples,
            'sampletypes'=> $lists['sampletypes'],
            'sampletype' => []
        ]);
    }

    public function actionGetlisttemplate() {
        if(isset($_GET['template_id'])){
            $id = (int) $_GET['template_id'];
            $modelSampletemplate =  SampleName::findOne(['sample_name_id'=>$id]);
            if(count($modelSampletemplate)>0){
                $sampleName = $modelSampletemplate->sample_name;
                $sampleDescription = $modelSampletemplate->description;
            } else {
                $sampleName = "";
                $sampleDescription = "";
            }
        } else {
            $sampleName = "Error getting sample name";
            $sampleDescription = "Error getting description";
        }
        return Json::encode([
            'name'=>$sampleName,
            'description'=>$sampleDescription,
        ]);
    }


    public function actionSavetolocal()
    {
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
        $request_id = (int) Yii::$app->request->post('request_id');
        $pstc_id = $_SESSION['pstcid'];
        
        $function = new PstcComponent();
        $pstc = json_decode($function->getViewRequest($request_id,$rstlId,$pstc_id),true);
        
        $post= Yii::$app->request->post('eRequest');

        $model = new Request;
        $model->request_datetime = $post['request_datetime'];
        $model->rstl_id = Yii::$app->user->identity->profile->rstl_id;
        $model->customer_id = $pstc['request_data']['customer_id'];
        $model->lab_id = $post['lab_id'];
        $model->payment_type_id = 1;
        $model->discount_id = $post['discount_id'];
        $model->purpose_id = $post['purpose_id'];
        $model->modeofrelease_ids =1;
        $model->total = $post['lab_id'];
        $model->conforme = $post['conforme'];
        $model->receivedBy= $post['receivedBy'];
        $model->request_type_id = $post['request_type_id'];
        $model->report_due = $post['report_due'];
        $model->pstc_id = $pstc['request_data']['pstc_request_id'];
        if($model->save(false))
        {
            foreach($pstc['sample_data'] as $sampol) 
            {
                $sample = new Sample;
                $sample->request_id = $model->request_id;
                $sample->rstl_id = Yii::$app->user->identity->profile->rstl_id;
                $sample->sampletype_id = $sampol['sampletype_id'];
                $sample->samplename = $sampol['sample_name'];
                $sample->description = $sampol['sample_description'];
                $sample->sampling_date = date('Y-m-d H:i:s');
                $sample->sample_month = date_format(date_create($model->request_datetime),'m');
                $sample->sample_year = date_format(date_create($model->request_datetime),'Y');
                
                if($sample->save(false))
                {
                    foreach($pstc['analysis_data'] as $an){
                        $analysis = new Analysis;
                        $analysis->request_id = $model->request_id;
                        $analysis->sample_id = $sample->sample_id;
                        $analysis->testname =$an['testname'];
                        $analysis->methodref_id =$an['method_id'];
                        $analysis->method =$an['method'];
                        $analysis->references =$an['reference'];
                        $analysis->fee = $an['fee'];
                        $analysis->quantity =1;
                        $analysis->date_analysis = date('Y-m-d');
                        $analysis->rstl_id = $model->rstl_id;
                        $analysis->test_id = 0;
                        $analysis->save(false);
                    }
                    $pstc_id = $pstc['request_data']['pstc_request_id'];
                    $function = new PstcComponent();
                    $pstc = json_decode($function->getAccepted($pstc_id),true);
                    // var_dump($pstc); exit();
                    Yii::$app->session->setFlash('success', 'Request Saved to local!');

                    // return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$pstc['pstc_request_id'],'pstc_id'=>$pstc['pstc_id']]);
                    return $this->redirect(['/lab/request/view','id'=>$model->request_id]);
                }
            }
        }
    }


    public function actionRequest_local()
    {
        $model = new eRequest();
        $connection= Yii::$app->labdb;
        $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        $transaction = $connection->beginTransaction();
        
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
        $requestId = (int) Yii::$app->request->get('request_id');
        $pstcId = $_SESSION['pstcid'];
        $function = new PstcComponent();

        if($rstlId > 0 && $pstcId > 0 && $requestId > 0) {
            $details = json_decode($function->getViewRequest($requestId,$rstlId,$pstcId),true);
           
            $request = $details['request_data'];
            $samples = $details['sample_data'];
            $analyses = $details['analysis_data'];
            $respond = $details['respond_data'];
            $pstc = $details['pstc_data'];
            $customer = $details['customer_data'];
            $subtotal = $details['subtotal'];
            $discount = $details['discount'];
            $total = $details['total'];

            $sampleDataProvider = new ArrayDataProvider([
                'allModels' => $samples,
                'pagination'=>false,
            ]);

            $analysisDataProvider = new ArrayDataProvider([
                'allModels' => $analyses,
                'pagination'=>false,
            ]);
        } else {
            Yii::$app->session->setFlash('error', 'Invalid request!');
            return $this->redirect(['/pstc/pstcrequest']);
        }

        if ($model->load(Yii::$app->request->post()) && count($analyses) >= count($samples)) {
            $post = Yii::$app->request->post('eRequest');
            $total_fee = $total - ($subtotal * ($post['discount']/100));

            $model->modeofrelease_ids = implode(",", $post['modeofreleaseids']);
            $model->total = $total_fee;
            $model->pstc_request_id = $requestId;
            $model->request_datetime = date('Y-m-d H:i:s');
            $model->created_at = date('Y-m-d H:i:s');
            $model->rstl_id = $rstlId;
            $model->customer_id = $request['customer_id'];
            $model->pstc_id = $pstcId;

            $sampleSave = 0;
            $analysisSave = 0;
			$requestSave = 0;
            if($model->save(false)) {
                $local_requestId = $model->request_id;
                $psct_requestdetails = json_decode($function->getRequestDetails($requestId,$rstlId,$pstcId),true);

                $samples_analyses = $psct_requestdetails['sample_analysis_data'];
                $customer = $psct_requestdetails['customer_data'];

                foreach($samples_analyses as $sample) {
                    $modelSample = new Sample();

                    $modelSample->request_id = $local_requestId;
                    $modelSample->rstl_id = $rstlId;
                    $modelSample->sample_month = date_format(date_create($model->request_datetime),'m');
                    $modelSample->sample_year = date_format(date_create($model->request_datetime),'Y');
                    //$modelSample->testcategory_id = 0; //pstc request, test category id is in analysis
                    //$modelSample->sampletype_id = 0; //pstc request, sample type id is in analysis
                    $modelSample->samplename = $sample['sample_name'];
                    $modelSample->description = $sample['sample_description'];
                    $modelSample->customer_description = $sample['customer_description'];
                    // $modelSample->sampling_date = $sample['sampling_date']; //to be updated
                    $modelSample->sampling_date = empty($sample['sampling_date']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s',strtotime($sample['sampling_date'])); //to be updated
                    $modelSample->pstcsample_id = $sample['pstc_sample_id'];
                    //$modelSample->testcategory_id = $post['testcategory_id'];
                    //$modelSample->sampletype_id = $post['sampletype_id'];
                    $modelSample->testcategory_id = $sample['testcategory_id'];
                    $modelSample->sampletype_id = $sample['sampletype_id'];

                    if($modelSample->save(false)) {
                        foreach ($sample['analyses'] as $analysis) {
                            $modelAnalysis = new Analysis();
                            $modelAnalysis->rstl_id = $rstlId;
                            $modelAnalysis->date_analysis = date('Y-m-d');
                            $modelAnalysis->request_id = $local_requestId;
                            $modelAnalysis->pstcanalysis_id = $analysis['pstc_analysis_id'];
                            $modelAnalysis->package_id =  $analysis['package_id'];
                            $modelAnalysis->package_name =  $analysis['package_name'];
                            $modelAnalysis->sample_id = $modelSample->sample_id;
                            $modelAnalysis->test_id = $analysis['testname_id'];
                            $modelAnalysis->testname = $analysis['testname'];
                            $modelAnalysis->methodref_id = $analysis['method_id'];
                            $modelAnalysis->method = $analysis['method'];
                            $modelAnalysis->references = $analysis['reference'];
                            $modelAnalysis->fee = $analysis['fee'];
                            $modelAnalysis->testcategory_id = $analysis['testcategory_id'];
                            $modelAnalysis->sample_type_id = $analysis['sampletype_id'];
                            $modelAnalysis->is_package = $analysis['is_package'];
                            $modelAnalysis->is_package_name = $analysis['is_package_name'];
                            $modelAnalysis->quantity = 1;
                            if($modelAnalysis->save(false)) {
                                $analysisSave = 1;
                            } else {
                                $transaction->rollBack();
                                $analysisSave = 0;
                            }
                        }
                        $sampleSave = 1;
                    } else {
                        $transaction->rollBack();
                        $sampleSave = 0;
                    }
                }

                $requestSave = 1;

                if($sampleSave == 1 && $analysisSave == 1 && $requestSave == 1) {
                    //$transaction->commit();
                    //if($transaction->commit()) {
                    //if($generate_code == 'success') {
                        $generate_code = $this->saveRequest($local_requestId,$model->lab_id,$rstlId,date('Y',strtotime($model->request_datetime)));
                        //$func = new Functions();
                        //$samplecode = $func->GenerateSampleCode($local_requestId);
                        //print_r($generate_code);
                        //exit;
                    //if($generate_code == 'success') {
                        $sample_data = [];
                        $analysis_data = [];
                        if($generate_code == "success") {
                            $local_samples = Sample::find()->where(['request_id'=>$local_requestId])->asArray()->all();
                            $local_request = Request::findOne($local_requestId);
                            $local_analyses = Analysis::find()->where(['request_id'=>$local_requestId])->asArray()->all();

                            $requestData = [
                                'pstc_request_id' => $local_request->pstc_request_id,
                                'request_ref_num' => $local_request->request_ref_num,
                                'rstl_id' => $local_request->rstl_id,
                                'pstc_id' => $local_request->pstc_id,
                                'customer_id' => $local_request->customer_id,
                                'local_request_id' => $local_request->request_id,
                                'request_date_created' => $local_request->request_datetime,
                                'estimated_due_date' => $local_request->report_due,
                                'lab_id' => $local_request->lab_id,
                                'discount_id' => (int) $local_request->discount_id,
                                'discount_rate' => $local_request->discount,
                            ];

                            foreach ($local_samples as $s_data) {
                                $sampleData = [
                                    'pstc_sample_id' => $s_data['pstcsample_id'],
                                    'sample_code' => $s_data['sample_code'],
                                    'sample_month' => $s_data['sample_month'],
                                    'sample_year' => $s_data['sample_year'],
                                    'rstl_id' => $local_request->rstl_id,
                                    'pstc_id' => $pstcId,
                                    'sample_description' => $s_data['description'],
                                    'customer_description' => $s_data['customer_description'],
                                    'sample_name' => $s_data['samplename'],
                                    'local_sample_id' => $s_data['sample_id'],
                                    'local_request_id' => $local_requestId,
                                    'sampletype_id' => $s_data['sampletype_id'],
                                    'testcategory_id' => $s_data['testcategory_id'],
                                    'sampling_date' => $s_data['sampling_date'],
                                ];
                                array_push($sample_data, $sampleData);
                            }

                            foreach ($local_analyses as $a_data) {
                                $analysisData = [
                                    'pstc_analysis_id' => $a_data['pstcanalysis_id'],
                                    //'pstc_sample_id' => $a_data['pstc_sample_id'],
                                    'local_analysis_id' => $a_data['analysis_id'],
                                    'local_sample_id' => $a_data['sample_id'],
                                    'rstl_id' => $local_request->rstl_id,
                                    'testname_id' => $a_data['test_id'],
                                    'testname' => $a_data['testname'],
                                    'method_id' => $a_data['methodref_id'],
                                    'method' => $a_data['method'],
                                    'reference' => $a_data['references'],
                                    'package_id' => $a_data['package_id'],
                                    'package_name' => $a_data['package_name'],
                                    'quantity' => $a_data['quantity'],
                                    'fee' => $a_data['fee'],
                                    'pstc_id' => $pstcId,
                                    'date_analysis' => $a_data['date_analysis'],
                                    'is_package' => $a_data['is_package'],
                                    'is_package_name' => $a_data['is_package_name'],
                                    'sampletype_id' => $a_data['sample_type_id'],
                                    'testcategory_id' => $a_data['testcategory_id'],
                                    'type_fee_id' => $a_data['type_fee_id'],
                                    'local_user_id' => (int) Yii::$app->user->identity->profile->user_id,
                                    'local_request_id' => $a_data['request_id'],
                                ];
                                array_push($analysis_data, $analysisData);
                            }

                            $pstc_request_details = Json::encode(['request_data'=>$requestData,'sample_data'=>$sample_data,'analysis_data'=>$analysis_data,'pstc_id'=>$pstcId,'rstl_id'=>$rstlId],JSON_NUMERIC_CHECK);
                            //$pstcUrl='https://eulimsapi.onelab.ph/api/web/referral/pstcrequests/updaterequest_details';
                            $pstcUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/pstcrequests/updaterequest_details';
                       
                            $curl = new curl\Curl();
                            $pstc_return = $curl->setRequestBody($pstc_request_details)
                            ->setHeaders([
                                'Content-Type' => 'application/json',
                                'Content-Length' => strlen($pstc_request_details),
                            ])->post($pstcUrl);

                            if($pstc_return == 1) {
                                $transaction->commit();
                                Yii::$app->session->setFlash('success', 'Request successfully saved!');
                                return $this->redirect(['/lab/request/view','id'=>$local_requestId]);
                            } else {
                                $transaction->rollBack();
                                //return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Request 1 failed to save!</div>";
                                Yii::$app->session->setFlash('error', 'Request failed to save!');
                                return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$requestId,'pstc_id'=>$pstcId]);
                            }
                        } else {
                            $transaction->rollBack();
                            //return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to generate samplecode!</div>";
                            Yii::$app->session->setFlash('error', 'Failed to generate samplecode!');
                            return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$requestId,'pstc_id'=>$pstcId]);
                        }
                    //} else {
                    //    $transaction->rollBack();
                        //return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Request 2 failed to save!</div>";
                    //    Yii::$app->session->setFlash('error', 'Request failed to save!');
                    //    return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$requestId,'pstc_id'=>$pstcId]);
                   //}
                } else {
                    //$requestSave = 0;
                    $transaction->rollBack();
                    //return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Request 2 failed to save!</div>";
                    Yii::$app->session->setFlash('error', 'Request failed to save!');
                    return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$requestId,'pstc_id'=>$pstcId]);
                }
            } else {
                //Yii::$app->session->setFlash('error', "Request failed to save!");
                //return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$requestId,'pstc_id'=>$pstcId]);
                $transaction->rollBack();
                //return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Request 3 failed to save!</div>";
                Yii::$app->session->setFlash('error', 'Request failed to save!');
                return $this->redirect(['/pstc/pstcrequest/view','request_id'=>$requestId,'pstc_id'=>$pstcId]);
            }
        } else {
            $date = new DateTime();
            $date2 = new DateTime();
            $profile= Profile::find()->where(['user_id'=> Yii::$app->user->id])->one();
            date_add($date2,date_interval_create_from_date_string("1 day"));
            $model->request_datetime = date("Y-m-d h:i:s");
            $model->report_due = date_format($date2,"Y-m-d");
            $model->created_at = date('U');
            $model->rstl_id = $rstlId;
            $model->payment_type_id = 1;
            $model->modeofrelease_ids = '1';
            $model->discount_id = 0;
            $model->discount = '0.00';
            $model->total = 0.00;
            $model->posted = 0;
            $model->status_id = 1;
            $model->request_type_id = 1;
            $model->modeofreleaseids = '1';
            $model->payment_status_id = 1;
            //$model->testcategory_id = ;
            //$model->sampletype_id = ;
            $model->request_date = date("Y-m-d");
            $model->customer_id = $request['customer_id'];
            $model->conforme = $request['submitted_by'];

            if($profile){
                $model->receivedBy=$profile->firstname.' '. strtoupper(substr($profile->middleinitial,0,1)).'. '.$profile->lastname;
            }else{
                $model->receivedBy="";
            }

            if(\Yii::$app->request->isAjax) {
                return $this->renderAjax('_formRequestDetails', [
                    'model' => $model,
                    'request' => $request,
                    'customer' => $customer,
                    'sampleDataProvider' => $sampleDataProvider,
                    'analysisDataprovider'=> $analysisDataProvider,
                    'respond' => $respond,
                    'pstc' => $pstc,
                    'subtotal' => $subtotal,
                    'discounted' => $discount,
                    'total' => $total,
                    'countSample' => count($samples),
                    'countAnalysis' => count($analyses),
                    //'testcategory' => null,
                    //'sampletype' => null,
                    'testcategory' => ArrayHelper::map(Testcategory::find()->all(),'testcategory_id','category'), //data should be in synched in ulims portal
                    'laboratory' => ArrayHelper::map(Lab::find()->all(),'lab_id','labname'), //data should be in synched in ulims portal
                    'sampletype' => ArrayHelper::map(Sampletype::find()->all(),'sampletype_id','type'), //data should be in synched in ulims portal
                ]);
            } else {
                return $this->renderAjax('_formRequestDetails', [
                    'model' => $model,
                    'request' => $request,
                    'customer' => $customer,
                    'sampleDataProvider' => $sampleDataProvider,
                    'analysisDataprovider'=> $analysisDataprovider,
                    'respond' => $respond,
                    'pstc' => $pstc,
                    'subtotal' => $subtotal,
                    'discounted' => $discount,
                    'total' => $total,
                    'countSample' => count($samples),
                    'countAnalysis' => count($analyses),
                    //'testcategory' => null,
                    //'sampletype' => null,
                    'testcategory' => ArrayHelper::map(Testcategory::find()->all(),'testcategory_id','category'), //data should be in synched in ulims portal
                    'laboratory' => ArrayHelper::map(Lab::find()->all(),'lab_id','labname'), //data should be in synched in ulims portal
                    'sampletype' => ArrayHelper::map(Sampletype::find()->all(),'sampletype_id','type'), //data should be in synched in ulims portal
                ]);
            }
        }
    }

    public function actionListsampletype() {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $sampletypeId = end($_POST['depdrop_parents']);
          
            $function = new PstcComponent();
            $list = json_decode($function->testnamemethods($sampletypeId),true);
            
            $selected  = null;
            if ($sampletypeId != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i) {
                    if($i['testname']){
                        $out[] = ['id' => $i['testname']['testname_id'], 'name' => $i['testname']['test_name']];
                        if ($i == 0) {
                            $selected = $testname['testname_id'];
                        }
                    }
                }
                \Yii::$app->response->data = Json::encode(['output'=>$out, 'selected'=>'']);
                return;
            }
        }
        echo Json::encode(['output' => '', 'selected'=>'']);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $session = Yii::$app->session;    
        if($model->delete()) {
            location.reload();
        } else {
            return $model->error();
        }
    }

    protected function findModel($id)
    {
        if (($model = Pstcanalysis::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionGettestnamemethod()
	{
        $testname_id = $_GET['testname_id'];
        $sampletype_id = $_GET['sampletype_id'];
        $sample = $_GET['sample'];
        $rstl_id = (int) Yii::$app->user->identity->profile->rstl_id;

        $function = new PstcComponent();
        $testnamemethod = json_decode($function->testnamemethod($testname_id,$sampletype_id,$rstl_id),true);
        $testnamedataprovider = new ArrayDataProvider([
            'allModels' => $testnamemethod,
            'pagination' => [
                'pageSize' => false,
            ],
        ]);
   
        return $this->renderAjax('_method', [
           'testnamedataprovider' => $testnamedataprovider,
        ]);
	
     }
    // ------------------------------------
    // Protected functions
    // ------------------------------------

    protected function listCustomers($rstlId)
    {
        $function = new PstcComponent();
        $customer = json_decode($function->getcustomer($rstlId),true);
        // $customer = ArrayHelper::map(Customer::find()->where('rstl_id =:rstlId',[':rstlId'=>$rstlId])->all(), 'customer_id',
        //     function($customer, $defaultValue) {
        //         return $customer->customer_name;
        // });
        return $customer;
    }

    protected function listSampletemplate()
    {
        $sampleTemplate = ArrayHelper::map(SampleName::find()->all(), 'sample_name_id', 
            function($sampleTemplate, $defaultValue) {
                return $sampleTemplate->sample_name;
        });

        return $sampleTemplate;
    }

    public function actionGet_testcategory()
    {
        $labId = (int) Yii::$app->request->get('lab_id');
        if($labId > 0)
        {
            $testcategory = Labsampletype::find()
                //->select('tbl_sampletype.*')
                ->joinWith(['testcategory'],true)
                ->where('tbl_lab_sampletype.lab_id = :labId', [':labId' => $labId])
                //->asArray()
                ->groupBy('tbl_testcategory.testcategory_id')
                ->all();

            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            if(count($testcategory) > 0)
            {
                foreach($testcategory as $list) {
                   
                    $data[] = ['id' => $list->testcategory->testcategory_id, 'text' => $list->testcategory->category];
                }
            } else {
                $data = ['id' => '', 'text' => 'No results found'];
            }
        } else {
            $data = ['id' => '', 'text' => 'No results found'];
        }
        return ['data' => $data];
    }


    public function actionCreatepackage($id)
    {
        $model = new Packagelist();
        $request_id = $_GET['id'];
        $searchModel = new PackagelistSearch();
        $session = Yii::$app->session;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if ($model->load(Yii::$app->request->post())) {
            $sample_ids= $_POST['sample_ids'];
            $ids = explode(',', $sample_ids);  
            $post= Yii::$app->request->post();       
        }

        $samplesQuery = Pstcsample::find()->where(['pstc_request_id' => $id]);
            $sampleDataProvider = new ActiveDataProvider([
                'query' => $samplesQuery,
                'pagination' => [
                'pageSize' => false,
            ],             
        ]);

        $request = $this->findRequest($request_id);
        $testcategory = $this->listTestcategory();
        
        $sampletype = [];
        $test = [];

        if ($model->load(Yii::$app->request->post())) {
                 $sample_ids= $_POST['sample_ids'];
                 $ids = explode(',', $sample_ids);  
                 $post= Yii::$app->request->post();

                 foreach ($ids as $sample_id){  

                     $p = $post['Packagelist']['name'];
                     $r = str_replace("," , "", $post['Packagelist']['rate']);

                     $analysis = new Pstcanalysis();
                     $modelpackage =  Package::findOne(['id'=>$post['Packagelist']['name']]);

                     $analysis->pstc_sample_id = $sample_id;
                     $analysis->rstl_id = $GLOBALS['rstl_id'];
                     $analysis->pstc_id = $_SESSION['pstc_id'];
                     $analysis->testname_id = 0;
                     $analysis->testname = $modelpackage->name;
                     $analysis->package_id = $modelpackage->id;
                     $analysis->package_name = $modelpackage->name;
                     $analysis->method_id = 0;
                     $analysis->method = "-";
                     $analysis->reference = "-";
                     $analysis->fee = $r;
                     $analysis->quantity = 1;
                     $analysis->is_package = 1;
                     $analysis->testId_old = 0;
                     $analysis->package_old = 0;
                     $analysis->deleted_old = 0;
                     $analysis->taggingId_old = 0;
                     $analysis->user_id = 0;
                     $analysis->save();

                     $testname_id = $_POST['package_ids'];
                     $test_ids = explode(',', $testname_id);  

                     foreach ($test_ids as $t_id){

                        $analysis_package = new Pstcanalysis();
                        $testnamemethod =  Testnamemethod::findOne(['testname_method_id'=>$t_id]);
                        $modeltest=  Testname::findOne(['testname_id'=>$testnamemethod->testname_id]);
                        $methodreference =  Methodreference::findOne(['method_reference_id'=>$testnamemethod->method_id]);

                        $modelmethod=  Methodreference::findOne(['testname_id'=>$t_id]);
                        
                        $analysis_package->pstc_sample_id = $sample_id;
                        $analysis_package->rstl_id = $GLOBALS['rstl_id'];
                        $analysis_package->pstc_id = $_SESSION['pstcid'];
                        $analysis_package->testname_id = $t_id;
                        $analysis_package->testname = $modeltest->testName;
                        $analysis_package->method_id = $methodreference->method_reference_id;
                        $analysis_package->method = $methodreference->method;
                        $analysis_package->reference = $methodreference->reference;
                        $analysis_package->fee = 0;
                        $analysis_package->quantity = 1;
                        $analysis_package->is_package = 1;
                        $analysis_package->testId_old = 0;
                        $analysis_package->package_old = 0;
                        $analysis_package->deleted_old = 0;
                        $analysis_package->taggingId_old = 0;
                        $analysis_package->user_id = 0;
                        $analysis_package->save(false);
                      
                    }      
                 }                   
                 Yii::$app->session->setFlash('success', 'Package Succesfull Added');
                 return $this->redirect(['/lab/request/view', 'id' =>$request_id]);
        } 
        if (Yii::$app->request->isAjax) {

            $analysismodel = new Analysis();
            $analysismodel->rstl_id = $GLOBALS['rstl_id'];
            $analysismodel->pstcanalysis_id = $GLOBALS['rstl_id'];
            $analysismodel->request_id = $GLOBALS['rstl_id'];
            $analysismodel->testname = $GLOBALS['rstl_id'];
            $analysismodel->cancelled = 0;
            $analysismodel->is_package = 1;
            $analysismodel->sample_id = $GLOBALS['rstl_id'];
            $analysismodel->sample_code = $GLOBALS['rstl_id'];
            $analysismodel->date_analysis = '2018-06-14 7:35:0';

            return $this->renderAjax('_packageform', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'request_id'=>$request_id,
                'sampleDataProvider' => $sampleDataProvider,
                'testcategory' => $testcategory,
                'test' => $test,
                'sampletype'=>$sampletype
            ]);
        }else{
            $model->rstl_id = $GLOBALS['rstl_id'];
            return $this->render('_packageform', [
                'model' => $model,
                'request_id'=>$request_id,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'sampleDataProvider' => $sampleDataProvider,
                'testcategory' => $testcategory,
                'test' => $test,
                'sampletype'=>$sampletype
            ]);
        }
    }

    public function actionListpackage()
    {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $id = end($_POST['depdrop_parents']);
            //$list = Package::find()->andWhere(['sampletype_id'=>$id])->asArray()->all();

            $list =  Package::find()
            ->innerJoin('tbl_sampletype', 'tbl_sampletype.sampletype_id=tbl_package.sampletype_id')
            ->Where(['tbl_package.sampletype_id'=>2])
            ->asArray()
            ->all();

            $selected  = null;
            if ($id != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i => $package) {
                    $out[] = ['id' => $package['id'], 'name' => $package['name']];
                    if ($i == 0) {
                        $selected = $package['id'];
                    }
                }
                
                echo Json::encode(['output' => $out, 'selected'=>$selected]);
                return;
            }
        }
        echo Json::encode(['output' => '', 'selected'=>'']);
    }

    public function actionGetpackage() {

        if ($_GET['packagelist_id']){
            if(isset($_GET['packagelist_id'])){
                $id = (int) $_GET['packagelist_id'];
                $modelpackagelist =  Package::findOne($id);
                $tet = $modelpackagelist->tests;
    
                    if($modelpackagelist){
                        $rate = number_format($modelpackagelist->rate,2);
                        $space = explode(',', $tet);
    
                        $tests = "";
                        $testsNameMethods = Testnamemethod::find()->where(['in', 'testname_method_id',$space])->all();
                            foreach($testsNameMethods as $method){
                                $tests = $tests." ".$method->testname->testName;
                            }
    
                    } else {
                        $rate = "";
                        $tests = "";
                        
                    }
                } else {
                    $rate = "Error getting rate";
                    $tests = "Error getting tests";
                }
                
                return Json::encode([
                    'rate'=>$rate,
                    'tests'=>$tests,
                    'ids'=>$tet,
                ]);
            }else{
                $x = 0;
            return Json::encode([
                'rate'=>$x,
                'tests'=>'None',
                'ids'=>$x,
            ]);
            }
        }

    protected function findRequest($requestId)
    {
        if (($model =Pstcrequest::findOne($requestId)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function listTestcategory()
    {
        $testcategory = ArrayHelper::map(
           Sampletype::find()
           ->leftJoin('tbl_lab_sampletype', 'tbl_lab_sampletype.sampletype_id=tbl_sampletype.sampletype_id')
           ->all(), 'sampletype_id', 
           function($testcategory, $defaultValue) {
               return $testcategory->type;
        });

        return $testcategory;
    }

}
