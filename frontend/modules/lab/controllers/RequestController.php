<?php

namespace frontend\modules\lab\controllers;

use Yii;
use frontend\modules\lab\components\eRequest;
use common\models\lab\exRequest;
use common\models\lab\Testreport;
use common\models\lab\exRequestreferral;
use common\models\lab\Referralrequest;
use common\models\lab\Request;
use common\models\lab\Discount;
use common\models\lab\Analysis;
//use common\models\lab\AnalysisSearch;
use common\models\lab\RequestSearch;
use common\models\lab\Requestcode;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use common\models\lab\Sample;
use yii\db\Query;
use common\models\lab\Customer;
use DateTime;
use common\models\system\Profile;
use common\components\Functions;
use common\components\ReferralComponent;
//use linslin\yii2\curl\Curl;
use kartik\mpdf\Pdf;
use frontend\modules\finance\components\epayment\ePayment;
use common\components\PstcComponent;
use common\models\finance\Op;
use common\models\system\Rstl;
use linslin\yii2\curl;
use codemix\excelexport\ExcelFile;
use common\models\system\User;
use frontend\modules\lab\components\Printing;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\data\ArrayDataProvider;
use common\models\lab\Lab;


use common\components\Notification;
use common\models\inventory\Equipmentservice; //use to check calibration schedule
use common\models\inventory\InventoryEntries; //use to check expiry of products

//use yii\helpers\Url;
/**
 * RequestController implements the CRUD actions for Request model.
 */
