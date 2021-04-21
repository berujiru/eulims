<?php

namespace frontend\modules\api\controllers;

use common\models\referral\Lab;
use common\models\referral\Discount;
use common\models\referral\Purpose;
use common\models\referral\Modeofrelease;
use common\models\referral\Sampletype;
use common\models\referral\Sampletypetestname;
use common\models\referral\Testnamemethod;
use common\models\referral\Testname;
use common\models\referral\Methodreference;
use common\models\referral\Packagelist;
use common\models\referral\Agency;
use common\models\referral\Notification;
use common\models\referral\Bidnotification;
use common\models\referral\Referral;
use common\models\referral\Sample;
use common\models\referral\Analysis;
use common\models\referral\Attachment;
use common\models\referral\Pstcrequest;
use common\models\referral\Customer;
use common\models\referral\Referralextend;
use common\models\referral\Rstl;

use yii\db\Query;

class RestreferralController extends \yii\rest\Controller
{
	public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \sizeg\jwt\JwtHttpBearerAuth::class,
            'except' => ['index','upload_deposit','upload_or','getdiscount'],
            'user'=> \Yii::$app->referralaccount
        ];

        return $behaviors;
    }

    protected function verbs(){
        return [
            // 'login' => ['POST'],
            // 'user' => ['GET'],
        ];
    }

    public function actionIndex(){
        return "Index";
    }

    public function actionLabs(){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Lab::find()->all();
        return $data;
    }

    public function actionDiscounts(){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Discount::find()->all();
        return $data;
    }

    public function actionPurposes(){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Purpose::find()->all();
        return $data;
    }

    public function actionModesrelease(){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Modeofrelease::find()->all();
        return $data;
    }

    public function actionGetdiscount($discountid){
        $discount= Discount::find()->where(['discount_id'=>$discountid])->one();
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        return $discount;
    }

    public function actionGetcustomer($referral_id){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $reff = Referral::findOne($referral_id);
        if(!$reff)
            return null;
        $customer = Customer::find()->where(['local_customer_id'=>$reff->customer_id,'rstl_id'=>$reff->receiving_agency_id])->one();
        return $customer;
    }

    //returns a list of rstl ids of the agencies
    public function actionListmatchagency($rstl_id,$lab_id,$methodref_id,$package_id){ 
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        //search the testnamemethod record
        $model = Methodreference::find()->select(['sync_id'])->where(['methodreference_id'=>explode(',',$methodref_id)])->all();
        //returns list of sync ids example 11-5 where 11 is an rstl_id 

        $agency_ids = implode(',', array_map(function ($data) {
            //explodes the restl id from the pk of the record
            $rstl_id = explode('-', $data['sync_id']);
            return $rstl_id[0];
        }, $model));

        return $agency_ids;
    }

    public function actionListagency($agency_id){

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        $data = Rstl::find()
                    ->where([
                        'rstl_id' =>array_map('intval', explode(',', $agency_id)),
                    ])
                    ->orderBy('rstl_id')
                    ->asArray()
                    ->all();

        return $data;
    }
    //EGG 03/23/21
    public function actionShowupload($referral_id,$type){
        $data = Attachment::find()
                            ->where('referral_id =:referral_id', [':referral_id'=>$referral_id])
							->andWhere('attachment_type=:type', [':type'=>$type])
                            ->all();
		return $data;
    }

    public function actionReferred_agency($referral_id,$rstl_id){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        $data = false;
        $referral = Referral::find()
                    ->select('receiving_agency_id,testing_agency_id')
                    ->where('referral_id =:referralId', [':referralId'=>$referral_id])
                    ->andWhere('receiving_agency_id =:receivingAgency OR testing_agency_id =:testingAgency', [':receivingAgency'=>$rstl_id,':testingAgency'=>$rstl_id])
                    ->one();
        
        
        if($referral){
            $receivingAgency = Agency::find()
                        ->where('agency_id =:agencyId', [':agencyId'=>$referral->receiving_agency_id])
                        ->one();
            $testingAgency = Agency::find()
                        ->where('agency_id =:agencyId', [':agencyId'=>$referral->testing_agency_id])
                        ->one();
            $data = ['receiving_agency'=>$receivingAgency,'testing_agency'=>$testingAgency];
        }else{
            $notification = Notification::find()
                        ->select('sender_id,recipient_id')
                        ->where('referral_id =:referralId AND notification_type_id = 1', [':referralId'=>$referral_id])
                        ->andWhere('sender_id =:senderAgency OR recipient_id =:recipientAgency', [':senderAgency'=>$rstl_id,':recipientAgency'=>$rstl_id])
                        ->one();
            if($notification){
                $receivingAgency = Agency::find()
                            ->where('agency_id =:agencyId', [':agencyId'=>$notification->sender_id])
                            ->one();
                    
                $testingAgency = Agency::find()
                            ->where('agency_id =:agencyId', [':agencyId'=>$notification->recipient_id])
                            ->one();
                $data = ['receiving_agency'=>$receivingAgency,'testing_agency'=>$testingAgency];
            }
        }

        return $data;
    }

    public function actionBidderagency($request_id,$rstl_id){
        return null;
    }

    public function actionBidnotice($request_id,$rstl_id){
        return null;
    }

    //gets the sampletype via lab id
    public function actionSampletypebylab($lab_id){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        $data = Testnamemethod::find()
            ->select(['tbl_testname_method.sampletype_id','type'])
            ->joinWith('sampletype')
            ->where(['lab_id' => $lab_id])
            ->groupby(['sampletype_id'])
            ->asArray()
            ->all();            



        return $data;
    }

    /**
     * Lists data sampletype_testname by sampletype_id
     * @return mixed
     */

    public function actionTestnamebysampletypeids($sampletype_ids,$lab_id){

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $sampletypeId = rtrim($sampletype_ids);



        $data = Testnamemethod::find()
            ->select(['tbl_testname_method.testname_id','test_name'=>'tbl_testname.test_name'])
            ->joinWith('testname')
            ->where(['sampletype_id' => explode(',', $sampletypeId)])
            ->andWhere(['lab_id' => $lab_id])
            ->groupBy('tbl_testname_method.testname_id')
            ->orderBy('tbl_testname_method.testname_id')
            ->asArray()
            ->all();

        return $data;
    }

    public function actionTestnamemethodref($testname_id,$lab_id,$sampletypeId){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data= Testnamemethod::find()
            ->joinWith(['testname','methodreference'])
            ->where('tbl_testname_method.testname_id = :testnameId', [':testnameId' => $testname_id])
            ->andWhere(['sampletype_id' => explode(',', $sampletypeId)])
            ->andWhere(['lab_id' => $lab_id])
            ->asArray()
            ->all();
        return $data;
    }

    public function actionTestnameone($testname_id){

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Testname::find()
            ->where('testname_id =:testnameId', [':testnameId'=>$testname_id])
            ->asArray()
            ->one();
        return $data;
    }

    public function actionMethodreferenceone($methodref_id){

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Methodreference::find()
            ->where('methodreference_id =:method_ref_id', [':method_ref_id'=>$methodref_id])
            ->asArray()
            ->one();

    return $data;   
    }

     public function actionAgencymethodreferenceone($methodref_id){

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Methodreference::find()
            ->where('methodreference_id =:method_ref_id', [':method_ref_id'=>$methodref_id])
            ->one();
        if(!$data) //if no reference returns null array
            return [];

        $agency = explode('-', $data->sync_id);

        if(!$agency[0]) //if no value returns null
            return [];

        $agen= Rstl::findOne($agency[0]);

        return $agen;   
    }

    public function actionDiscountbyid($discount_id){

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Discount::find()->where(['discount_id'=>$discount_id,'status'=>1])->one();        
        return $data;
    }

    public function actionListpackage($lab_id,$sampletype_id){

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $data = Packagelist::find()
            ->where(['lab_id'=>$lab_id,'sampletype_id'=> explode(',', $sampletype_id) ])
            ->all();
        return $data;
    }

    public function actionPackage_detail($package_id){

        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $package = Packagelist::find()->where(['package_id'=>$package_id])->one();

        if(!empty($package->test_methods)){
            $testmethods = explode(",",$package->test_methods);
            $testmethod_data = [];
            foreach ($testmethods as $testmethodId){
                $testmethodrefs = Testnamemethod::findOne($testmethodId);
                $listTestmethods = [
                    'testname_method_id'=>$testmethodrefs->testname_method_id,
                    'testname_id'=>$testmethodrefs->testname_id,
                    'testname'=>$testmethodrefs->testname->test_name,
                    'methodreference_id'=>$testmethodrefs->methodreference_id,
                    'method'=>$testmethodrefs->methodreference->method,
                    'reference'=>$testmethodrefs->methodreference->reference,
                ];
                array_push($testmethod_data, $listTestmethods);
            }
            return ['testmethod_data'=>$testmethod_data,'package'=>$package];
        } else {
            return false;
        }
    }
    //expecting to return a boolean value
    public function actionCheckactivelab($lab_id,$agency_id){
         \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
         return 1; //returns boolean better return it true but the client checks if 1 or not 1
    }

    //expecting to return 0 = request_id invalid request id, 1 = already notified, 2 = not yet modified
    public function actionChecknotify($request_id,$agency_id){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        //query the notification table join with referral, use the agencyid as recipient id and request id as local request id, and notify value to 1

        $referral = Notification::find()
                        ->select('tbl_notification.*')
                        ->joinWith('referral')
                        ->where('local_request_id =:localrequestId', [':localrequestId'=>$request_id])
                        ->andWhere('recipient_id =:recipientId', [':recipientId'=>$agency_id])
                        ->andWhere('notification_type_id =:notify',[':notify'=>1])
                        ->count();
        if($referral)
            return 2; //already sent notification
        return 1;
    }

    //expecting to return a boolean value
    public function actionCheckConfirm($request_id,$receiving_id,$testing_id){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        $model = Notification::find()
                        ->select('tbl_notification.*')
                        ->joinWith('referral')
                        ->where('local_request_id =:localrequestId', [':localrequestId'=>$request_id])
                        ->andWhere('sender_id =:senderId', [':senderId'=>$receiving_id])
                        ->andWhere('recipient_id =:recipientId', [':recipientId'=>$testing_id])
                        ->andWhere('notification_type_id =:notify',[':notify'=>1])
                        ->andWhere('responded =:responded',[':responded'=>1])
                        ->count();

        $confirmed = Notification::find()
                        ->select('tbl_notification.*')
                        ->joinWith('referral')
                        ->where('local_request_id =:localrequestId', [':localrequestId'=>$request_id])
                        ->andWhere('sender_id =:senderId', [':senderId'=>$testing_id])
                        ->andWhere('recipient_id =:recipientId', [':recipientId'=>$receiving_id])
                        ->andWhere('notification_type_id =:confirm',[':confirm'=>2])
                        ->count();

        if($notified > 0 && $confirmed > 0){
            return 1;
        } else {
            return 0;
        }
    }

    //return estimated due date
    public function actionShowdue($request_id,$rstl_id,$sender_id)
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        try {
            $showdue = Notification::find()
                ->select('notification_id, tbl_notification.remarks as estimated_due')
                ->joinWith(['referral'],false)
                ->where('local_request_id =:localrequestId AND receiving_agency_id=:receivingAgency', [':localrequestId'=>$request_id,':receivingAgency'=>$rstl_id])
                ->andWhere('sender_id =:sender AND notification_type_id =:notificationType', [':sender'=>$sender_id,':notificationType'=>2])
                ->asArray()->one();
            
            if($showdue){
                return $showdue['estimated_due'];
            } else {
                return 0;
            }
            
        } catch (Exception $e) {
            throw new \yii\web\HttpException(500, 'Internal server error');
        }

    }

    //sets notification with estimated due
    public function actionConfirm()
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        if(count(\Yii::$app->request->post('notice_details')) > 0){
            $connection= \Yii::$app->referraldb;
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
            $transaction = $connection->beginTransaction();
            
            $notification = new Notification;
            $details = \Yii::$app->request->post('notice_details');
            $notification->referral_id = (int) $details['referral_id'];
            $notification->notification_type_id = 2;
            $notification->sender_id = (int) $details['sender_id'];
            $notification->recipient_id = (int) $details['recipient_id'];
            $notification->sender_user_id = (int) $details['sender_user_id'];
            $notification->sender_name = $details['sender_name'];
            $notification->remarks = $details['remarks'];
            $notification->notification_date = date('Y-m-d H:i:s');
            if($notification->save()){
                $model_notification = Notification::find()->where(['notification_id'=>$details['id_noticed']])->one();
                $model_notification->responded = 1;
                $model_notification->date_responded = date('Y-m-d H:i:s');
                if($model_notification->save(false)){
                    $transaction->commit();
                    //$return = $notification->notification_id;
                    return true;
                } else {
                    $transaction->rollBack();
                    return false;
                }
            } else {
                $transaction->rollBack();
                return false;
            }
        } else {
            return false;
        }
    }

    //returns 2 responses , response and referral_id
    public function actionInsertreferraldata()
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $return = 0;
        $referralId = 0;
        $referralSave = 0;
        $sampleSave = 0;
        $analysisSave = 0;

        if(count(\Yii::$app->request->post()) > 0){
            //be safe patricia!!, its a life saver
            $connection= \Yii::$app->referraldb;
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
            $transaction = $connection->beginTransaction();

            $request = \Yii::$app->request->post('request_data');
            $samples = \Yii::$app->request->post('sample_data');
            $analyses = \Yii::$app->request->post('analysis_data');
            //checks if the referral in the centralized server is already existing
            $checkReferral = $this->checkReferral($request['request_id'],$request['rstl_id']);
            if ($checkReferral>0) {
                #referral is already existing
                //therefore passing it to the referralID variable
                $referralId = $checkReferral;
                $return = 2;
                return ['response'=>$return,'referral_id'=>$referralId];
            } else{
                if(count($request)>0){
                        $modelReferral = new Referral;
                        $modelReferral->referral_date_time = $request['request_datetime'];
                        $modelReferral->referralDate = date('Y-m-d',strtotime($request['request_datetime']));
                        $modelReferral->referralTime = date("h:i:sa",strtotime($request['request_datetime']));
                        $modelReferral->local_request_id = $request['request_id'];
                        $modelReferral->receiving_agency_id = $request['rstl_id'];
                        $modelReferral->testing_agency_id = 0;
                        $modelReferral->lab_id = $request['lab_id'];
                        $modelReferral->sample_received_date = $request['sample_received_date'];
                        $modelReferral->customer_id = $request['customer_id'];
                        $modelReferral->payment_type_id = $request['payment_type_id'];
                        $modelReferral->modeofrelease_id = $request['modeofrelease_ids'];
                        $modelReferral->purpose_id = $request['purpose_id'];
                        $modelReferral->discount_id = $request['discount_id'];
                        $modelReferral->discount_rate = $request['discount'];
                        $modelReferral->total_fee = $request['total'];
                        $modelReferral->report_due = $request['report_due'];
                        $modelReferral->conforme = $request['conforme'];
                        $modelReferral->receiving_user_id = $request['user_id_receiving'];
                        $modelReferral->cro_receiving = $request['receivedBy'];
                        $modelReferral->created_at_local = date('Y-m-d H:i:s',$request['created_at']);
                        $modelReferral->create_time = date('Y-m-d H:i:s');
                        $modelReferral->update_time = date('Y-m-d H:i:s');
                        $modelReferral->bid = $request['bid'];
                        $modelReferral->pstc_id = $request['pstc_id'];
                        if($modelReferral->save(false)){
                            $referralId = $modelReferral->referral_id;
                            $referralSave = 1; //flags that the request has been save in this transaction
                            //iterates the sample and save them
                            if(count($samples) > 0){
                                foreach ($samples as $sample) {
                                    $modelSample = new Sample;
                                    $modelSample->referral_id = $modelReferral->referral_id;
                                    $modelSample->local_sample_id = $sample['sample_id'];
                                    $modelSample->local_request_id = $sample['request_id'];
                                    $modelSample->receiving_agency_id = $sample['rstl_id'];
                                    //$modelSample->package_id = $sample['package_id'];
                                    $modelSample->sample_type_id = $sample['sampletype_id'];
                                    $modelSample->sample_code = $sample['sample_code'];
                                    $modelSample->sample_name = $sample['samplename'];
                                    $modelSample->description = $sample['description'];
                                    $modelSample->customer_description = $sample['customer_description'];
                                    $modelSample->sampling_date = $sample['sampling_date'];
                                    $modelSample->remarks = $sample['remarks'];
                                    $modelSample->sample_month = $sample['sample_month'];
                                    $modelSample->sample_year = $sample['sample_year'];
                                    $modelSample->active = $sample['active'];
                                    $modelSample->created_at = date('Y-m-d H:i:s');
                                    $modelSample->updated_at = date('Y-m-d H:i:s');

                                    if($modelSample->save(false)){
                                        $sampleSave = 1; //flags that 
                                        //now iterates the analyses
                                        //foreach to save every test under a sample 
                                    foreach ($sample['analyses'] as $analysis) {
                                        $modelAnalysis = new Analysis;
                                        $modelAnalysis->sample_id = $modelSample->sample_id;
                                        $modelAnalysis->local_analysis_id = $analysis['analysis_id'];
                                        $modelAnalysis->local_sample_id = $analysis['sample_id'];
                                        $modelAnalysis->date_analysis = $analysis['date_analysis'];
                                        $modelAnalysis->agency_id = $analysis['rstl_id'];
                                        $modelAnalysis->package_id = $analysis['package_id'];
                                        $modelAnalysis->testname_id = $analysis['test_id'];
                                        $modelAnalysis->methodreference_id = $analysis['methodref_id'];
                                        $modelAnalysis->analysis_fee = $analysis['fee'];
                                        $modelAnalysis->cancelled = $analysis['cancelled'];
                                        $modelAnalysis->status = 1;
                                        $modelAnalysis->cancelled = 0;
                                        $modelAnalysis->is_package = $analysis['is_package'];
                                        $modelAnalysis->type_fee_id = $analysis['type_fee_id'];
                                        $modelAnalysis->created_at = date('Y-m-d H:i:s');
                                        $modelAnalysis->updated_at = date('Y-m-d H:i:s');

                                        if($modelAnalysis->save()){
                                            $analysisSave = 1;
                                        }else{
                                            $analysisSave = 0;
                                            $transaction->rollBack();
                                            break;
                                        }
                                    }

                                    }else{
                                        $sampleSave = 0; //flags the a sample was not save therefore rollsback
                                        //else for the if sample was save
                                        $transaction->rollBack();
                                        break;
                                    }
                                }
                            }
                        }
                        else{
                            //esle for "if request was saved"
                            $transaction->rollBack();
                        }
                }
                
            }

            // return ['referral'=>$referralSave,'sample'=>$sampleSave,'analysis'=>$analysisSave];// use to debug , gives off the reason 

            //after all of the procedure above, determines waether to send notification or not
            if($referralSave == 1 && $sampleSave == 1 && $analysisSave == 1){
                $transaction->commit();
                $return = 1;

            } else {
                $transaction->rollBack();
                $return = 0;
                $referralId =0;
            }
        }
        
        return ['response'=>$return,'referral_id'=>$referralId];
    }

    //returns boolean, notifies the referred agency about the referral    
    public function actionNotify(){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        if(count(\Yii::$app->request->post('notice_details')) > 0){
            $details = \Yii::$app->request->post('notice_details');

            $notification = new Notification;
            $notification->referral_id = (int) $details['referral_id'];
            $notification->notification_type_id = 1;
            $notification->sender_id = (int) $details['sender_id'];
            $notification->recipient_id = (int) $details['recipient_id'];
            $notification->sender_user_id = (int) $details['sender_user_id'];
            $notification->sender_name = $details['sender_name'];
            $notification->remarks = $details['remarks'];
            $notification->notification_date = date('Y-m-d H:i:s');
            if($notification->save(false)){
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    //returns boolean, notifies the referred agency about the referral    
    public function actionNotifysent(){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        if(count(\Yii::$app->request->post('notice_details')) > 0){
            $details = \Yii::$app->request->post('notice_details');

            $notification = new Notification;
            $notification->referral_id = (int) $details['referral_id'];
            $notification->notification_type_id = 3;
            $notification->sender_id = (int) $details['sender_id'];
            $notification->recipient_id = (int) $details['recipient_id'];
            $notification->sender_user_id = (int) $details['sender_user_id'];
            $notification->sender_name = $details['sender_name'];
            $notification->remarks = $details['remarks'];
            $notification->notification_date = date('Y-m-d H:i:s');
            if($notification->save(false)){
                //respond the notification 2 
                $prevnotif = Notification::find()->where(['referral_id'=>$details['referral_id'],'notification_type_id'=>2])->one();
                if($prevnotif){
                    $prevnotif->responded=1;
                    $prevnotif->save(false);
                }
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    //returns number of notification
    public function actionCountnotification($rstl_id)
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        if($rstl_id > 0){
            $model = new Notification;
            try {
                $query = $model::find()
                    ->where('recipient_id =:recipientId', [':recipientId'=>$rstl_id])
                    ->andWhere('responded =:responded',[':responded'=>0]);

                $notificationCount = $query->count();
                $notification = $query->orderBy('notification_date DESC')->all();

                if($notificationCount > 0){
                    return ['notification'=>$notification,'count_notification'=>$notificationCount];
                } else {
                    return ['notification'=>[],'count_notification'=>0];
                }
                
            } catch (Exception $e) {
                throw new \yii\web\HttpException(500, 'Internal server error');
            }
        } else {
            throw new \yii\web\HttpException(400, 'No records found');
        }
    }

    public function actionCountbidnotification($rstl_id)
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        if($rstl_id > 0){
            $model = new Bidnotification;
            try {
                $query = $model::find()
                    ->where('recipient_agency_id =:recipientId', [':recipientId'=>$rstl_id])
                    ->andWhere('seen =:seen',[':seen'=>0]);
                
                $bidnotificationCount = $query->count();
                $bidnotification = $query->orderBy('posted_at DESC')->all();
                
                if($bidnotificationCount > 0){
                    return ['bidnotification'=>$bidnotification,'count_bidnotification'=>$bidnotificationCount];
                } else {
                    return ['bidnotification'=>null,'count_bidnotification'=>0];
                }
                
            } catch (Exception $e) {
                throw new \yii\web\HttpException(500, 'Internal server error');
            }
        } else {
            throw new \yii\web\HttpException(400, 'No records found');
        }
    }

    public function actionListall($rstl_id)
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        $model = new Notification;
        try {
            
            $query = $model::find()
                ->joinWith(['referral'],false)
                ->where('recipient_id =:recipientId', [':recipientId'=>$rstl_id])
                ->andWhere(['!=', 'tbl_notification.referral_id', 0]);
            
            $notificationCount = $query->count();
            $notification = $query->orderBy('notification_date DESC')->all();
            
            if($notificationCount > 0){
                return ['notification'=>$notification,'count_notification'=>$notificationCount];
            } else {
                return ['notification'=>[],'count_notification'=>0];
            }
            
        } catch (Exception $e) {
            return ['notification'=>[],'count_notification'=>0];
        }
        
    }


    public function actionReferral_one($referral_id,$rstl_id)
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        try {
            $model = new Referral;
            
            $referral = $model::find()
                ->where('referral_id =:referralId', [':referralId'=>$referral_id])
                ->andWhere('receiving_agency_id =:receivingAgency OR testing_agency_id =:testingAgency', [':receivingAgency'=>$rstl_id,':testingAgency'=>$rstl_id])
                ->asArray()->one();
            
                return $referral;
        } catch (Exception $e) {
            throw new \yii\web\HttpException(500, 'Internal server error');
        }
    }

    public function actionCheckowner($referral_id,$sender_id)
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        if($referral_id > 0 && $sender_id > 0){
            $check = Referral::find()
                ->where('referral_id =:referralId', [':referralId'=>$referral_id])
                ->andWhere('receiving_agency_id =:senderId', [':senderId'=>$sender_id])
                ->count();
            
            if($check > 0)
                return 1;
            
            return 0;
        }

        return 0;
        
    }


    public function actionViewdetail($referral_id,$rstl_id)
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        
        if(!empty($referral_id) && !empty($rstl_id)){
            // $referralId = (int) $getrequest->get('referral_id');
            // $recipientId = (int) $getrequest->get('rstl_id');
            // $senderId = (int) $getrequest->get('rstl_id');
            
            $checkNotified = $this->checkNotified($referral_id,$rstl_id);
            $checkOwner = $this->actionCheckowner($referral_id,$rstl_id);
            
            if($checkNotified > 0 || $checkOwner > 0){
                $modelReferral = new Referral;
                $modelSample = new Sample;
                $modelAnalysis = new Analysis;
                $modelCustomer = new Customer;
                $modelNotification= new Notification;
                

                $referral = $modelReferral::find()
                    ->where('referral_id =:referralId', [':referralId'=>$referral_id])
                    ->one(); 
                
                $samples = $modelSample::find()
                    ->where('referral_id =:referralId', [':referralId'=>$referral_id])
                    ->asArray()
                    ->all();

                    
                $sampleIds = implode(',', array_map(function ($data) {
                    return $data['sample_id'];
                }, $samples));
                    
                $analyses = $modelAnalysis::find()
                    ->select('tbl_analysis.*,tbl_sample.sample_name,tbl_sample.sample_code,tbl_testname.test_name,tbl_methodreference.method,tbl_methodreference.reference')
                    ->joinWith(['sample','testname','methodreference'],false)
                    ->where(['in', 'tbl_analysis.sample_id', explode(',',$sampleIds)])
                    //->andWhere(['cancelled'=>0])
                    ->asArray()
                    ->all();
                    
                $customer = $modelCustomer::find()
                    ->where('customer_id =:customerId', [':customerId'=>$referral->customer_id])
                    ->one();
                    
                $notification = $modelNotification::find()
                    ->where('referral_id =:referralId', [':referralId'=>$referral_id])
                    ->all();
                
                $data = ['request_data'=>$referral,'sample_data'=>$samples,'analysis_data'=>$analyses,'customer_data'=>$customer,'notification_data'=>$notification];
                
                return $data;
                
            } else {
                //return "Your agency doesn't appear notified!";
                return 0;
            }
        } else {
            throw new \yii\web\HttpException(400, 'No records found');
        }
    }

    //gets a single record from notification
    public function actionNotification_one($notificationId,$rstlId)
    {
        $notification = Notification::find()
            ->where('notification_id =:notificationId', [':notificationId'=>$notificationId])
            ->andWhere('recipient_id =:recipientId', [':recipientId'=>$rstlId])
            ->asArray()->one();

        return $notification;
    }

    //salvaged code from STG in the eulimsapi
    //send referral
    //return 0 - fail to update
    //return 1 - update successful
    //return 2 - no data
    public function actionSendreferral()
    {
        //date_default_timezone_set('Asia/Manila'); //set timezone to Asia/Manila
        $referralSave = 0;
        $sampleSave = 0;
        $return = 0;
        $referralId = "";
        $estimatedDue = "";
        if(count(\Yii::$app->request->post()) > 0){

            $connection= \Yii::$app->referraldb;
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
            $transaction = $connection->beginTransaction();
            
            $request = \Yii::$app->request->post('request_data');
            $samples = \Yii::$app->request->post('sample_data');
            //$analyses = Yii::$app->request->post('analysis_data');
            
            $checkReferral = $this->checkReferral($request['request_id'],$request['rstl_id']);
            
            if($checkReferral == 0){
                //referral not found
                $return = 2;
            } else {
                try {
                    //echo count($request);
                    if(count($request) > 0){
                        $referral = Referral::find()->where('local_request_id =:requestId AND receiving_agency_id =:receivingId',[':requestId'=>$request['request_id'],':receivingId'=>$request['rstl_id']])->one();
                        
                        $notification = $this->getNotificationDetails($referral->referral_id,$request['rstl_id'],\Yii::$app->request->post('agency_id'));
                        $referral->referral_code = $request['request_ref_num'];
                        $referral->referral_date_time = $request['request_datetime'];
                        $referral->testing_agency_id = (int) \Yii::$app->request->post('agency_id');
                        $referral->sample_received_date = $request['sample_received_date'];
                        // $referral->customer_id = $request['customer_id'];
                        $referral->payment_type_id = $request['payment_type_id'];
                        $referral->modeofrelease_id = $request['modeofrelease_ids'];
                        $referral->purpose_id = $request['purpose_id'];
                        $referral->discount_id = $request['discount_id'];
                        $referral->discount_rate = $request['discount'];
                        $referral->total_fee = $request['total'];
                        $referral->report_due = date('Y-m-d',strtotime($notification['remarks']));
                        $referral->conforme = $request['conforme'];
                        $referral->receiving_user_id = $request['user_id_receiving'];
                        $referral->cro_receiving = $request['receivedBy'];
                        $referral->testing_user_id = $notification['sender_user_id'];
                        $referral->cro_testing = $notification['sender_name'];
                        $referral->update_time = date('Y-m-d H:i:s');
                        if($referral->save(false))
                        {
                            if(count($samples) > 0)
                            {
                                foreach($samples as $sample){       
                                    $sampleData = Sample::find()->where('referral_id =:referralId AND local_request_id =:requestId AND local_sample_id =:sampleId AND receiving_agency_id =:receivingAgency',[':referralId'=>$referral->referral_id,':requestId'=>$request['request_id'],':sampleId'=>$sample['sample_id'],':receivingAgency'=>$request['rstl_id']])->one();
                                    
                                    $sampleData->sample_code = $sample['sample_code'];
                                    $sampleData->sample_month = $sample['sample_month'];
                                    $sampleData->sample_year = $sample['sample_year'];
                                    $sampleData->updated_at = date('Y-m-d H:i:s');
                                    if($sampleData->save(false) !== false){
                                        $sampleSave = 1;
                                    } else {
                                        $transaction->rollBack();
                                        $sampleSave = 0;
                                    }
                                }
                            } else {
                                $transaction->rollBack();
                                $sampleSave = 0;
                            }
                            $referralSave = 1;
                            $referralId = $referral->referral_id;
                            $estimatedDue = $referral->report_due;
                        }
                        else {
                            $transaction->rollBack();
                            $referralSave = 0;
                        }
                    } else {
                        //return "No referral data!";
                        $return = 2;
                    }
                    if($referralSave == 1 && $sampleSave == 1){

                        //find and update the pstc if meron

                        $pstc = Pstcrequest::findOne($referral->pstc_id);
                        if($pstc){
                            $pstc->referral_id = $referral->referral_id;
                            $pstc->save(false);
                        }


                        $transaction->commit();
                        $return = 1;
                    } else {
                        $transaction->rollBack();
                        $return = 0;
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                } catch (\Throwable $e) {
                   $transaction->rollBack();
                }
            }
        } else {
            //return "No data posted!";
            $return = 2;
        }
        
        return ['response'=>$return,'referral_id'=>$referralId,'estimated_due'=>$estimatedDue];
    }

    //salvaged code from STG from attactment controller
    public function actionShow_upload()
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        set_time_limit(120);
        
        if(\Yii::$app->request->get('referral_id') > 0 && \Yii::$app->request->get('rstl_id') > 0 && \Yii::$app->request->get('type') > 0){
            try {
                $referralId = (int) \Yii::$app->request->get('referral_id');
                $rstlId = (int) \Yii::$app->request->get('rstl_id');
               // $type = (int) \Yii::$app->request->get('type');
                 //$type =2;
                $attachment = Attachment::find()
                    ->joinWith('referral',false)
                    ->where('tbl_attachment.referral_id =:referralId AND attachment_type =:attachmentType', [':referralId'=>$referralId,':attachmentType'=>$type])
                    ->andWhere('receiving_agency_id =:receivingAgency OR testing_agency_id =:testingAgency', [':receivingAgency'=>$rstlId,':testingAgency'=>$rstlId])
                    ->asArray()->all();
                
                if($attachment){
                    return $attachment;
                } else {
                    return 0;
                }
            } catch (Exception $e) {
                throw new \yii\web\HttpException(500, 'Internal server error');
            }
        } else {
            throw new \yii\web\HttpException(400, 'No records found');
        }
    }

    //updated by EGG 03/24/21
    public function actionDownload()
    {
        set_time_limit(120);
        
        if(\Yii::$app->request->get('referral_id') > 0 && \Yii::$app->request->get('file') > 0 && \Yii::$app->request->get('rstl_id') > 0){
            $referralId = (int) \Yii::$app->request->get('referral_id');
            $rstlId = (int) \Yii::$app->request->get('rstl_id');
            $fileId = (int) \Yii::$app->request->get('file');
            /*return $attachment = Attachment::find()
                ->select('tbl_attachment.*,tbl_referral.referral_code')
                ->joinWith('referral',false)
                ->where('tbl_attachment.referral_id =:referralId AND attachment_id =:fileId', [':referralId'=>$referralId,':fileId'=>$fileId])
               // ->andWhere('receiving_agency_id =:receivingAgency OR testing_agency_id =:testingAgency', [':receivingAgency'=>$rstlId,':testingAgency'=>$rstlId])
                ->asArray()->one();*/
			$attachment = Attachment::find()
                            ->where('referral_id =:referral_id', [':referral_id'=>$referralId])
							->andWhere('attachment_id=:fileId', [':fileId'=>$fileId])
                            ->one();
            
            $path = $_SERVER['DOCUMENT_ROOT'].'/uploads';

            $file = $path . "/referral/".$attachment['filename'];


            // return $path = \Yii\helpers\Url::to("uploads/referral/".$attachment['referral_code'].'/'.$attachment['filename'];

            if(file_exists($file)) {
                //return Yii::$app->response->sendFile($file);
//return Yii::$app->response->sendFile($file);
				return $file;
            } else {
                return 0;
            }
        }
    }

    //protecetd function was salvaged from STG
    protected function checkReferral($requestId,$rstlId)
    {
        $model = new Referral;
        
        if($requestId > 0 && $rstlId > 0){
            $referral = $model::find()
            ->select('referral_id')
            ->where('local_request_id =:localrequestId', [':localrequestId'=>$requestId])
            ->andWhere('receiving_agency_id =:rstlId', [':rstlId'=>$rstlId])
            ->one();
            
            if(count($referral)>0){
                $referralId = $referral->referral_id;
            } else {
                $referralId = 0;
            }
        } else {
            $referralId = 0;
        }
        
        return $referralId;
    }

    //salvaged data from STG
    //for saving eulims local
    public function actionGetreferraldetail($referral_id,$rstl_id){
        $getrequest = \Yii::$app->request;
        if($referral_id && $rstl_id){
            
            $checkTestingLab = $this->checkTestingLab($referral_id,$rstl_id);
            $checkOwner = $this->actionCheckowner($referral_id,$rstl_id);
            if($checkTestingLab > 0 || $checkOwner > 0){
                
                $referral = Referral::find()
                    ->where('referral_id =:referralId', [':referralId'=>$referral_id])
                    ->one();
                
                $samples_analyses = Sample::find()
                    ->joinWith(['analyses','analyses.testname','analyses.methodreference'])
                    ->where('referral_id =:referralId', [':referralId'=>$referral_id])
                    ->asArray()
                    ->all();
                    
                $data = ['request_data'=>$referral,'sample_analysis_data'=>$samples_analyses,'customer_data'=>true];
                
                return $data;
                
            } else {
                //return "Your agency doesn't appear notified!";
                return 0;
            }
        } else {
            throw new \yii\web\HttpException(400, 'No records found');
        }
    }

    //salvaged from STG but changed few ~BTC
    //update samplecode
    public function actionUpdatesamplecode()
    {
        if(count(\Yii::$app->request->post()) > 0){
            $connection= \Yii::$app->referraldb;
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
            $transaction = $connection->beginTransaction();
            
            $samples = \Yii::$app->request->post('sample_data');
            $receivingAgencyId = (int) \Yii::$app->request->post('receiving_agency');
            $referralId = (int) \Yii::$app->request->post('referral_id');
            $notificationId=(int) \Yii::$app->request->post('notificationId');
            if($referralId > 0 && $receivingAgencyId > 0){
                $checkReferral = Referral::find()
                    ->where('referral_id =:referralId', [':referralId'=>$referralId])
                    ->andWhere('receiving_agency_id =:rstlId', [':rstlId'=>$receivingAgencyId])
                    ->count();
            } else {
                $checkReferral = 0;
            }
            
            if($checkReferral == 0){
                $return = 0;
            } else {
                if(count($samples) > 0)
                {
                    foreach($samples as $sample){
                        
                        if($sample['sample_id'] > 0){
                            $sampleData = Sample::find()->where('referral_id =:referralId AND sample_id =:sampleId AND receiving_agency_id =:receivingAgency',[':referralId'=>$referralId,':sampleId'=>$sample['sample_id'],':receivingAgency'=>$receivingAgencyId])->one();
                        
                            $sampleData->sample_code = $sample['sample_code'];
                            $sampleData->sample_month = $sample['sample_month'];
                            $sampleData->sample_year = $sample['sample_year'];
                            $sampleData->updated_at = date('Y-m-d H:i:s');
                            //if($sampleData->save(false) !== false){
                            if($sampleData->save(false)){
                                $sampleSave = 1;
                            } else {
                                $transaction->rollBack();
                                $sampleSave = 0;
                            }
                        } else {
                            $transaction->rollBack();
                            $return = 0;
                        }
                    }
                    if($sampleSave == 1){
                        //update/respond the notification
                        $notice = Notification::find()->where('notification_id =:notificationId AND notification_type_id =:notificationType',[':notificationId'=>$notificationId,':notificationType'=>3])->one();
                        $notice->responded = 1;
                        $notice->date_responded = date('Y-m-d H:i:s');
                        if($notice->save(false)){
                            $transaction->commit();
                            $return = 1;
                        }else{
                            $transaction->rollBack();
                            $return = 0;
                        }
                    } else {
                        $transaction->rollBack();
                        $return = 0;
                    }
                } else {
                    $transaction->rollBack();
                    $return = 0;
                }
            }
        } else {
            $return = 0;
        }
        return $return;
    }

    //salvaged from stg
    //get sample code
    public function actionGetsamplecode()
    {
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;
        $getrequest = \Yii::$app->request;
        
        if(!empty($getrequest->get('request_id')) && !empty($getrequest->get('rstl_id'))){
            $requestId = (int) $getrequest->get('request_id');
            $senderId = (int) $getrequest->get('rstl_id');
            
            if($requestId > 0){
                $owner = Referral::find()
                    ->where('local_request_id =:requestId', [':requestId'=>$requestId])
                    ->andWhere('receiving_agency_id =:senderId', [':senderId'=>$senderId])
                    ->count();
            } else {
                $owner = 0;
            }
            
            if($owner){
                $modelSample = new Sample;

                $referral = Referral::find()
                    ->where('local_request_id =:requestId AND receiving_agency_id =:receivingAgencyId', [':requestId'=>$requestId,':receivingAgencyId'=>$senderId])
                    ->one();
                
                $samples_analyses = Sample::find()
                    ->joinWith(['analyses'],true)
                    ->where('local_request_id =:requestId AND receiving_agency_id =:receivingAgencyId', [':requestId'=>$requestId,':receivingAgencyId'=>$senderId])
                    ->asArray()
                    ->all();
                    
                $data = ['referral_data'=>$referral,'sample_analysis_data'=>$samples_analyses];
                
                return $data;
                
            } else {
                return 0;
            }
        } else {
            throw new \yii\web\HttpException(400, 'No records found');
        }
    }

    //Updated by EGG Please check changes before updating this controller 03/23/21
    public function actionUpload_deposit()
    {
        set_time_limit(120);
        
        if(isset($_FILES['file_data']['tmp_name'])){
            $connection= \Yii::$app->referraldb;
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
            $transaction = $connection->beginTransaction();
            
            if(count(\Yii::$app->request->post('uploader_data')) > 0){
                $upload = json_decode(\Yii::$app->request->post('uploader_data'));
                
                $model = new Attachment;
                $model->filename = $_FILES['file_data']['name'];
                $model->attachment_type = 1;
                $model->referral_id = (int) $upload->referral_id;
                $model->upload_date = date('Y-m-d H:i:s');
                $model->uploadedby_user_id = (int) $upload->user_id;
                $model->uploadedby_name = $upload->uploader;
                if($model->save(false)){
                    $dir = $upload->referral_code;
                    $path = "uploads/referral/";
                
                  
					if(move_uploaded_file($_FILES['file_data']['tmp_name'],$path.$_FILES['file_data']['name'])){
						$transaction->commit();
						$return = 1;
					} else {
						$transaction->rollBack();
						$return = 2;
					}
                     
                } else {
                    $transaction->rollBack();
                    $return = 2;
                }
            } else {
                $transaction->rollBack();
                $return = 0;
            }
        } else {
            $return = 0;
        }
        return $return;
    }
	
	 public function actionUpload_or()
    {
        set_time_limit(120);
        
        if(isset($_FILES['file_data']['tmp_name'])){
            $connection= \Yii::$app->referraldb;
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
            $transaction = $connection->beginTransaction();
            
            if(count(\Yii::$app->request->post('uploader_data')) > 0){
                $upload = json_decode(\Yii::$app->request->post('uploader_data'));
                
                $model = new Attachment;
                $model->filename = $_FILES['file_data']['name'];
                $model->attachment_type = 2; //OR
                $model->referral_id = (int) $upload->referral_id;
                $model->upload_date = date('Y-m-d H:i:s');
                $model->uploadedby_user_id = (int) $upload->user_id;
                $model->uploadedby_name = $upload->uploader;
                if($model->save(false)){
                    $dir = $upload->referral_code;
                    $path = "uploads/referral/";
                
                  
					if(move_uploaded_file($_FILES['file_data']['tmp_name'],$path.$_FILES['file_data']['name'])){
						$transaction->commit();
						$return = 1;
					} else {
						$transaction->rollBack();
						$return = 2;
					}
                     
                } else {
                    $transaction->rollBack();
                    $return = 2;
                }
            } else {
                $transaction->rollBack();
                $return = 0;
            }
        } else {
            $return = 0;
        }
        return $return;
    }


    /** ACCOMPLISHMENT REPORT **/
    public function actionAccomplishmentreportall($rstl_id,$fromDate,$toDate){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        $modelReferral = Referralextend::find()
                ->where('(testing_agency_id =:testingAgencyId OR receiving_agency_id =:receivingAgencyId) AND cancelled =:cancel AND DATE_FORMAT(`referral_date_time`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND testing_agency_id != 0', [':testingAgencyId'=>$rstl_id,':receivingAgencyId'=>$rstlId,':cancel'=>0,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate])
                ->groupBy(['DATE_FORMAT(referral_date_time, "%Y-%m")'])
                ->orderBy([
                    'DATE_FORMAT(referral_date_time, "%Y")' => SORT_DESC,
                    'DATE_FORMAT(referral_date_time, "%m")' => SORT_ASC,
                ]);
        return $modelReferral;
    }

    public function actionAccomplishmentreportselected($rstl_id,$lab_id,$fromDate,$toDate){
        \Yii::$app->response->format= \yii\web\Response::FORMAT_JSON;

        $modelReferral = Referralextend::find()
                ->where('(testing_agency_id =:testingAgencyId OR receiving_agency_id =:receivingAgencyId) AND cancelled =:cancel AND lab_id = :labId AND DATE_FORMAT(`referral_date_time`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND testing_agency_id != 0', [':testingAgencyId'=>$rstl_id,':receivingAgencyId'=>$rstl_id,':cancel'=>0,':labId'=>$lab_id,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate])
                ->groupBy(['DATE_FORMAT(referral_date_time, "%Y-%m")'])
                ->orderBy([
                    'DATE_FORMAT(referral_date_time, "%Y")' => SORT_DESC,
                    'DATE_FORMAT(referral_date_time, "%m")' => SORT_ASC,
                ]);

        return $modelReferral;
    }

    //salvaged from STG
    protected function checkTestingLab($referralId,$recipientId)
    {
        if($referralId > 0 && $recipientId > 0){
            $check = Notification::find()
                ->where('referral_id =:referralId', [':referralId'=>$referralId])
                ->andWhere('recipient_id =:recipientId', [':recipientId'=>$recipientId])
                ->andWhere('notification_type_id =:notice', [':notice'=>3])
                ->count();
            
            if($check > 0){
                $status = 1;
            } else {
                $status = 0;
            }
            return $status;
        } else {
            return 0;
        }
    }

    //salvaged from STG
    protected function checkNotified($referralId,$recipientId)
    {
        if($referralId > 0 && $recipientId > 0){
            $check = Notification::find()
                ->where('referral_id =:referralId', [':referralId'=>$referralId])
                ->andWhere('recipient_id =:recipientId', [':recipientId'=>$recipientId])
                ->andWhere('notification_type_id =:notice', [':notice'=>1])
                ->count();
            
            if($check > 0){
                $status = 1;
            } else {
                $status = 0;
            }
            return $status;
        } else {
            return 0;
        }
    }

    //salvaged code from STG
    //return referral notification estimated due date
    protected function getNotificationDetails($referralId,$rstlId,$senderId)
    {   
        if((int) $referralId > 0 && (int) $rstlId > 0 && (int) $senderId > 0){
            try {
                $notification = Notification::find()
                    ->where('referral_id =:referralId AND sender_id =:sender AND recipient_id =:recipient AND notification_type_id =:notificationType',[':referralId'=>$referralId,':sender'=>$senderId,':recipient'=>$rstlId,':notificationType'=>2])
                    ->asArray()->one();
                
                if($notification){
                    return $notification;
                } else {
                    return 0;
                }
                
            } catch (Exception $e) {
                throw new \yii\web\HttpException(500, 'Internal server error');
            }
        } else {
            throw new \yii\web\HttpException(400, 'No records found');
        }
    }
}