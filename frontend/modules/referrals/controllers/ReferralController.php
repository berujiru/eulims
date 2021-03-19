<?php

namespace frontend\modules\referrals\controllers;

use Yii;
use common\models\referral\Referral;
use common\models\referral\ReferralSearch;
use common\models\lab\Referralrequest;
use common\models\lab\Requestcode;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use linslin\yii2\curl;
use common\models\lab\exRequestreferral;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use common\models\lab\Request;
use common\models\lab\Analysis;
use common\models\lab\Analysisextend;
use common\models\lab\Sample;
use common\models\lab\Discount;
use common\models\lab\Samplecode;
use common\components\ReferralComponent;
use common\components\ReferralFunctions;
use common\components\Functions;
use yii\data\ArrayDataProvider;
use common\models\referral\Notification;
use common\models\referral\Statuslogs;
use common\models\referral\Referraltrackreceiving;
use common\models\referral\Referraltracktesting;
use frontend\modules\referrals\template\Printreferral;
use common\models\lab\Lab;
use common\models\system\Rstl;
use yii\db\Query;

/**
 * ReferralController implements the CRUD actions for Referral model.
 */
class ReferralController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Referral models.
     * @return mixed
     */
    public function actionIndex() 
    {
        //$searchModel = new ReferralSearch();
        //$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $refcomponent = new ReferralComponent();

        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;

        if($rstlId > 0) {
            $referrals = json_decode($refcomponent->getIncomingReferral($rstlId),true);
        } else {
            $referrals = 0;
            return $this->redirect(['/site/login']);
        }

        if($referrals == 0 || $referrals == 'false'){
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
            //'searchModel' => $searchModel,
            'dataProvider' => $referralDataprovider,
        ]);
    }

    public function actionSentreferral() 
    {
        //$searchModel = new ReferralSearch();
        //$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $refcomponent = new ReferralComponent();

        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;

        if($rstlId > 0) {
            $referrals = json_decode($refcomponent->getSentReferral($rstlId),true);
        } else {
            $referrals = 0;
            return $this->redirect(['/site/login']);
        }

        if($referrals == 0 || $referrals == 'false'){
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
            //'searchModel' => $searchModel,
            'dataProvider' => $referralDataprovider,
        ]);
    }


    /**
     * Displays a single Referral model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        // $refcomponent = new ReferralComponent();
        // echo $refcomponent->getSource(); exit;

        $referralId = (int) $id;
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
        $noticeId = (int) Yii::$app->request->get('notice_id');

        if($rstlId > 0 && $noticeId > 0)
        {
            $refcomponent = new ReferralComponent();
            $referralDetails = $refcomponent->getReferraldetails($referralId,$rstlId);
            $noticeDetails = $refcomponent->getNotificationOne($noticeId,$rstlId);

            if($referralDetails && $noticeDetails)
            {
                $model = new Request(); //for declaration required in Detailview

                $request = $referralDetails['request_data'];
                $samples = $referralDetails['sample_data'];
                $analyses = $referralDetails['analysis_data'];
                $customer = $referralDetails['customer_data'];
                //set third parameter to 1 for attachment type deposit slip //this one returns false temporarily
                $deposit = $refcomponent->getAttachment($referralId,Yii::$app->user->identity->profile->rstl_id,1);
                //set third parameter to 2 for attachment type or //this one returns false temporarily
                $or = $refcomponent->getAttachment($referralId,Yii::$app->user->identity->profile->rstl_id,2);
                $referred_agency = $refcomponent->getReferredAgency($referralId,Yii::$app->user->identity->profile->rstl_id);

                $receiving_agency = !empty($referred_agency['receiving_agency']) && $referred_agency > 0 ? $referred_agency['receiving_agency']['name'] : null;
                $testing_agency = !empty($referred_agency['testing_agency']) && $referred_agency > 0 ? $referred_agency['testing_agency']['name'] : null;

                $sampleDataProvider = new ArrayDataProvider([
                    'allModels' => $samples,
                    'pagination'=> [
                        'pageSize' => 10,
                    ],
                ]);

                $analysisDataprovider = new ArrayDataProvider([
                    'allModels' => $analyses,
                    //'pagination'=>false,
                    'pagination'=> [
                        'pageSize' => 10,
                    ],

                ]);

                $analysis_fees = implode(',', array_map(function ($data) {
                    return $data['analysis_fee'];
                }, $analyses));

                $subtotal = array_sum(explode(",",$analysis_fees));
                $rate = $request['discount_rate'];
                $discounted = $subtotal * ($rate/100);
                $total = $subtotal - $discounted;

                return $this->render('view', [
                    'model' => $model,
                    'request' => $request,
                    'customer' => $customer,
                    'sampleDataProvider' => $sampleDataProvider,
                    'analysisdataprovider'=> $analysisDataprovider,
                    'subtotal' => $subtotal,
                    'discounted' => $discounted,
                    'total' => $total,
                    'countSample' => count($samples),
                    'notification' => $noticeDetails,
                    'receiving_agency' => $receiving_agency,
                    'testing_agency' => $testing_agency,
                    'depositslip' => $deposit,
                    'officialreceipt' => $or,
                ]);
            } else {
                Yii::$app->session->setFlash('error', "Your agency doesn't appear notified!");
                return $this->redirect(['/referrals/notification']);
            }
        } else {
            Yii::$app->session->setFlash('error', "Invalid request!");
            return $this->redirect(['/referrals/notification']);
        }
    }

    public function actionViewreferral($id)
    {
        
        $referralId = (int) $id;
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;

        if($rstlId > 0 && $referralId > 0)
        {
            $refcomponent = new ReferralComponent();
            $referralDetails = json_decode($refcomponent->getReferraldetails($referralId,$rstlId),true);
            
            if($referralDetails != 0)
            {
                $model = new Request(); //for declaration required in Detailview
               // $modelref = $this->findModelRef($id);       
                $request = $referralDetails['request_data'];
                $samples = $referralDetails['sample_data'];
                $analyses = $referralDetails['analysis_data'];
                $customer = $referralDetails['customer_data'];
                $notification=$referralDetails['notification_data'];
                $statuslogs= json_decode($refcomponent->getStatuslogs($referralId));
               
                //set third parameter to 1 for attachment type deposit slip
                $deposit = json_decode($refcomponent->getAttachment($referralId,Yii::$app->user->identity->profile->rstl_id,1),true);
                //set third parameter to 2 for attachment type or
                $or = json_decode($refcomponent->getAttachment($referralId,Yii::$app->user->identity->profile->rstl_id,2),true);
                $referred_agency = json_decode($refcomponent->getReferredAgency($referralId,Yii::$app->user->identity->profile->rstl_id),true);

                $receiving_agency = !empty($referred_agency['receiving_agency']) && $referred_agency > 0 ? $referred_agency['receiving_agency']['name'] : null;
                $testing_agency = !empty($referred_agency['testing_agency']) && $referred_agency > 0 ? $referred_agency['testing_agency']['name'] : null;

                $testresult = json_decode($refcomponent->getAttachment($id,Yii::$app->user->identity->profile->rstl_id,3),true);
              
                if($testresult <> 0){
                    $testresultDataProvider = new ArrayDataProvider([
                        'allModels' => $testresult,
                        'pagination'=> [
                            'pageSize' => 10,
                        ],
                    ]); 
                }else{
                    $testresultDataProvider = new ArrayDataProvider([]);
                }
                $notificationDataProvider = new ArrayDataProvider([
                    'allModels' => $notification,
                    'pagination'=> [
                        'pageSize' => 10,
                    ],
                ]);
                
                $sampleDataProvider = new ArrayDataProvider([
                    'allModels' => $samples,
                    'pagination'=> [
                        'pageSize' => 10,
                    ],
                ]);

                $analysisDataprovider = new ArrayDataProvider([
                    'allModels' => $analyses,
                    //'pagination'=>false,
                    'pagination'=> [
                        'pageSize' => 10,
                    ],

                ]);

                $analysis_fees = implode(',', array_map(function ($data) {
                    return $data['analysis_fee'];
                }, $analyses));

                $subtotal = array_sum(explode(",",$analysis_fees));
                $rate = $request['discount_rate'];
                $discounted = $subtotal * ($rate/100);
                $total = $subtotal - $discounted;

                //print_r($testresult);exit;
                return $this->render('viewreferral', [
                    'model' => $model,
                   // 'modelref' => $modelref,
                    'request' => $request,
                    'customer' => $customer,
                    'sampleDataProvider' => $sampleDataProvider,
                    'analysisdataprovider'=> $analysisDataprovider,
                    'subtotal' => $subtotal,
                    'discounted' => $discounted,
                    'total' => $total,
                    'countSample' => count($samples),
                    'receiving_agency' => $receiving_agency,
                    'testing_agency' => $testing_agency,
                    'depositslip' => $deposit,
                    'officialreceipt' => $or,
                    'testresult' => $testresultDataProvider,
                    'notificationDataProvider' => $notificationDataProvider,
                    'logs'=>$statuslogs,
                    'modelRefTracktesting'=>$this->findModeltestingtrack($id),
                    'modelRefTrackreceiving'=>$this->findModelreceivedtrack($id)
               
                ]);
            } else {
                Yii::$app->session->setFlash('error', "Invalid referral!");
                return $this->redirect(['/referrals/referral']);
            }
        } else {
            Yii::$app->session->setFlash('error', "Invalid referral!");
            return $this->redirect(['/referrals/referral']);
        }
    }

    //bergel cutara
    //temporary viewreferral pointing to old referral system
    public function actionViewoldreferral($id)
    {
        $referralId = (int) $id;
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;

        if($rstlId > 0 && $referralId > 0)
        {
            $refcomponent = new ReferralComponent();
              $referralDetails = json_decode($refcomponent->getReferraldetails($referralId,$rstlId),true);
            if($referralDetails != 0)
            {
                $model = new Request(); //for declaration required in Detailview
               // $modelref = $this->findModelRef($id);       
                $request = $referralDetails;
                $samples = $referralDetails['samples'];
                $analyses = $referralDetails['analyses'];
                $customer = $referralDetails['customer'];
                // $notification=$referralDetails['notification_data'];
                $statuslogs= json_decode($refcomponent->getStatuslogs($referralId));
               
                //set third parameter to 1 for attachment type deposit slip
                $deposit = json_decode($refcomponent->getAttachment($referralId,Yii::$app->user->identity->profile->rstl_id,1),true);
                //set third parameter to 2 for attachment type or
                $or = json_decode($refcomponent->getAttachment($referralId,Yii::$app->user->identity->profile->rstl_id,2),true);
                $referred_agency = json_decode($refcomponent->getReferredAgency($referralId,Yii::$app->user->identity->profile->rstl_id),true);

                $receiving_agency = !empty($referred_agency['receiving_agency']) && $referred_agency > 0 ? $referred_agency['receiving_agency']['name'] : null;
                $testing_agency = !empty($referred_agency['testing_agency']) && $referred_agency > 0 ? $referred_agency['testing_agency']['name'] : null;

                $testresult = json_decode($refcomponent->getAttachment($id,Yii::$app->user->identity->profile->rstl_id,3),true);
              
                if($testresult <> 0){
                    $testresultDataProvider = new ArrayDataProvider([
                        'allModels' => $testresult,
                        'pagination'=> [
                            'pageSize' => 10,
                        ],
                    ]); 
                }else{
                    $testresultDataProvider = new ArrayDataProvider([]);
                }
                // $notificationDataProvider = new ArrayDataProvider([
                //     'allModels' => $notification,
                //     'pagination'=> [
                //         'pageSize' => 10,
                //     ],
                // ]);
                
                $sampleDataProvider = new ArrayDataProvider([
                    'allModels' => $samples,
                    'pagination'=> [
                        'pageSize' => 10,
                    ],
                ]);

                $analysisDataprovider = new ArrayDataProvider([
                    'allModels' => $analyses,
                    //'pagination'=>false,
                    'pagination'=> [
                        'pageSize' => 10,
                    ],

                ]);

                $analysis_fees = implode(',', array_map(function ($data) {
                    return $data['fee'];
                }, $analyses));

                $subtotal = array_sum(explode(",",$analysis_fees));
                $rate = $request['discount']['rate'];
                $discounted = $subtotal * ($rate/100);
                $total = $subtotal - $discounted;

                //print_r($testresult);exit;
                return $this->render('viewoldreferral', [
                    'model' => $model,
                   // 'modelref' => $modelref,
                    'request' => $request,
                    'customer' => $customer,
                    'sampleDataProvider' => $sampleDataProvider,
                    'analysisdataprovider'=> $analysisDataprovider,
                    'subtotal' => $subtotal,
                    'discounted' => $discounted,
                    'total' => $total,
                    'countSample' => count($samples),
                    'receiving_agency' => $receiving_agency,
                    'testing_agency' => $testing_agency,
                    'depositslip' => $deposit,
                    'officialreceipt' => $or,
                    'testresult' => $testresultDataProvider,
                    // 'notificationDataProvider' => $notificationDataProvider,
                    'logs'=>$statuslogs,
                    'modelRefTracktesting'=>$this->findModeltestingtrack($id),
                    'modelRefTrackreceiving'=>$this->findModelreceivedtrack($id)
               
                ]);
            } else {
                Yii::$app->session->setFlash('error', "Invalid referral!haha");
                return $this->redirect(['/referrals/referral']);
            }
        } else {
            Yii::$app->session->setFlash('error', "No RSTL ID or REFERRAL ID");
            return $this->redirect(['/referrals/referral']);
        }
    }

    //we will call this if need to generatesamplecode to the old referral
    public function actionGeneratesamplecodereferral(){
        if(isset($_POST['request_id'])){
            $request_id = $_POST['request_id'];
            $refcomponent = new ReferralComponent();

            $response =  $refcomponent->generatesamplecode($request_id);
            if($response){
                $return="Success";
                Yii::$app->session->setFlash('success', 'Request Reference # and Sample Code Successfully Generated!');
            }else{
                Yii::$app->session->setFlash('danger', 'Request Reference # and Sample Code Failed to Generate!');
                $return="Failed";
            }

            return $this->redirect(['viewoldreferral','id'=>$request_id]);
        }else{
            return "no data has beeen received";
        }

        //get the request id

        //query the request id and get all the samples


    }

    /**
     * Creates a new Referral model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Referral();
        if ($model->load(Yii::$app->request->post())) {
            $model->referral_date = '0000-00-00';
            $model->referral_time = '';

            if($model->save(false)){
                Yii::$app->session->setFlash('success', $model->samplename." Successfully Updated.");
                return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);

            }
        } elseif (Yii::$app->request->isAjax) {
                return $this->renderAjax('_form', [
                    'model' => $model,
                ]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Referral model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->referral_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Referral model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Referral model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Referral the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Referral::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    //send notification //btc has been here
    public function actionNotify()
    {
        $sample_data = [];
        $analysis_data = [];
        if(Yii::$app->request->get('request_id')){
            $requestId = (int) Yii::$app->request->get('request_id');
            $agency_id = (int) Yii::$app->request->get('agency_id');
        } else {
            Yii::$app->session->setFlash('error', "Request ID not valid!");
            //delay for 2 seconds, before executing next line of code
            sleep(2);
            return $this->redirect(['/lab/request']);
        }
        
        $samples = Sample::find()->where(['request_id'=>$requestId])->asArray()->all();
        //refactored the code here to the referralcomponents on function notifyReferral

        if($agency_id > 0){
                $checkWithAnalysis = $this->checkWithAnalysis($requestId);
                if(count($samples) != $checkWithAnalysis){
                    return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Notification Failed: Make sure each sample contains at least one analysis.</div>";
                }else {

                    $refcomponent = new ReferralComponent;
                    $response = $refcomponent->syncReferral($requestId,$agency_id); //return boolean
					var_dump($response); exit;
                    if($response['response']==1){

                        //put the code here to notify the referred agency
                        //response got index response and referral_id
                        $notifyresponse = $refcomponent->notifyAgency($response['referral_id'],$agency_id,"Notification Sent");
                        
                        if($notifyresponse){

                            $refreq =  Referralrequest::find()->where(['request_id'=>$requestId])->one();
                            if($refreq){
                                $refreq->notified=1;
                                $refreq->save(false);
                            }

                             return "<div class='alert alert-success'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Referral Successfully Synced! and Agency Notified</div>";

                            sleep(2);
                            return $this->redirect(['/lab/request']);
                         }

                        return "<div class='alert alert-warning'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Referral Successfully Synced! but failed to notify. <br/> Please Notify Them Again.</div>";

                    }elseif($response['response']==2){
                        //referral is already exisiting in the server, now we try to notify only
                        $notifyresponse = $refcomponent->notifyAgency($response['referral_id'],$agency_id,"Notification Sent");
                        if($notifyresponse){
                            $refreq =  Referralrequest::find()->where(['request_id'=>$requestId])->one();
                            if($refreq){
                                $refreq->notified=1;
                                $refreq->save(false);
                                return "<div class='alert alert-success'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Referral already Synced! and Agency is Notified!</div>";
                            }
                            return "<div class='alert alert-warning'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Referral already Synced! but failed to notify Agency!</div>";
                        }

                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Referral already Synced! , But failed to notify the target agency</div>";
                    }else{
                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Syncing Failed! , Failed to communicate with the server.</div>";
                    }
                }
        } else {
            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;No agency to be notified!</div>";
        }
    }
    //confirm notification
    public function actionConfirm()
    {
        if (Yii::$app->request->post()) {

            if(!empty(Yii::$app->request->post('estimated_due_date')))
            {
                $connection= Yii::$app->labdb;
                $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
                $transaction = $connection->beginTransaction();

                $mi = !empty(Yii::$app->user->identity->profile->middleinitial) ? " ".substr(Yii::$app->user->identity->profile->middleinitial, 0, 1).". " : " ";

                $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
                $noticeId = (int) Yii::$app->request->get('notice_id');
                $referralId = (int) Yii::$app->request->get('referral_id');
                $sentby = (int) Yii::$app->request->get('sender_id');

                $senderName = Yii::$app->user->identity->profile->firstname.$mi.Yii::$app->user->identity->profile->lastname;

                if($noticeId > 0 && $referralId > 0 && $sentby > 0){
                    $details = [
                        'referral_id' => $referralId,
                        'sender_id' => Yii::$app->user->identity->profile->rstl_id,
                        'recipient_id' => $sentby,
                        'sender_user_id' => Yii::$app->user->identity->profile->user_id,
                        'sender_name' => $senderName,
                        'remarks' => date('Y-m-d',strtotime(Yii::$app->request->post('estimated_due_date'))),
                        'id_noticed' => $noticeId,
                    ];
                    $notificationData = Json::encode(['notice_details'=>$details],JSON_NUMERIC_CHECK);


                    $refcomponent=new ReferralComponent();
                    $notificationResponse = $refcomponent->setestimatedue($notificationData);

                    if($notificationResponse){
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', "Confirmation sent");
                        return $this->redirect(['/referrals/notification']);
                    } else {
                        $transaction->rollBack();
                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Server Error: Confirmation failed!</div>";
                    }
                } else {
                    $transaction->rollBack();
                    return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Invalid request!</div>";
                }
            } else {
                Yii::$app->session->setFlash('error', "Estimated Due Date should not be empty!");
                return $this->redirect(['/referrals/referral/view','id'=>Yii::$app->request->get('referral_id'),'notice_id'=>Yii::$app->request->get('notice_id')]);
            }
        } else {
            return $this->renderAjax('_confirm');
        }
    }
    //send referral
    public function actionSend()
    {
        $sample_data = [];
        //$analysis_data = [];
        if(Yii::$app->request->get('request_id')){
            $requestId = (int) Yii::$app->request->get('request_id');
        } else {
            Yii::$app->session->setFlash('error', "Request ID not valid!");
            //delay for 2 seconds, before executing next line of code
            sleep(2);
            return $this->redirect(['/lab/request']);
        }

        $connection= Yii::$app->labdb;
        $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        $transaction = $connection->beginTransaction();

        $agency_id = (int) Yii::$app->request->get('agency_id');
        //exrequest is an extended class of the request
        $modelRequest = exRequestreferral::find()->where(['request_id'=>$requestId,'request_type_id'=>2])->one();
        //referralrequest is the referral reference of the request , it got its own table
        $ref_request = Referralrequest::find()->where('request_id =:requestId AND notified =:notified',[':requestId'=>$requestId,':notified'=>1])->one();
        //of course the samples
        $samples = Sample::find()->where(['request_id'=>$requestId])->asArray()->all();
        //and the analysis
        $analysesCount = Analysis::find()->where(['request_id'=>$requestId])->count();

        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
        $labId = $modelRequest->lab_id;
        $year = date('Y');

        if($agency_id > 0){
            if(count($modelRequest) > 0 && count($ref_request) > 0 && count($samples) > 0 && $analysesCount > 0)
            {
                //check if each sample contains at least one analysis
                $checkWithAnalysis = $this->checkWithAnalysis($requestId);
                if(count($samples) != $checkWithAnalysis){
                    return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to send: Make sure each sample contains at least one analysis.</div>";
                } else {
                    $generateCode = $this->generateCode($rstlId,$requestId,$labId,$year);
                    if($generateCode == 1){
                        //requerying the request after generating its request Code
                        $request = exRequestreferral::find()->where(['request_id'=>$requestId,'request_type_id'=>2])->one();

                        if(Yii::$app->request->get('bidding') == 1){ //no bidding yet
                            //this is just temporary
                            $data = [];
                        } else {
                            $requestData = [
                                'request_id' => $request->request_id,
                                'request_ref_num' => $request->request_ref_num,
                                'request_datetime' => $request->request_datetime,
                                'rstl_id' => $request->rstl_id,
                                'lab_id' => $request->lab_id,
                                'customer_id' => $request->customer_id,
                                'payment_type_id' => $request->payment_type_id,
                                'modeofrelease_ids' => $request->modeofrelease_ids,
                                'discount_id' => $request->discount_id,
                                'discount' => $request->discount,
                                'purpose_id' => $request->purpose_id,
                                'total' => $request->total,
                                //'report_due' => $request->report_due, //report due is updated base on the estimated due date set by the lab confirmed the referral
                                'conforme' => $request->conforme,
                                'receivedBy' => $request->receivedBy,
                                'request_type_id' => $request->request_type_id,
                                'sample_received_date' => $ref_request->sample_received_date,
                                'user_id_receiving' => Yii::$app->user->identity->profile->user_id
                            ];

                            foreach ($samples as $sample) {
                                $sampleData = [
                                    'sample_id' => $sample['sample_id'],
                                    'request_id' => $sample['request_id'],
                                    'sample_code' => $sample['sample_code'],
                                    'sample_month' => $sample['sample_month'],
                                    'sample_year' => $sample['sample_year']
                                ];
                                array_push($sample_data, $sampleData);
                            }
                            $data = Json::encode(['request_data'=>$requestData,'sample_data'=>$sample_data,'agency_id'=>$agency_id],JSON_NUMERIC_CHECK);
                        }

                        //new function in the referral component that contacts 'sendreferral' action in the api
                        $refcomponent=new ReferralComponent();
                        $referralResponse = $refcomponent->sendReferral($data);

                        switch ($referralResponse['response']) {
                            case 0:
                                $transaction->rollBack();
                                return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to send referral details.</div>";
                                break;
                            case 1 :
                                //return "Referral details saved.";
                                if(Yii::$app->request->get('bidding') == 1){
                                    $analysisSave = 0;
                                    $requestUpdate = Request::find()->where(['request_id'=>$requestId,'request_type_id'=>2])->one();
                                    
                                    $requestUpdate->report_due = $referralResponse['estimated_due'];
                                    $requestUpdate->total = $referralResponse['total_fee'];

                                    foreach ($referralResponse['analyses_data'] as $analysisbid) {
                                        $analysis = Analysis::find()->where('analysis_id =:analysisId AND rstl_id =:rstlId',[':analysisId'=>$analysisbid['local_analysis_id'],':rstlId'=>$rstlId])->one();
                                        
                                        $analysis->fee = $analysisbid['analysis_fee'];
                                        $analysis->referral_analysis_id = $analysisbid['analysis_id'];
                                        if($analysis->save(false)){
                                            $analysisSave = 1;
                                        } else {
                                            $transaction->rollBack();
                                            $analysisSave = 0;
                                        }
                                    }

                                    $referral_request_update = Referralrequest::find()->where('request_id =:requestId AND notified =:notified',[':requestId'=>$requestId,':notified'=>1])->one();
                                    $referral_request_update->testing_agency_id = $agency_id;

                                    if($requestUpdate->save(false) && $referral_request_update->save() && $analysisSave == 1){
                                        goto send;
                                    } else {
                                        $transaction->rollBack();
                                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to update local data!</div>";
                                    }
                                } else {
                                    $requestUpdate = Request::find()->where(['request_id'=>$requestId,'request_type_id'=>2])->one();
                                    $requestUpdate->report_due = $referralResponse['estimated_due'];
                                    $requestUpdate->referral_id = $referralResponse['referral_id'];
                                    $referral_request_update = Referralrequest::find()->where('request_id =:requestId AND notified =:notified',[':requestId'=>$requestId,':notified'=>1])->one();
                                    $referral_request_update->testing_agency_id = $agency_id;

                                    if($requestUpdate->save(false) && $referral_request_update->save()){
                                        goto send;
                                    } else {
                                        $transaction->rollBack();
                                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to update local data!</div>";
                                    }
                                }
                                break;
                            case 2:
                                $transaction->rollBack();
                                return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;No data to be saved.</div>";
                                break;

                            //function to send notification
                            send: {
                                //sends notification that the referral has been sent
                                $transaction->commit();//new changes i move the commit here because the columns needed for the pstc is not existing anymore
                                // $notifyresponse = $refcomponent->notifyAgency($referralResponse['referral_id'],$agency_id,"Referral Sent");
                                $notifyresponse = $refcomponent->notifysendReferral($referralResponse['referral_id'],$agency_id,"Referral Sent");

                                //new line , i move the notification greetings here
                                if($notifyresponse){
                                    return "<div class='alert alert-success'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Referral Sent and the Agency is Notified!</div>";
                                }

                                return "<div class='alert alert-warning'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Referral Sent but failed to notify!</div>";

                                // $pstc_request = exRequestreferral::find()->where(['request_id'=>$requestId,'request_type_id'=>2])->one();

                                // $pstcResponse = 1;

                                // if(!empty($pstc_request->pstc_id)) {

                                //     $pstc_sample_data = [];
                                //     $pstc_analysis_data = [];

                                //     $local_samples = Sample::find()->where(['request_id'=>$requestId])->asArray()->all();
                                //     $local_request = Request::findOne($requestId);
                                //     $local_analyses = Analysis::find()->where(['request_id'=>$requestId])->asArray()->all();

                                //     $pstc_requestData = [
                                //         'pstc_request_id' => $local_request->pstc_request_id,
                                //         'request_ref_num' => $local_request->request_ref_num,
                                //         'rstl_id' => $local_request->rstl_id,
                                //         'pstc_id' => $local_request->pstc_id,
                                //         'customer_id' => $local_request->customer_id,
                                //         'local_request_id' => $local_request->request_id,
                                //         'request_date_created' => $local_request->request_datetime,
                                //         'estimated_due_date' => $local_request->report_due,
                                //         'lab_id' => $local_request->lab_id,
                                //         'discount_id' => (int) $local_request->discount_id,
                                //         'discount_rate' => $local_request->discount,
                                //     ];

                                //     foreach ($local_samples as $s_data) {
                                //         $pstc_sampleData = [
                                //             'pstc_sample_id' => $s_data['pstcsample_id'],
                                //             'sample_code' => $s_data['sample_code'],
                                //             'sample_month' => $s_data['sample_month'],
                                //             'sample_year' => $s_data['sample_year'],
                                //             'rstl_id' => $local_request->rstl_id,
                                //             'sample_description' => $s_data['description'],
                                //             'customer_description' => $s_data['customer_description'],
                                //             'sample_name' => $s_data['samplename'],
                                //             'local_sample_id' => $s_data['sample_id'],
                                //             'local_request_id' => $s_data['request_id'],
                                //             'sampletype_id' => $s_data['sampletype_id'],
                                //             'testcategory_id' => $s_data['testcategory_id'],
                                //             'sampling_date' => $s_data['sampling_date'],
                                //         ];
                                //         array_push($pstc_sample_data, $pstc_sampleData);
                                //     }

                                //     foreach ($local_analyses as $a_data) {
                                //         $pstc_analysisData = [
                                //             'pstc_analysis_id' => $a_data['pstcanalysis_id'],
                                //             'sampletype_id' => $a_data['sample_type_id'],
                                //             'testcategory_id' => $a_data['testcategory_id'],
                                //             'local_analysis_id' => $a_data['analysis_id'],
                                //             'local_sample_id' => $a_data['sample_id'],
                                //             'package_id' => $a_data['package_id'],
                                //             'package_name' => $a_data['package_name'],
                                //             'testname' => $a_data['testname'],
                                //             'testname_id' => $a_data['test_id'],
                                //             'method_id' => $a_data['methodref_id'],
                                //             'method' => $a_data['method'],
                                //             'reference' => $a_data['references'],
                                //             'fee' => $a_data['fee'],
                                //             'is_package' => $a_data['is_package'],
                                //             'is_package_name' => $a_data['is_package_name'],
                                //             'rstl_id' => $local_request->rstl_id,
                                //             'type_fee_id' => $a_data['type_fee_id'],
                                //             'local_user_id' => (int) Yii::$app->user->identity->profile->user_id,
                                //             'local_request_id' => $a_data['request_id'],
                                //         ];
                                //         array_push($pstc_analysis_data, $pstc_analysisData);
                                //     }

                                //     $pstc_request_details = Json::encode(['request_data'=>$pstc_requestData,'sample_data'=>$pstc_sample_data,'analysis_data'=>$pstc_analysis_data,'rstl_id'=>$rstlId,'pstc_id'=>$local_request->pstc_id],JSON_NUMERIC_CHECK);
                                //     $pstcUrl='https://eulimsapi.onelab.ph/api/web/referral/pstcrequests/updaterequest_details';
                                //     //$pstcUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/pstcrequests/updaterequest_details';

                                //     $curlPstc = new curl\Curl();
                                //     $pstcResponse = $curlPstc->setRequestBody($pstc_request_details)
                                //     ->setHeaders([
                                //         'Content-Type' => 'application/json',
                                //         'Content-Length' => strlen($pstc_request_details),
                                //     ])->post($pstcUrl);
                                // }

                                // if($notificationResponse > 0 && $pstcResponse > 0) {
                                //     if($ref_request->notified == 0){
                                //         $transaction->rollBack();
                                //         return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Not yet notified!</div>";
                                //     } else {
                                //         $transaction->commit();
                                //         //return 'Notification sent.';
                                //         //EGG Statuslog
                                //             $refcomponent=new ReferralComponent();
                                //             $refid=$referralResponse['referral_id'];
                                //             $stat=json_decode($refcomponent->getCheckstatus($refid,1));

                                //             if($stat == 0){
                                //                 $received=['referralid'=>$refid,'statusid'=>1];
                                //                 $receivedData = Json::encode(['data'=>$received]);
                                //                 $receivedUrl ='https://eulimsapi.onelab.ph/api/web/referral/statuslogs/insertdata';
                                //                 //$receivedUrl ='http://localhost/eulimsapi.onelab.ph/api/web/referral/statuslogs/insertdata';

                                //                 $curlTesting = new curl\Curl();
                                //                 $receivedResponse = $curlTesting->setRequestBody($receivedData)
                                //                 ->setHeaders([
                                //                         'Content-Type' => 'application/json',
                                //                         'Content-Length' => strlen($receivedData), 
                                //                 ])->post($receivedUrl);
                                //             }
                                //         //
                                //         Yii::$app->session->setFlash('success', "Successfully sent");
                                //         //delay for 2 seconds, before executing next line of code
                                //         sleep(2);
                                //         return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                                //     }
                                // } else {
                                //     $transaction->rollBack();
                                //     return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Server Error: Failed to send!</div>";
                                // }
                            }
                        }
                    } else {
                        $transaction->rollBack();
                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to generate referral code.</div>";
                    }
                }
            } else {
                return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;No data to be posted!</div>";
            }
        } else {
            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;No agency to be sent!</div>";
        }
    }
    //open referral request for bidding
    public function actionOpen()
    {
        $sample_data = [];
        $analysis_data = [];
        if(Yii::$app->request->get('request_id')){
            $requestId = (int) Yii::$app->request->get('request_id');
        } else {
            Yii::$app->session->setFlash('error', "Request ID not valid!");
            //delay for 2 seconds, before executing next line of code
            sleep(2);
            return $this->redirect(['/lab/request']);
        }
        $request = exRequestreferral::find()->where(['request_id'=>$requestId,'request_type_id'=>2])->asArray()->one();
        $ref_request = Referralrequest::find()->where('request_id =:requestId',[':requestId'=>$requestId])->one();
        $samples = Sample::find()->where(['request_id'=>$requestId])->asArray()->all();
        $analyses = Analysis::find()->where(['request_id'=>$requestId])->asArray()->all();
        //$agency_id = (int) Yii::$app->request->get('agency_id');

        if(count($request) > 0 && count($ref_request) > 0 && count($samples) > 0 && count($analyses) > 0)
        {
            //check if each sample contains at least one analysis
            $checkWithAnalysis = $this->checkWithAnalysis($requestId);
            if(count($samples) != $checkWithAnalysis){
                return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Open for Bidding Fail: Make sure each sample contains at least one analysis.</div>";
            } else {
                $connection= Yii::$app->labdb;
                $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
                $transaction = $connection->beginTransaction();

                $requestData = [
                    'request_id' => $request['request_id'],
                    //'request_ref_num' => $request->request_ref_num,
                    'request_datetime' => $request['request_datetime'],
                    'rstl_id' => $request['rstl_id'],
                    'lab_id' => $request['lab_id'],
                    'customer_id' => $request['customer_id'],
                    'payment_type_id' => $request['payment_type_id'],
                    'modeofrelease_ids' => $request['modeofrelease_ids'],
                    'discount_id' => $request['discount_id'],
                    'discount' => $request['discount'],
                    'purpose_id' => $request['purpose_id'],
                    'total' => $request['total'], //fee is default to the currrent fee of test method
                    'report_due' => $request['report_due'], //initial estimated due date sent by the receiving lab
                    'conforme' => $request['conforme'],
                    'receivedBy' => $request['receivedBy'],
                    'status_id' => $request['status_id'],
                    'request_type_id' => $request['request_type_id'],
                    'created_at' => $request['created_at'],
                    'sample_received_date' => $ref_request['sample_received_date'],
                    'user_id_receiving' => Yii::$app->user->identity->profile->user_id,
                    'bid'=>0
                ];

                foreach ($samples as $sample) {
                    $sampleData = [
                        'sample_id' => $sample['sample_id'],
                        'rstl_id' => $sample['rstl_id'],
                    //    'package_id' => $sample['package_id'],
                    //    'package_rate' => $sample['package_rate'],
                        'sampletype_id' => $sample['sampletype_id'],
                        'sample_code' => $sample['sample_code'],
                        'samplename' => $sample['samplename'],
                        'description' => $sample['description'],
                        'customer_description' => $sample['customer_description'],
                        'sampling_date' => $sample['sampling_date'],
                        'remarks' => $sample['remarks'],
                        'request_id' => $sample['request_id'],
                        'sample_month' => $sample['sample_month'],
                        'sample_year' => $sample['sample_year'],
                        'active' => $sample['active'],
                        'completed' => $sample['completed']
                    ];
                    array_push($sample_data, $sampleData);
                }

                foreach ($analyses as $analysis) {
                    $analysisData = [
                        'analysis_id' => $analysis['analysis_id'],
                        'date_analysis' => $analysis['date_analysis'],
                        'rstl_id' => $analysis['rstl_id'],
                        'request_id' => $analysis['request_id'],
                        'sample_id' => $analysis['sample_id'],
                        'sample_code' => $analysis['sample_code'],
                        'package_id' => $analysis['package_id'],
                        'testname' => $analysis['testname'],
                        'method' => $analysis['method'],
                        'methodref_id' => $analysis['methodref_id'],
                        'references' => $analysis['references'],
                        'fee' => $analysis['fee'],
                        'test_id' => $analysis['test_id'],
                        'cancelled' => $analysis['cancelled'],
                        'is_package' => $analysis['is_package'],
                        'is_package_name' => $analysis['is_package_name'],
                        'type_fee_id' => $analysis['type_fee_id']
                    ];
                    array_push($analysis_data, $analysisData);
                }

                $data = Json::encode(['request_data'=>$requestData,'sample_data'=>$sample_data,'analysis_data'=>$analysis_data],JSON_NUMERIC_CHECK);

                $referralUrl='https://eulimsapi.onelab.ph/api/web/referral/referrals/insertreferraldata';
                //$referralUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/referrals/insertreferraldata';
               
                $curl = new curl\Curl();
                $referralreturn = $curl->setRequestBody($data)
                ->setHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen($data),
                ])->post($referralUrl);

                $referralResponse = Json::decode($referralreturn);
                switch ($referralResponse['response']) {
                    case 0:
                        $transaction->rollBack();
                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to save referral details.</div>";
                        break;
                    case 1 :
                        //return "Referral details saved.";
                        goto notify;
                        break;
                    case 2:
                        $transaction->rollBack();
                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;No data to be saved.</div>";
                        break;
                    case 3:
                        //return "Referral details already existing.";
                        goto notify;
                        break;

                    //function to send notification
                    notify: {
                        $details = [
                            'referral_id' => $referralResponse['referral_id'],
                            'receiving_agency_id' => Yii::$app->user->identity->profile->rstl_id,
                            'sender_user_id' => Yii::$app->user->identity->profile->user_id,
                        ];
                        $bidnotificationData = Json::encode(['notice_details'=>$details],JSON_NUMERIC_CHECK);

                        $bidnotificationUrl ='https://eulimsapi.onelab.ph/api/web/referral/bidnotifications/notify';
                        //$bidnotificationUrl ='http://localhost/eulimsapi.onelab.ph/api/web/referral/bidnotifications/notify';

                        $curlNoti = new curl\Curl();
                        $notificationResponse = $curlNoti->setRequestBody($bidnotificationData)
                        ->setHeaders([
                            'Content-Type' => 'application/json',
                            'Content-Length' => strlen($bidnotificationData),
                        ])->post($bidnotificationUrl);

                        if($notificationResponse > 0){
                            if($ref_request->notified == 0){
                                //echo 'Notification sent.';
                                $modelref_request = Referralrequest::find()->where(['request_id'=>$requestId])->one();
                                $modelref_request->notified = 1;
                                if($modelref_request->save()){
                                    $transaction->commit();
                                    Yii::$app->session->setFlash('success', "Successfully opened for bidding.");
                                    //delay for 2 seconds, before executing next line of code
                                    sleep(2);
                                    return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                                } else {
                                    $transaction->rollBack();
                                    return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Open Bidding Failed: Error occured to open bid!</div>";
                                }
                            } else {
                                $transaction->commit();
                                //return 'Notification sent.';
                                Yii::$app->session->setFlash('success', "Successfully opened for bidding.");
                                //delay for 2 seconds, before executing next line of code
                                sleep(2);
                                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                            }
                        } else {
                            $transaction->rollBack();
                            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Server Error: Failed to open for bidding!</div>";
                        }
                    }
                }
            }
        } else {
            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;No data to be posted!</div>";
        }
    }

    //referral details save as local request 
    //btc reached here , lets see what is in store
    public function actionSavelocal()
    {
        //saving request
        if (Yii::$app->request->get()) {
            $referralId = (int) Yii::$app->request->get('referral_id');
            $notificationId = (int) Yii::$app->request->get('notice_id');

            if($referralId > 0 && $notificationId > 0){
                $model = new exRequestreferral();
                $modelReferralrequest = new Referralrequest();
                $refcomponent = new ReferralComponent();
                $rstlId = Yii::$app->user->identity->profile->rstl_id;
                $connection= Yii::$app->labdb;
                $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
                $transaction = $connection->beginTransaction();

                $referralDetails = $refcomponent->getReferralRequestDetails($referralId,$rstlId);
                $referral = $referralDetails['request_data'];
                $samples_analyses = $referralDetails['sample_analysis_data'];
                $customer = $referralDetails['customer_data'];
                //request
                $model->request_datetime = date('Y-m-d H:i:s', strtotime($referral['referral_date_time']));
                $model->rstl_id = $rstlId;
                $model->lab_id = $referral['lab_id'];
                $model->customer_id = $referral['customer_id'];
                $model->payment_type_id = $referral['payment_type_id'];
                $model->modeofrelease_ids = $referral['modeofrelease_id'];
                $model->discount = $referral['discount_rate'];
                $model->discount_id = $referral['discount_id'];
                $model->purpose_id = $referral['purpose_id'];
                $model->total = $referral['total_fee'];
                $model->report_due = $referral['report_due'];
                $model->conforme = $referral['conforme'];
                $model->receivedBy = $referral['cro_testing'];
                $model->created_at = date('U');
                $model->request_type_id = 2;
                $model->payment_status_id = 1;
                $model->sample_received_date = $referral['sample_received_date'];
                $model->referral_id = $referralId;

                if ($model->save(false)){
                    $requestId = $model->request_id;
                    $updateReference = $connection->createCommand()->update('tbl_request', ['request_ref_num' => $referral['referral_code']],'request_id =:requestId')->bindParam(':requestId', $requestId)->execute();

                    $modelReferralrequest->request_id = $requestId;
                    $modelReferralrequest->sample_received_date = $referral['sample_received_date'];
                    $modelReferralrequest->receiving_agency_id = $referral['receiving_agency_id'];
                    $modelReferralrequest->testing_agency_id = $rstlId;
                    $modelReferralrequest->referral_type_id = 2;
                    foreach($samples_analyses as $sample){
                        $modelSample = new Sample();
                        $modelSample->request_id = $model->request_id;
                        $modelSample->rstl_id = $rstlId;
                        $modelSample->sample_month = date_format(date_create($model->request_datetime),'m');
                        $modelSample->sample_year = date_format(date_create($model->request_datetime),'Y');
                        $modelSample->sampletype_id = $sample['sample_type_id'];
                        $modelSample->samplename = $sample['sample_name'];
                        $modelSample->description = $sample['description'];
                        $modelSample->customer_description = $sample['customer_description'];
                        $modelSample->sampling_date = $sample['sampling_date'];
                        $modelSample->referral_sample_id = $sample['sample_id'];
                        if($modelSample->save(false)){
                            foreach ($sample['analyses'] as $analysis) {
                                $modelAnalysis = new Analysisextend();
                                $modelAnalysis->rstl_id = (int) $rstlId;
                                $modelAnalysis->date_analysis = date('Y-m-d');
                                $modelAnalysis->request_id = $requestId;
                                $modelAnalysis->sample_id = $modelSample->sample_id;
                                $modelAnalysis->test_id = $analysis['testname_id'];
                                $modelAnalysis->testname = $analysis['testname']['test_name'];
                                $modelAnalysis->methodref_id = $analysis['methodreference_id'];
                                $modelAnalysis->method = $analysis['methodreference']['method'];
                                $modelAnalysis->references = $analysis['methodreference']['reference'];
                                $modelAnalysis->fee =$analysis['analysis_fee'];
                                $modelAnalysis->quantity = 1; 
                                $modelAnalysis->referral_analysis_id = $analysis['analysis_id'];
                                if($modelAnalysis->save(false)){
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
                    if($sampleSave == 1 && $analysisSave == 1 && $modelReferralrequest->save() && $updateReference) {
                        $func = new Functions();
                        $samplecode = $func->GenerateSampleCode($requestId);
                        $sample_data = [];
                        if($samplecode == "true"){
                            $samples = Sample::find()->where(['request_id'=>$requestId])->asArray()->all();

                            foreach ($samples as $data) {
                                $sampleData = [
                                    'sample_id' => $data['referral_sample_id'],
                                    'sample_code' => $data['sample_code'],
                                    'sample_month' => $data['sample_month'],
                                    'sample_year' => $data['sample_year'],
                                ];
                                array_push($sample_data, $sampleData);
                            }

                            $sampledata = Json::encode(['sample_data'=>$sample_data,'referral_id'=>$referralId,'receiving_agency'=>$referral['receiving_agency_id'],'notificationId'=>$notificationId],JSON_NUMERIC_CHECK);
                            //update the sample code of the referral in the api
                       
                            $referralreturn = $refcomponent->updatesamplecode($sampledata); //changes made we will also update the notification 
                            if($referralreturn == 1){

                                $transaction->commit();
                                Yii::$app->session->setFlash('success', 'Request successfully saved!');
                                return $this->redirect(['view', 'id' => $referralId,'notice_id'=>$notificationId]);

                            } else {
                                $transaction->rollBack();
                                return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to update sample code!</div>";
                            }
                        } else {
                            $transaction->rollBack();
                            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Failed to generate samplecode!</div>";
                        }
                    } else {
                        $transaction->rollBack();
                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Request was not saved to local!</div>";
                    }
                } else {
                    $transaction->rollBack();
                    return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Request was not saved to local!</div>";
                }
            } else {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Invalid request!');
                return $this->redirect(['notifications']);
            }
        } else {
            Yii::$app->session->setFlash('error', 'Invalid request!');
            return $this->redirect(['view', 'id' => $referralId,'notice_id'=>$notificationId]);
        }
    }
    //find referral request
    protected function findRequest($id)
    {
        $model = exRequestreferral::find()->where(['request_id'=>$id,'request_type_id'=>2])->one();
        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested Request its either does not exist or you have no permission to view it.');
        }
    }
    //check if sample has at least one analysis
    protected function checkWithAnalysis($requestId)
    {
        $analyses = Yii::$app->labdb->createCommand("
                    SELECT tbl_sample.sample_id, COUNT(tbl_analysis.`analysis_id`) AS 'analysisCount'
                    FROM tbl_sample LEFT JOIN tbl_analysis 
                    ON tbl_sample.`sample_id` = tbl_analysis.`sample_id`
                    WHERE tbl_sample.`request_id` =:requestId
                    GROUP BY tbl_sample.sample_id
                    HAVING analysisCount > 0")
                ->bindParam(':requestId', $requestId)->queryAll();
        return count($analyses);
    }
    //generate reference code and sample code
    protected function generateCode($rstlId,$requestId,$labId,$year){

        /*** code salvage from EGG in generating the request code*/

        $lastnum=(new Query)
            ->select('MAX(number) AS lastnumber')
            ->from('eulims_lab.tbl_requestcode')
            ->where(['lab_id' => $labId, 'year'=> $year])
            ->one();
        $monthyear=date('mY',strtotime(date('Y-m-d')));
        $rstl= Rstl::find()->where(['rstl_id'=>$rstlId])->one();
        $code=$rstl->code;
        
        $lab= Lab::find()->where(['lab_id'=>$labId])->one();
        $labcode=$lab->labcode;
        
        $str_trans_num=0;
          if($lastnum != ''){
            $str_trans_num=$lastnum["lastnumber"] + 1;
            $str_trans_num=str_pad($str_trans_num, 4, "0", STR_PAD_LEFT);
              
          }
          else{
              $str_trans_num='0001';
          }

        $referenceNumber=$code."-".$monthyear."-".$labcode."-".$str_trans_num;
        $requestIncrement=$str_trans_num;
        /*******************/

        $connection= Yii::$app->labdb;
        $transaction =$connection->beginTransaction();
        //Update the tbl_requestcode
        $requestcode= Requestcode::find()->where([
            'rstl_id'=>(int) $rstlId,
            'lab_id'=>(int) $labId,
            'year'=>(int) $year
        ])->one($connection);
        
        if(!$requestcode){
            $requestcode=new Requestcode();
        }
        $requestcode->request_ref_num=$referenceNumber;
        $requestcode->rstl_id=(int) $rstlId;
        $requestcode->lab_id=(int) $labId;
        $requestcode->number=(int) $requestIncrement;
        $requestcode->year=(int) $year;
        $requestcode->cancelled=0;
        $requestcode->save();
        //Update tbl_request table
        $request= Request::find()->where(['request_id'=>$requestId])->one($connection);
        $request->request_ref_num=$referenceNumber;
        $request->request_datetime=date("Y-m-d h:i:s");
       
        $discountquery = Discount::find()->where(['discount_id' => $request->discount_id])->one();

        $rate =  $discountquery->rate;
        
        $sql = "SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id=$requestId";
        $command = $connection->createCommand($sql);
        $subrow = $command->queryOne();
        $subtotal = $subrow['subtotal'];
        $total = $subtotal - ($subtotal * ($rate/100));
        
        $request->total = $total;

        if($request->save(false)){
            $samples = Sample::find()->where(['request_id'=>$requestId])->asArray()->all();

            foreach ($samples as $sample) {
                $modelSample = Sample::find()->where(['sample_id'=>$sample['sample_id'],'request_id'=>$requestId])->one();
                $modelSample->sample_month = date('m', strtotime($request->request_datetime));
                $modelSample->sample_year = date('Y', strtotime($request->request_datetime));

                if($modelSample->save(false)){
                    $sampleSave = 1;
                } else {
                    $transaction->rollBack();
                    $return = 0;
                }
            }
            if($sampleSave == 1){
                $transaction->commit();
                $return = 1;
            } else {
                $transaction->rollBack();
                $return = 0;
            }
        } else {
            $transaction->rollBack();
            $return = 0;
        }
        return $return;
    }

    //get samplecode from referral db
    public function actionGet_samplecode()
    {

        if(Yii::$app->request->get('request_id')){
            $requestId = (int) Yii::$app->request->get('request_id');
        } else {
            Yii::$app->session->setFlash('error', "Request ID not valid!");
            //delay for 2 seconds, before executing next line of code
            sleep(2);
            return $this->redirect(['/lab/request']);
        }

        if($requestId > 0){
            $connection= Yii::$app->labdb;
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
            $transaction = $connection->beginTransaction();

            $rstlId = Yii::$app->user->identity->profile->rstl_id;
            $refcomponent = new ReferralComponent();
            $get_details = $refcomponent->getSamplecode_details($requestId,$rstlId);
            if($get_details > 0){
                $referral = $get_details['referral_data'];
                $samples_analyses = $get_details['sample_analysis_data'];
            } else {
                Yii::$app->session->setFlash('error', "Sample code not yet generated!");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            }

            //update request
            $request = $this->findRequest($requestId);
            $request->referral_id = $referral['referral_id'];
            if($request->save(false)) {
                foreach ($samples_analyses as $data_sample) {
                    if(empty($data_sample['sample_code'])){
                        $transaction->rollBack();
                        $requestSave = 0;
                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Testing Lab didn't generate sample code yet!</div>";
                    } else {
                        //update sample
                        $sample = Sample::find()->where(['sample_id'=>$data_sample['local_sample_id'],'request_id'=>$data_sample['local_request_id']])->one();
                        $sample->sample_code = $data_sample['sample_code'];
                        $sample->referral_sample_id = $data_sample['sample_id'];
                        if($sample->save(false)){
                            //insert to tbl_samplecode
                            $samplecode = new Samplecode();
                            $samplecode->rstl_id = $rstlId;
                            $samplecode->reference_num = $referral['referral_code'];
                            $samplecode->sample_id = $data_sample['local_sample_id'];
                            $samplecode->lab_id = $referral['lab_id'];
                            $samplecode->number = 0; //if the source is referral request
                            $samplecode->year = date('Y',strtotime($request->request_datetime));
                            $samplecode->source = 2;

                            if($samplecode->save()){
                                foreach ($data_sample['analyses'] as $data_analysis) {
                                    //update analysis
                                    $analysis = Analysis::find()->where(['sample_id'=>$data_analysis['local_sample_id'],'analysis_id'=>$data_analysis['local_analysis_id']])->one();
                                    $analysis->referral_analysis_id = $data_analysis['analysis_id'];
                                    if($analysis->save(false)){
                                        $analysisSave = 1;
                                    } else {
                                        $transaction->rollBack();
                                        $analysisSave = 0;
                                        return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Analysis not updated!</div>";
                                    }
                                }
                                $samplecodeSave = 1;
                            } else {
                                $transaction->rollBack();
                                $samplecodeSave = 0;
                            }
                            $sampleSave = 1;
                        } else {
                            $transaction->rollBack();
                            $sampleSave = 0;
                            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Sample not updated!</div>";
                        }
                    }
                }
                $requestSave = 1;
            } else {
                $transaction->rollBack();
                $requestSave = 0;
                return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Request not updated!</div>";
            }
            if($requestSave == 1 && $sampleSave == 1 && $analysisSave == 1 && $samplecodeSave == 1) {

                //these 3 lines of code are from below , but we dont need to udpate the pstc yet
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Sample code updated!');
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);

                //pstc disabled here temporarily
                //check if the request is from the pstc and updates it ~btc
                // if($request->pstc_request_id > 0 && $request->pstc_id > 0) {
                //     $local_samples = Sample::find()->where(['request_id'=>$requestId])->asArray()->all();
                //     $local_request = Request::findOne($requestId);
                //     $pstc_sample_data = [];

                //     $pstc_requestData = [
                //         'pstc_id' => $local_request->pstc_id,
                //         'rstl_id' => $local_request->rstl_id,
                //         'request_id' => $local_request->request_id,
                //         'pstc_request_id' => $local_request->pstc_request_id,
                //     ];

                //     foreach ($local_samples as $s_data) {
                //         $pstcsampleData = [
                //             'pstc_sample_id' => $s_data['pstcsample_id'],
                //             'sample_code' => $s_data['sample_code'],
                //             'sample_id' => $s_data['sample_id'],
                //             'rstl_id' => $local_request->rstl_id,
                //             'local_sample_id' => $s_data['sample_id'],
                //             'local_request_id' => $s_data['request_id'],
                //         ];
                //         array_push($pstc_sample_data, $pstcsampleData);
                //     }

                //     $pstc_request_details = Json::encode(['sample_data'=>$pstc_sample_data,'request_data'=>$pstc_requestData],JSON_NUMERIC_CHECK);
                //     $pstcUrl='https://eulimsapi.onelab.ph/api/web/referral/pstcrequests/update_samplecode';
                //     //$pstcUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/pstcrequests/update_samplecode';
               
                //     $curl = new curl\Curl();
                //     $pstc_return = $curl->setRequestBody($pstc_request_details)
                //     ->setHeaders([
                //         'Content-Type' => 'application/json',
                //         'Content-Length' => strlen($pstc_request_details),
                //     ])->post($pstcUrl);

                //     if($pstc_return == 1) {
                //         $transaction->commit();
                //         Yii::$app->session->setFlash('success', 'Sample code updated!');
                //         return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                //     } else {
                //         $transaction->rollBack();
                //         return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Fail to update sample code!</div>";
                //     }
                // } else {
                //     $transaction->commit();
                //     Yii::$app->session->setFlash('success', 'Sample code updated!');
                //     return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                // }
            } else {
                $transaction->rollBack();
                return "<div class='alert alert-danger'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:18px;'></span>&nbsp;Can't get sample code!</div>";
            }
        } else {
            Yii::$app->session->setFlash('error', 'Invalid request!');
            return $this->redirect(['/lab/request/view', 'id' => $requestId]);
        }
    }
    protected function findModelRef($id)
    {
        if (($model = Referral::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    protected function findModelreceivedtrack($referral_id)
    {
        $refcomponent = new ReferralComponent();
        $referralreceivingDetails = json_decode($refcomponent->getTrackreceiving($referral_id));
        
        //$received = $referralreceivingDetails['receiving_data'];
        return $referralreceivingDetails;
        
    }  
    protected function findModeltestingtrack($referral_id)
    {
        $refcomponent = new ReferralComponent();
        $trackingdetails = json_decode($refcomponent->getTracktesting($referral_id));
//        $tracking=$trackingdetails['testing_data'];
        
        return $trackingdetails;
        
    }

    public function actionPrintref($id){
        $referral_id = (int) $id;
        $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;

        $refcomponent = new ReferralComponent();
        $referralDetails = json_decode($refcomponent->getReferraldetails($referral_id,$rstlId),true);

        $request = $referralDetails['request_data'];
        $samples = $referralDetails['sample_data'];
        $analyses = $referralDetails['analysis_data'];
        $customer = $referralDetails['customer_data'];
       // print_r($request);
       // print_r($analyses);

       // exit;
        $Printing=new Printreferral();
        $Printing->setReferral($request);
        $Printing->setSamples($samples);
        $Printing->setAnalyses($analyses);
        $Printing->setCustomer($customer);
        $Printing->Printing($id); 
    }    
}