set_time_limit(180);
class RequestController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST']
                ],
            ],
        ];
    }

    /**
     * Lists all Request models.
     * @return mixed
     */
    public function actionIndex()
    { 

        $Func=new Functions();
        $Func->CheckRSTLProfile();
        
        $searchModel = new RequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=10;
        $GLOBALS['rstl_id']=Yii::$app->user->identity->profile->rstl_id;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    public function actionPrintRequest($id){
        $Printing=new Printing();
        $Printing->PrintRequest($id);
    }
    /**
     * Displays a single Request model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {

        $searchModel = new RequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize=10;
        $samplesQuery = Sample::find()->where(['request_id' => $id]);
        $sampleDataProvider = new ActiveDataProvider([
            'query' => $samplesQuery,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        $request_type = $this->findModel($id)->request_type_id;

        //$GLOBALS['rstl_id']=Yii::$app->user->identity->profile->rstl_id;
        
        $samples = Sample::find()->where(['request_id' => $id])->all();
        
        $sample_ids = '';
        foreach ($samples as $sample){
            $sample_ids .= $sample->sample_id.",";
        }
        $sample_ids = substr($sample_ids, 0, strlen($sample_ids)-1);
       
        if ($sample_ids){
            $ids = explode(",", $sample_ids);   
        }else{
            $ids = ['-1'];
        }

        if($request_type == 2) {
            $refcomponent = new ReferralComponent();
            $modelref_request = Referralrequest::find()->where(['request_id'=>$id])->one();
            $reqModel=$this->findRequestModel($id);

            $analysisQuery = Analysis::find()
                ->where(['IN', 'sample_id', $ids]);

            $analysisdataprovider = new ActiveDataProvider([
                'query' => $analysisQuery,
                'pagination' => false,
            ]);
            //if referral id is empty, look for the customer details in the local
            if($reqModel->referral_id){
                //gets the customer on the API //updated to new api //btc
                $customer = $refcomponent->getCustomerOne($reqModel->referral_id);                
            }else{
                $customer = Customer::findOne($reqModel->customer_id); //look in the local
            }

            //gets all the matching agency ??? hard to maintain, needs inovative idea here //btc 
            $agency = $refcomponent->listMatchAgency($id);



            //updated by egg 04/06/21
            $deposit = $refcomponent->getAttachment($reqModel->referral_id,1);
            $or = $refcomponent->getAttachment($reqModel->referral_id,2);

            //get the referred Agency details //btc
            //updated to new api //btc TODOOOOOOOOOOOOOO
            $referred_agency = $refcomponent->getReferredAgency($reqModel->referral_id,Yii::$app->user->identity->profile->rstl_id);

            $as_receiving = !empty($referred_agency['receiving_agency']) && $referred_agency > 0 ? $referred_agency['receiving_agency']['name'] : null;
            $as_testing = !empty($referred_agency['testing_agency']) && $referred_agency > 0 ? $referred_agency['testing_agency']['name'] : null;
            //updated to new api with false return temporarily //btc TODOOOOOOOOOOOOO
            $bid = json_decode($refcomponent->getBidderAgency($id,Yii::$app->user->identity->profile->rstl_id),true);
            //updated to new api with false return temporarily //btc TODOOOOOOOOOOOOO
            $countBidnotice = json_decode($refcomponent->countBidnotice($id,Yii::$app->user->identity->profile->rstl_id),true);
            
            $noSampleCode = Sample::find()->where("request_id =:requestId AND ISNULL(sample_code)",[':requestId'=>$id])->count();

            if($bid == 0){
                $countBid = 0;
                $bidders = [];
            } else {
                $countBid = $bid['count_bid'];
                if($countBid > 0){
                    $bidders = $bid['bidders'];
                } else {
                    $bidders = [];
                }
            }

            $bidderDataprovider = new ArrayDataProvider([
                'allModels' => $bidders,
                'pagination'=>false,
            ]);

            $agencyDataprovider = new ArrayDataProvider([
                'allModels' => $agency,
                'pagination'=>false,
            ]);

        } else {
            $analysisQuery = Analysis::find()
                ->where(['IN', 'sample_id', $ids]);

            $analysisdataprovider = new ActiveDataProvider([
                'query' => $analysisQuery,
                'pagination' =>false,
            ]);
        }
        
        if(\Yii::$app->user->can('view-all-rstl')){
            $model=exRequest::findOne($id);
        }else{
            $model=$this->findRequestModel($id);
        }

        $connection= Yii::$app->labdb;
        $analysisfee = $connection->createCommand('SELECT SUM(fee) as analysis_subtotal FROM tbl_analysis WHERE request_id =:requestId')
        ->bindValue(':requestId',$id)->queryOne();
        //$packagefee = $connection->createCommand('SELECT SUM(package_rate) as package_subtotal FROM tbl_sample WHERE request_id =:requestId')
        //->bindValue(':requestId',$id)->queryOne();
        $subtotal = $analysisfee['analysis_subtotal'];
        //$package_subtotal = $packagefee['package_subtotal'];
        //$subtotal = $analysis_subtotal + $package_subtotal;
        $rate = $model->discount;
        $discounted = $subtotal * ($rate/100);
        $total = $subtotal - $discounted;

        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;

        if($request_type == 2){
            $checkTesting = $this->checkTesting($id,$rstlId);
            $checkSamplecode = $this->checkSamplecode($id);
            return $this->render('viewreferral', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'sampleDataProvider' => $sampleDataProvider,
                'analysisdataprovider'=> $analysisdataprovider,
                'agencydataprovider'=> $agencyDataprovider,
                'bidderDataprovider'=> $bidderDataprovider,
                'modelref_request'=>$modelref_request,
                'subtotal' => $subtotal,
                'discounted' => $discounted,
                'total' => $total,
                'countSample' => count($samples),
                'checkTesting' => $checkTesting,
                'checkSamplecode' => $checkSamplecode,
                'customer' => $customer,
				'depositslip' => $deposit,
                'officialreceipt' => $or,
                'as_receiving' => $as_receiving,
                'as_testing' => $as_testing,
                'countBid' => $countBid,
                'countBidnotice' => $countBidnotice,
                'noSampleCode' => $noSampleCode,
                'multiagency'=>count($agency)
            ]);

        } else {
            return $this->render('view', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'sampleDataProvider' => $sampleDataProvider,
                'analysisdataprovider'=> $analysisdataprovider,
            ]);
        }
    }

    public function actionReportstatus($id)
    {
       $id = $_GET['id'];

       $request = Request::find()->where(['request_id' => $id])->one();
       $testreport = Testreport::find()->where(['request_id' => $id]);

       $req = Request::find()->where(['request_id' => $id]);

       $testreportdataprovider = new ActiveDataProvider([
           'query' => $req,
           'pagination' => [
               'pageSize' => false,
                   ],                 
       ]);

       if(Yii::$app->request->isAjax){
                return $this->renderAjax('_reportstatus', [
               'testreportdataprovider'=>$testreportdataprovider,
               'request'=>$request,
               ]);
       }
          
    }

    public function actionPaymentstatus($id)
    {
       $id = $_GET['id'];

       $request = Request::find()->where(['request_id' => $id])->one();
       $sample = Sample::find()->where(['request_id' => $id]);
       
       $req = Request::find()->where(['request_id' => $id]);
    
       $paymentstatusdataprovider = new ActiveDataProvider([
           'query' => $req,
           'pagination' => [
               'pageSize' => false,
                   ],                 
       ]);

       if(Yii::$app->request->isAjax){
                return $this->renderAjax('_paymentstatus', [
               'paymentstatusdataprovider'=>$paymentstatusdataprovider,
               'request'=>$request,
               ]);
       }
          
    }

    public function actionCustomerlist($q = null, $id = null) {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        if (!is_null($q)) {
            $query = new Query;
            $query->select('customer_id as id, customer_name AS text')
                    ->from('tbl_customer')
                    ->where(['like', 'customer_name', $q])
                    ->limit(20);
            $command = $query->createCommand();
            $command->db= \Yii::$app->labdb;
            $data = $command->queryAll();
            $out['results'] = array_values($data);
        } elseif ($id > 0) {
            $out['results'] = ['id' => $id, 'text' =>Customer::find()->where(['customer_id'=>$id])->customer_name];
        }
        return $out;
    }
    public function actionPdf(){
        $pdf=new \common\components\MyPDF();
        $Content="<button>Click me</button>";

        $pdf->renderPDF($Content,NULL,NULL,['orientation'=> Pdf::ORIENT_LANDSCAPE]);
    }

    public function actionPrintlabel(){

       if(isset($_GET['request_id'])){
        $id = $_GET['request_id'];
        $mpdf = new \Mpdf\Mpdf([
            'format' => [60,66], 
            'orientation' => 'L',
            'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf'
        ]);
        $request = Request::find()->where(['request_id' => $id]);
        $samplesquery = Sample::find()->where(['request_id' => $id])->all();
        $requestquery = Request::find()->where(['request_id' => $id])->one();
        foreach ($samplesquery as $sample) {
            $limitreceived_date = substr($requestquery['request_datetime'], 0,10);
            $mpdf->AddPage('','','','','',0,0,0,0);
            $samplecode = '<font size="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'.$sample['sample_code']."</b>&nbsp;&nbsp;".$sample['samplename'].
            '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font size="1"><b>Received:&nbsp;&nbsp;</b>'.$limitreceived_date.'&nbsp;&nbsp;<b>Due:&nbsp;&nbsp;</b>'.$requestquery['report_due'];
        
            $mpdf->WriteHTML("<barcode code=".$sample['sample_code']." type='C39' />");
            $mpdf->WriteHTML($samplecode);

            $text = '<font size="5">WI-003-F1';
            $text2 = '<font size="5"><b>Rev 03/03.01.18<b>';

            $i = 1;
            $analysisquery = Analysis::find()->where(['sample_id' => $sample['sample_id']])->all();
            $acount = Analysis::find()->where(['sample_id' => $sample['sample_id']])->count();
                   foreach ($analysisquery as $analysis){
                        $mpdf->WriteHTML("&nbsp;&nbsp;&nbsp;&nbsp;<font size='2'>".$analysis['testname']."</font>");

                        if ($i++ == $acount)
                        break;
                   }               
            }          
            $mpdf->Output();
       }
    }
    public function actionTestpayment(){
        //$json=Yii::$app->getRequest()->getBodyParams();
        //return $json;
            $Op= Op::findOne(14);
            $Op->payment_mode_id=5;//Online Payment
            $Op->save(false);
    }
    public function actionTest($id){
        //$ePayment=new ePayment();
        //$result=$ePayment->PostOnlinePayment($id);
        //Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        //return $result;
        $Func=new Functions();
        $Proc="spGetPaymentForOnline(:op_id)";
        $Connection= \Yii::$app->financedb;
        $param=[
            'op_id'=>$id
        ];
        $requests=$Func->ExecuteStoredProcedureRows($Proc, $param, $Connection);
        $Payment_details=[];
        foreach($requests as $request){
            $payment_detail=[
                'request_ref_num'=>$request['request_ref_num'],
                'rrn_date_time'=>$request['request_datetime'],
                'amount'=>$request['amount']
            ];
            array_push($Payment_details, $payment_detail);
        }
        //Query order of payment
        $Op= Op::findOne($id);
        $Rstl= Rstl::findOne($Op->rstl_id);
        $Customer= Customer::findOne($Op->customer_id);
        $TransactDetails=[
            'transaction_num'=>$Op->transactionnum,
            'customer_code'=>$Customer->customer_code,
            'collection_type'=>$Op->collectiontype->natureofcollection,
            'collection_code'=>'collection-code',
            'order_date'=>$Op->order_date,
            'agency_code'=>$Rstl->code,
            'total_amount'=>$Op->total_amount,
            'payment_details'=>$Payment_details
        ];
        $content = json_encode($TransactDetails);
        
        //return $TransactDetails;
        $curl = new curl\Curl();
        $EpaymentURI="https://yii2customer.onelab.ph/web/api/op";
        //$EpaymentURI="http://www.eulims.local/capi/op";
        $response = $curl->setRequestBody($content)
            ->setHeaders([
               'Content-Type' => 'application/json',
               'Content-Length' => strlen($content)
            ])->post($EpaymentURI);
        $result=json_decode($response);
        return $result->description;
    }
  
    public function actionSaverequestransaction(){
        $post= Yii::$app->request->post();

        $return="Failed";
        $request_id=(int) $post['request_id'];
        $lab_id=(int) $post['lab_id'];
        $rstl_id=(int) $post['rstl_id'];
        $year=(int) $post['year'];
		
		//Checking for reference number
		$chkref=Request::findOne($request_id);
		if ($chkref->request_ref_num){
			return $this->redirect(['view', 'id' => $request_id]); 
		}
        // Generate Reference Number
        $func=new Functions();
        /*$Proc="spGetNextGeneratedRequestCode(:RSTLID,:LabID)";
        $Params=[
            ':RSTLID'=>$rstl_id,
            ':LabID'=>$lab_id
        ]; */
        $Connection= Yii::$app->labdb;
        $Transaction =$Connection->beginTransaction();
       // $Row=$func->ExecuteStoredProcedureOne($Proc, $Params, $Connection);
        ////Reference Number Removing SP 10/282020 EGG
		$Req= Request::find()->where(['request_id'=>$request_id])->one($Connection);
		$requestdate=$Req->request_datetime;
		//Updated to be able to encode backlogs of request	
		$reqyear=date('Y',strtotime($requestdate));
		 
		$lastnum=(new Query)
            ->select('MAX(number) AS lastnumber')
            ->from('eulims_lab.tbl_requestcode')
			->where(['lab_id' => $lab_id, 'year'=> $reqyear])
            ->one();	
			
			
		$monthyear=date('mY',strtotime($requestdate));
		$rstl= Rstl::find()->where(['rstl_id'=>$rstl_id])->one();
		$code=$rstl->code;
		
		$lab= Lab::find()->where(['lab_id'=>$lab_id])->one();
		$labcode=$lab->labcode;
		
		$str_trans_num=0;
          if($lastnum != ''){
            $str_trans_num=$lastnum["lastnumber"] + 1;
            $str_trans_num=str_pad($str_trans_num, 4, "0", STR_PAD_LEFT);
              
          }
          else{
              $str_trans_num='0001';
          }
		  
      
		///////////
		$ReferenceNumber=$code."-".$monthyear."-".$labcode."-".$str_trans_num;
        $RequestIncrement=$str_trans_num;
		
        //Update the tbl_requestcode
        $Requestcode= Requestcode::find()->where([
            'rstl_id'=>$rstl_id,
            'lab_id'=>$lab_id,
            'year'=>$year
        ])->one($Connection);
        
        if(!$Requestcode){
            $Requestcode=new Requestcode();
        }
        $Requestcode->request_ref_num=$ReferenceNumber;
        $Requestcode->rstl_id=$rstl_id;
        $Requestcode->lab_id=$lab_id;
        $Requestcode->number=$RequestIncrement;
        $Requestcode->year=$year;
        $Requestcode->cancelled=0;
        $Requestcode->save(false);


        //update ref in pstc table
        if($Req->pstc_id != 0){ // IF PSTC REQUEST
            $testarray = [
                'id' => $Req->pstc_id,
                'reference' => $ReferenceNumber,
                'due' => $Req->report_due
            ];

            $function = new PstcComponent();
            $data = json_decode($function->getUpdateref($testarray),true);
        }

        //Update tbl_request table
        $Request= Request::find()->where(['request_id'=>$request_id])->one($Connection);
        $Request->request_ref_num=$ReferenceNumber;
       
        $discountquery = Discount::find()->where(['discount_id' => $Request->discount_id])->one();

        $rate =  $discountquery->rate;
        
        $sql = "SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id=$request_id";
        $command = $Connection->createCommand($sql);
        $row = $command->queryOne();
        $subtotal = $row['subtotal'];
        $total = $subtotal - ($subtotal * ($rate/100));
        
        $Request->total=$total;

        if($Request->save(false)){
            $Func=new Functions();
            $response=$Func->GenerateSampleCode($request_id);
            if($response){

                //query customer using $Request->customer_id
                // $customer = Customer::find()->where(['customer_id'=>$Request->customer_id])->one();
                // $apiUrl=$GLOBALS['newapi_url'].'restcustomer/mailcode?email='.$customer->email;
                // $curl = new curl\Curl();
                // $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
                // $curl->setOption(CURLOPT_TIMEOUT, 180);
                // $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
                // $list = Json::decode($curl->get($apiUrl));


                $return="Success";
                Yii::$app->session->setFlash('success', 'Request Reference # and Sample Code Successfully Generated!');
                $Transaction->commit();
            }else{
                $Transaction->rollback();
                Yii::$app->session->setFlash('danger', 'Request Reference # and Sample Code Failed to Generate!');
                $return="Failed";
            }
        }else{
            Yii::$app->session->setFlash('danger', 'Request Reference # and Sample Code Failed to Generate!');
            $Transaction->rollback();
            $return="Failed";
        }
        
		//to here
        return $return;
    }
    /**
     * Creates a new Request model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new eRequest();
        $Func=new Functions();
        $Func->CheckRSTLProfile();
        $GLOBALS['rstl_id']=Yii::$app->user->identity->profile->rstl_id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
           // Yii::$app->session->setFlash('success', 'Request Successfully Created!');
            return $this->redirect(['view', 'id' => $model->request_id]); ///lab/request/view?id=1
        } else {
            $date = new DateTime();
            $date2 = new DateTime();
            $profile= Profile::find()->where(['user_id'=> Yii::$app->user->id])->one();
            date_add($date2,date_interval_create_from_date_string("1 day"));
            $model->request_datetime=date("Y-m-d H:i:s");
            //$model->report_due=date_format($date2,"Y-m-d");
            $model->created_at=date('U');
            $model->rstl_id= Yii::$app->user->identity->profile->rstl_id;//$GLOBALS['rstl_id'];
            $model->payment_type_id=1;
            $model->modeofrelease_ids='1';
            $model->discount_id=0;
            $model->discount='0.00';
            $model->total=0.00;
            $model->posted=0;
            $model->status_id=1;
           // $model->contact_num="123456789";
            $model->request_type_id=1;
            $model->modeofreleaseids='1';
            $model->payment_status_id=1;
           // $model->request_type_id=1;
            $model->request_date=date("Y-m-d");
            if($profile){
                $model->receivedBy=$profile->firstname.' '. strtoupper(substr($profile->middleinitial,0,1)).'. '.$profile->lastname;
            }else{
                $model->receivedBy="";
            }
            if(\Yii::$app->request->isAjax){
                return $this->renderAjax('create', [
                    'model' => $model,
                ]);
            }else{
                
                return $this->renderAjax('create', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Updates an existing Request model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        //$model = $this->findModel($id);
        $model= eRequest::findOne($id);
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            //Yii::$app->session->setFlash('success', 'Request Successfully Updated!');
            return $this->redirect(['view', 'id' => $model->request_id]);
        } else {
            if($model->request_ref_num){
                $model->request_ref_num=NULL;
            }
            if(\Yii::$app->request->isAjax){
                return $this->renderAjax('update', [
                    'model' => $model,
                ]);
            }else{
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Deletes an existing Request model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $Request=$this->findModel($id);
        if($Request){//Success
            $Request->status_id=2;
            $ret=$Request->save();
        }else{
            $ret=false;
        }
        return $ret;
    }

    /**
     * Finds the Request model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Request the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Request::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
     protected function findRequestModel($id)
    {
        $rstl_id=Yii::$app->user->identity->profile ? Yii::$app->user->identity->profile->rstl_id : -1;
        $model=exRequest::find()->where(['request_id'=>$id,'rstl_id'=>$rstl_id])->one();
        if ($model!== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The Request is either not existing or you have no permission to view it.');
        }
    }
    //bergel cutara
    //contacted by function to return result to be displayed in select2
    public function actionRequestlist($q = null, $id = null) {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        if (!is_null($q)) {
            $query = new Query;
            $query->select('request_id as id, request_ref_num AS text')
                    ->from('tbl_request')
                    ->where(['lab_id'=>\Yii::$app->user->identity->profile->lab_id])
                    ->andWhere(['like', 'request_ref_num', '%'.$q,false])
                    ->orderBy(['request_id'=>SORT_DESC])
                    ->limit(20);
            $command = $query->createCommand();
            $command->db= \Yii::$app->labdb;
            $data = $command->queryAll();
            $out['results'] = array_values($data);
        } elseif ($id > 0) {
            $out['results'] = ['id' => $id, 'text' =>Request::find()->where(['request_id'=>$id])->request_ref_num];
        }
        return $out;
    }

    /**
     * Creates a new Request model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreatereferral()
    {
        $model = new exRequestreferral();
        $Func=new Functions();
        $refcomponent = new ReferralComponent();
        $Func->CheckRSTLProfile();
        $connection= Yii::$app->labdb;
        $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        $GLOBALS['rstl_id']=Yii::$app->user->identity->profile->rstl_id;
        
        
        
        if ($model->load(Yii::$app->request->post())) {
            $transaction = $connection->beginTransaction();
            $modelReferralrequest = new Referralrequest();
            $model->request_datetime="0000-00-00 00:00:00";
            if ($model->save(false)){
                $modelReferralrequest->request_id = $model->request_id;
                $modelReferralrequest->sample_received_date = date('Y-m-d h:i:s',strtotime($model->sample_received_date));
                $modelReferralrequest->receiving_agency_id = Yii::$app->user->identity->profile->rstl_id;
                //$modelReferralrequest->testing_agency_id = null;
                $modelReferralrequest->referral_type_id = 1;
                if ($modelReferralrequest->save()){
                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $model->request_id]); ///lab/request/view?id=1
                } else {
                    $transaction->rollBack();
                    print_r($modelReferralrequest->getErrors());
                    return false;
                }
 
            } else {
                $transaction->rollBack();
                print_r($model->getErrors());
                //return false;
            }
        } else {
            $date = new DateTime();
            $date2 = new DateTime();
            $profile= Profile::find()->where(['user_id'=> Yii::$app->user->id])->one();
            date_add($date2,date_interval_create_from_date_string("1 day"));
            //$model->request_datetime=date("Y-m-d h:i:s");
            $model->request_datetime="0000-00-00 00:00:00";
            $model->report_due=date_format($date2,"Y-m-d");
            $model->created_at=date('U');
            $model->rstl_id=Yii::$app->user->identity->profile->rstl_id;//$GLOBALS['rstl_id'];
            $model->payment_type_id=1;
            $model->modeofrelease_ids='1';
            $model->discount_id=0;
            $model->discount='0.00';
            $model->total=0.00;
            $model->posted=0;
            $model->status_id=1;
            $model->request_type_id=2;
            $model->modeofreleaseids='1';
            $model->payment_status_id=1;
            $model->request_date=date("Y-m-d");
            if($profile){
                $model->receivedBy=$profile->firstname.' '. strtoupper(substr($profile->middleinitial,0,1)).'. '.$profile->lastname;
            }else{
                $model->receivedBy="";
            }

            //gets the listoflabs //btc
            $labreferral = ArrayHelper::map($refcomponent->listLabreferral(), 'lab_id', 'labname');
            //gets the list of discounts //btc
            $discountreferral = ArrayHelper::map($refcomponent->listDiscountreferral(), 'discount_id', 'type');
            //gets all the list of purposes //btc
            $purposereferral = ArrayHelper::map($refcomponent->listPurposereferral(), 'purpose_id', 'name');
            //gets all the list of modeofrelease //btc 
            $modereleasereferral = ArrayHelper::map($refcomponent->listModereleasereferral(), 'modeofrelease_id', 'mode');

            if(\Yii::$app->request->isAjax){
                return $this->renderAjax('createReferral', [
                    'model' => $model,
                    'labreferral' => $labreferral,
                    'discountreferral' => $discountreferral,
                    'purposereferral' => $purposereferral,
                    'modereleasereferral' => $modereleasereferral,
                    'api_url'=>$refcomponent->getSource()
                ]);
            }else{
                return $this->render('createReferral', [
                    'model' => $model,
                    'labreferral' => $labreferral,
                    'discountreferral' => $discountreferral,
                    'purposereferral' => $purposereferral,
                    'modereleasereferral' => $modereleasereferral,
                    'api_url'=>$refcomponent->getSource()
                ]);
            }
        }
    }

    /**
     * Updates an existing Request model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdatereferral($id)
    {
        //$model = $this->findModel($id);
        $model= exRequestreferral::findOne($id);
        $modelReferralrequest = Referralrequest::find()->where('request_id = :requestId', [':requestId' => $id])->one();
        $connection= Yii::$app->labdb;
        $refcomponent = new ReferralComponent();
        
        $labreferral = ArrayHelper::map($refcomponent->listLabreferral(), 'lab_id', 'labname');
        $discountreferral = ArrayHelper::map($refcomponent->listDiscountreferral(), 'discount_id', 'type');
        $purposereferral = ArrayHelper::map($refcomponent->listPurposereferral(), 'purpose_id', 'name');
        $modereleasereferral = ArrayHelper::map($refcomponent->listModereleasereferral(), 'modeofrelease_id', 'mode');

        $sampleCount = Sample::find()->where('request_id =:requestId',[':requestId'=>$id])->count();
        $analysisCount = Analysis::find()->where('request_id =:requestId',[':requestId'=>$id])->count();

        $oldLabId = $model->lab_id;
        $notified = !empty($modelReferralrequest->notified) ? $modelReferralrequest->notified : 0;
        $samplefail = null;
        $analysisfail = null;

        if ($model->load(Yii::$app->request->post())) {
            $transaction = $connection->beginTransaction();
            //check if lab is updated
            //if not equal to old lab_id, sample and analysis will be deleted
            //reason: sample type or test parameter may not be available for the lab
            if($oldLabId != $_POST['exRequestreferral']['lab_id'] && ($sampleCount > 0 || $analysisCount > 0))
            {
                $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();

                if($sampleCount > 0)
                {
                    $sampleDelete = Sample::deleteAll('request_id = :requestId',[':requestId'=>$id]);
                    if(!$sampleDelete)
                    {
                        $samplefail = 1;
                    } else {
                        if($analysisCount > 0){
                            $analysisDelete = Analysis::deleteAll('request_id = :requestId',[':requestId'=>$id]);            
                            if(!$analysisDelete)
                            {
                                $analysisfail = 1;
                            }
                        }
                    }
                }
            }

            if($samplefail == 1 || $analysisfail == 1){
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Error deleting sample/analysis!');
                return $this->redirect(['view', 'id' => $model->request_id]);
            } else {
                if($analysisCount > 0){
                    $discount = $refcomponent->getDiscountOne($model->discount_id);
                    $rate = $discount->rate;
                    $fee = $connection->createCommand('SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id =:requestId')
                    ->bindValue(':requestId',$id)->queryOne();
                    $subtotal = $fee['subtotal'];
                    $total = $subtotal - ($subtotal * ($rate/100));

                    $model->total = $total;
                }
                if ($model->save()){
                    $modelReferralrequest->request_id = $model->request_id;
                    $modelReferralrequest->sample_received_date = date('Y-m-d h:i:s',strtotime($model->sample_received_date));
                    $modelReferralrequest->receiving_agency_id = Yii::$app->user->identity->profile->rstl_id;
                    //$modelReferralrequest->testing_agency_id = null;
                    //$modelReferralrequest->referral_type_id = 1;
                    if ($modelReferralrequest->save()){
                        $transaction->commit();
                    } else {
                        $transaction->rollBack();
                        $modelReferralrequest->getErrors();
                        return false;
                    }
                    Yii::$app->session->setFlash('success', 'Referral Request Successfully Updated!');
                    return $this->redirect(['view', 'id' => $model->request_id]);
                } else {
                    $transaction->rollBack();
                    $model->getErrors();
                    return false;
                }
            }
        } else {
            $model->sample_received_date = !empty($modelReferralrequest->sample_received_date) ? $modelReferralrequest->sample_received_date : null;
            if($model->request_ref_num){
                $model->request_ref_num=NULL;
            }
            if(\Yii::$app->request->isAjax){
                return $this->renderAjax('updateReferral', [
                    'model' => $model,
                    'labreferral' => $labreferral,
                    'discountreferral' => $discountreferral,
                    'purposereferral' => $purposereferral,
                    'modereleasereferral' => $modereleasereferral,
                    'notified'=>$notified,
                    'api_url'=>$refcomponent->getSource()
                ]);
            }else{
                return $this->renderAjax('updateReferral', [
                    'model' => $model,
                    'labreferral' => $labreferral,
                    'discountreferral' => $discountreferral,
                    'purposereferral' => $purposereferral,
                    'modereleasereferral' => $modereleasereferral,
                    'notified'=>$notified,
                    'api_url'=>$refcomponent->getSource()
                ]);
            }
        }
    }

    //get referral customer list
    public function actionReferralcustomerlist($query = null, $id = null)
    {
            $refcomponent = new ReferralComponent();
            $apiUrl=$refcomponent->getSource().'/searchname?keyword='.$query;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $show = $curl->get($apiUrl);
            return $apiUrl;
    }

    //check if received sample as a tesing lab
    protected function checkTesting($requestId,$rstlId)
    {
        $model = Referralrequest::find()->where('request_id =:requestId AND testing_agency_id =:testingAgency AND referral_type_id =:referralType',[':requestId'=>$requestId,':testingAgency'=>$rstlId,':referralType'=>2])->count();
        if($model > 0){
            return 1;
        } else {
            return 0;
        }
    }
    //check if sample code is not null for referral request
    protected function checkSamplecode($requestId)
    {
        $request1 = exRequestreferral::find()->where('request_id =:requestId AND request_type_id =:requestType AND referral_id > 0',[':requestId'=>$requestId,':requestType'=>2])->count();
        $request2 = exRequestreferral::find()->where('request_id =:requestId AND request_type_id =:requestType',[':requestId'=>$requestId,':requestType'=>2])->count();
        $samples1 = Sample::find()->where('request_id =:requestId AND referral_sample_id > 0',[':requestId'=>$requestId])->count();
        $samples2 = Sample::find()->where('request_id =:requestId',[':requestId'=>$requestId])->count();
        $analyses1 = Analysis::find()->where('request_id =:requestId AND referral_analysis_id > 0',[':requestId'=>$requestId])->count();
        $analyses2 = Analysis::find()->where('request_id =:requestId',[':requestId'=>$requestId])->count();

        if($request1 == $request2 && $samples1 == $samples2 && $analyses1 == $analyses2){
            return 1;
        } else {
            return 0;
        }
    }
	//egg notify customer if test report is available
	 public function actionNotifysms($id,$reqid,$refnum)
    {
       $id = $_GET['id'];
	   $reqid = $_GET['reqid'];
	   $refnum = $_GET['refnum'];
       
       $customer = Customer::find()->where(['customer_id' => $id])->one();
       $contactnum = $customer->tel;
	   $email= $customer->email;
	    $notif= new Notification();
		if($email){
			$notif->sendEmail($email,$refnum);	
		}
		$rstlid=Yii::$app->user->identity->profile->rstl_id;
		$title="Test Report";
		$mes= "Good Day dear customer! Your test report for reference#: ".$refnum." is ready and available for pick-up.";
		$res=$notif->sendSMS("", $rstlid, $contactnum, $title, $mes, "eULIMS", $this->module->id,$this->action->id);
		$decode=Json::decode($res);
		//Yii::$app->session->setFlash('success',$decode["data"] );
		Yii::$app->session->setFlash('success',"Successfully notified the customer");
		return $this->redirect(['index']); 
    }
	//egg report due
	 public function actionNotifyreportdue()
    {
	  // $todaydate=date("Y-m-d");
	   $tomorrow = date("Y-m-d", strtotime("+1 day"));
	  // echo $tomorrow;
       $request = Request::find()->where(['report_due' => $tomorrow])->all();
	   $rstlid=Yii::$app->user->identity->profile->rstl_id;
	    $countdue=0;
		foreach ($request as $res){
			$refnum= $res->request_ref_num;
			$users = Profile::find()->where(['designation' => 'Lab Analyst'])->all();
			$title="Request Report Due";
			foreach ($users as $analyst){
				 $contactnum = $analyst->contact_numbers;	
				 if($contactnum){
					 $notif= new Notification();
					 $mes= "Hello dear analyst! There is a request due tomorrow with reference#: ".$refnum;
					 $res=$notif->sendSMS("", $rstlid, $contactnum, $title, $mes, "eULIMS", $this->module->id,$this->action->id);
					 $decode=Json::decode($res); 
				 }
			}
           $countdue++;
		}
		
		$this->Notifysched();
		$this->Notifyexpiry();
		Yii::$app->session->setFlash('success',$countdue ." request(s) due tomorrow!");
		return $this->redirect(['index']); 
    }
	//egg Calibration schedule
	 public function Notifysched()
    {
	   $rstlid=Yii::$app->user->identity->profile->rstl_id;
	   $tomorrow = date("Y-m-d", strtotime("+1 day"));
       $sched = Equipmentservice::find()->where(['startdate' => $tomorrow])->all();
	  
		$countdue=0;
		foreach ($sched as $res){
			$users = Profile::find()->where(['designation' => 'Lab Analyst'])->andWhere(['lab_id' => '3'])->all(); //Metro
			$title="Calibration Schedule";
			$item=$res->inventoryTransactions->product_name;
			
			foreach ($users as $analyst){
				 $contactnum = $analyst->contact_numbers;	
				 if($contactnum){
					 $notif= new Notification();
					 $mes= "Hello dear analyst! There is a scheduled calibration for tomorrow with Product Name: ".$item;
					 $res=$notif->sendSMS("", $rstlid, $contactnum, $title, $mes, "eULIMS", "Inventory","Calibration Schedule");
					 $decode=Json::decode($res); 
				 }
			}
		}
        
		return; 
    }
	
	//egg Near product Expiry
	 public function Notifyexpiry()
    {
	   $tomorrow = date("Y-m-d", strtotime("+3 day"));
       $sched = InventoryEntries::find()->where(['expiration_date' => $tomorrow])->all();
	  
		$countdue=0;
		foreach ($sched as $res){
			$users = Profile::find()->where(['designation' => 'Lab Analyst'])->andWhere(['lab_id' => '1'])->all(); //Metro
			$title="Near Product Expiry";
			$item=$res->product->product_name;
			
			foreach ($users as $analyst){
				 $contactnum = $analyst->contact_numbers;	
				 if($contactnum){
					 $notif= new Notification();
					 $mes= "Hello dear analyst! There is a product of near expiry date in 3days with Product Name: ".$item;
					 $res=$notif->sendSMS("", $rstlid, $contactnum, $title, $mes, "eULIMS", "Inventory","Calibration Schedule");
					 $decode=Json::decode($res); 
				 }
			}
		}
        
		return; 
    }
}