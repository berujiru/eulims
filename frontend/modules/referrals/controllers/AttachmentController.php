<?php

namespace frontend\modules\referrals\controllers;

use Yii;
use common\models\referral\Attachment;
use common\models\referral\AttachmentSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\helpers\Url;
use common\models\lab\exRequestreferral;
use common\components\ReferralFunctions;
//use linslin\yii2\curl;
use common\models\referral\Referral;
use common\components\ReferralComponent;
use yii\helpers\Json;
//use linslin\yii2\curl\Curl;
use common\models\system\LoginForm;
use linslin\yii2\curl;
/**
 * AttachmentController implements the CRUD actions for Attachment model.
 */
class AttachmentController extends Controller
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
     * Lists all Attachment models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AttachmentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Attachment model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Attachment model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Attachment();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->attachment_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Attachment model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->attachment_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Attachment model.
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
     * Finds the Attachment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Attachment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Attachment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
	//upload updated,Deposit Slip EGG 03/18/21
	public function actionUpload_deposit()
    {
        set_time_limit(120);

        if(Yii::$app->request->get('referral_id')){
            $referralId = (int) Yii::$app->request->get('referral_id');
        } else {
            Yii::$app->session->setFlash('error', "Referral ID not valid!");
            return $this->redirect(['/lab/request']);
        }

        $requestId = (int) Yii::$app->request->get('request_id');

        $request = $this->findRequest($requestId);

        $model = new Attachment();
		 if($model->load(Yii::$app->request->post())){
            $model->filename = UploadedFile::getInstances($model,'filename');
            $model->referral_id = $referralId;
            if($model->filename){
                $ch = curl_init();
                $referralCode = $request->request_ref_num;
                foreach ($model->filename as $filename) {
                    $file = $referralCode."_".date('YmdHis').".".$filename->extension;
                    $file_data = curl_file_create($filename->tempName,$filename->type,$file);

                    $mi = !empty(Yii::$app->user->identity->profile->middleinitial) ? " ".substr(Yii::$app->user->identity->profile->middleinitial, 0, 1).". " : " "; 
                    $uploaderName = Yii::$app->user->identity->profile->firstname.$mi.Yii::$app->user->identity->profile->lastname;

                    $uploader_data = [
                        'file_name' => $referralCode."_".date('YmdHis').".".$filename->extension,
                        'referral_id' => $referralId,
                        'referral_code' => $referralCode,
                        'user_id' => Yii::$app->user->identity->profile->user_id,
                        'uploader' => $uploaderName,
                    ];
                    $referralUrl='https://eulims.onelab.ph/api/restreferral/upload_deposit';
                   
					
                    $data = ['file_data'=>$file_data,'uploader_data'=>json_encode($uploader_data)];

                    //hardcoded curl since the extension doesn't support create file
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_URL, $referralUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                    $response = curl_exec($ch); 
                    if($response == 1){
                        Yii::$app->session->setFlash('success', "Deposit slip successfully uploaded.");
                        return $this->redirect(['/lab/request/view','id'=>$requestId]);
                    } elseif($response == 0) {
                        Yii::$app->session->setFlash('error', "Attachment invalid!");
                        return $this->redirect(['/lab/request/view','id'=>$requestId]);
                    } else {
                        Yii::$app->session->setFlash('error', "Can't upload attachment!");
                        return $this->redirect(['/lab/request/view','id'=>$requestId]);
                    }
                }
            } else {
                Yii::$app->session->setFlash('error', "Not valid upload attachment!");
                return $this->redirect(['/lab/request/view','id'=>$requestId]);
            }
        }
		
		
        if(Yii::$app->request->isAjax) {
            return $this->renderAjax('_formUploadDeposit', [
                'model' => $model,
            ]);
        }
    }
	//upload Official Receipt
    public function actionUpload_or()
    {
        set_time_limit(120);

        if(Yii::$app->request->get('referral_id')){
            $referralId = (int) Yii::$app->request->get('referral_id');
        } else {
            Yii::$app->session->setFlash('error', "Referral ID not valid!");
            return $this->redirect(['/lab/request']);
        }

        $requestId = (int) Yii::$app->request->get('request_id');
        $request = $this->findRequest($requestId);


        $model = new Attachment();
        if($model->load(Yii::$app->request->post())){
            $model->filename = UploadedFile::getInstances($model,'filename');
            $model->referral_id = $referralId;
            if($model->filename){
                $ch = curl_init();
                $referralCode = $request->request_ref_num;
                foreach ($model->filename as $filename) {
                    $file = $referralCode."_".date('YmdHis').".".$filename->extension;
                    $file_data = curl_file_create($filename->tempName,$filename->type,$file);

                    $mi = !empty(Yii::$app->user->identity->profile->middleinitial) ? " ".substr(Yii::$app->user->identity->profile->middleinitial, 0, 1).". " : " "; 
                    $uploaderName = Yii::$app->user->identity->profile->firstname.$mi.Yii::$app->user->identity->profile->lastname;

                    $uploader_data = [
                        'file_name' => $referralCode."_".date('YmdHis').".".$filename->extension,
                        'referral_id' => $referralId,
                        'referral_code' => $referralCode,
                        'user_id' => Yii::$app->user->identity->profile->user_id,
                        'uploader' => $uploaderName,
                    ];
                    $referralUrl='https://eulimsapi.onelab.ph/api/web/referral/attachments/upload_or';
                    //$referralUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/attachments/upload_or';

                    $data = ['file_data'=>$file_data,'uploader_data'=>json_encode($uploader_data)];

                    //hardcoded curl since the extension doesn't support create file
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_URL, $referralUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                    $response = curl_exec($ch);

                    //print_r($response);
                    //exit;

                    if($response == 1){
                        Yii::$app->session->setFlash('success', "Official receipt successfully uploaded.");
                        return $this->redirect(['/lab/request/view','id'=>$requestId]);
                    } elseif($response == 0) {
                        Yii::$app->session->setFlash('error', "Attachment invalid!");
                        return $this->redirect(['/lab/request/view','id'=>$requestId]);
                    } else {
                        Yii::$app->session->setFlash('error', "Can't upload attachment!");
                        return $this->redirect(['/lab/request/view','id'=>$requestId]);
                    }
                }
            } else {
                Yii::$app->session->setFlash('error', "Not valid upload attachment!");
                return $this->redirect(['/lab/request/view','id'=>$requestId]);
            }
        }
        if(Yii::$app->request->isAjax) {
            return $this->renderAjax('_formUploadOR', [
                'model' => $model,
            ]);
        }
    }
//upload Test Result
    public function actionUpload_result($referralid)
    {
        //echo $referralid;
        //exit;
        $refcomponent = new ReferralComponent();
        $rstlid= Yii::$app->user->identity->profile->rstl_id;
        if(!empty($referralid)){
            $referralId = (int) $referralid;
            // $referral = $this->findReferral($referralId);
            $referral=json_decode($refcomponent->getReferralOne($referralid, $rstlid),true);
            /*echo"<pre>";
            echo $referral->referral_code;
            echo"</pre>";
            exit;*/
            
        } else {
            Yii::$app->session->setFlash('error', "Referral ID not valid!");
            return $this->redirect(['/referrals/referral']);
        } 

        //set_time_limit(120);

        //if(Yii::$app->request->get('referral_id')){
        //    $referralId = (int) Yii::$app->request->get('referral_id');
        //} else {
        //    Yii::$app->session->setFlash('error', "Referral ID not valid!");
        //    return $this->redirect(['/lab/request']);
        //}

        //$requestId = (int) Yii::$app->request->get('request_id');
        //$request = $this->findRequest($requestId);

        $model = new Attachment();
        if($model->load(Yii::$app->request->post())){

            //print_r(Yii::$app->request->post());

            //exit;

            //$files_allowed =  array('pdf','png' ,'jpg', 'jpeg');
            //$ext = pathinfo($_FILES['file_data']['name'], PATHINFO_EXTENSION);

            $model->filename = UploadedFile::getInstances($model,'filename');
            $model->referral_id = $referralId;
            if($model->filename){
                $ch = curl_init();
                $referralCode = $referral['referral_code'];
                foreach ($model->filename as $filename) {
                    $file = $referralCode."_".date('YmdHis').".".$filename->extension;
                    $file_data = curl_file_create($filename->tempName,$filename->type,$file);

                    $mi = !empty(Yii::$app->user->identity->profile->middleinitial) ? " ".substr(Yii::$app->user->identity->profile->middleinitial, 0, 1).". " : " "; 
                    $uploaderName = Yii::$app->user->identity->profile->firstname.$mi.Yii::$app->user->identity->profile->lastname;

                    $uploader_data = [
                        'file_name' => $referralCode."_".date('YmdHis').".".$filename->extension,
                        'referral_id' => $referralId,
                        'referral_code' => $referralCode,
                        'user_id' => Yii::$app->user->identity->profile->user_id,
                        'uploader' => $uploaderName,
                    ];
                    $referralUrl='https://eulimsapi.onelab.ph/api/web/referral/attachments/upload_result';
                    //$referralUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/attachments/upload_result';

                    $data = ['file_data'=>$file_data,'uploader_data'=>json_encode($uploader_data)];

                    //hardcoded curl since the linslin\yii2\curl extension doesn't support create file
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_URL, $referralUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                    $response = curl_exec($ch);

                    if($response == 1){
                        
                        $stat=json_decode($refcomponent->getCheckstatus($referralId,6));
                        if($stat == 0){
                            $upload=['referralid'=>$referralId,'statusid'=>6];
                            $uploadData = Json::encode(['data'=>$upload]);
                            $uploadUrl ='https://eulimsapi.onelab.ph/api/web/referral/statuslogs/insertdata';
                            //$uploadUrl ='http://localhost/eulimsapi.onelab.ph/api/web/referral/statuslogs/insertdata';

                            $curlTesting = new Curl();
                            $uploadResponse = $curlTesting->setRequestBody($uploadData)
                            ->setHeaders([
                                    'Content-Type' => 'application/json',
                                    'Content-Length' => strlen($uploadData), 
                            ])->post($uploadUrl);
                        }
                        
                        Yii::$app->session->setFlash('success', "Test result successfully uploaded.");
                        return $this->redirect(['/referrals/referral/viewreferral','id'=>$referralId]);
                    } elseif($response == 0) {
                        Yii::$app->session->setFlash('error', "Attachment invalid!");
                        return $this->redirect(['/referrals/referral/viewreferral','id'=>$referralId]);
                    } else {
                        Yii::$app->session->setFlash('error', "Can't upload attachment!");
                        return $this->redirect(['/referrals/referral/viewreferral','id'=>$referralId]);
                    }
                }
            } else {
                Yii::$app->session->setFlash('error', "Not valid upload attachment!");
                return $this->redirect(['/referrals/referral/viewreferral','id'=>$referralId]);
            }
        }
        if(Yii::$app->request->isAjax) {
            return $this->renderAjax('_formUploadResult', [
                'model' => $model,
            ]);
        }
    }
     //for download attachment type deposit slip, official receipt, test result
    public function actionDownload()
    {
        set_time_limit(120);
        $refcomponent = new ReferralComponent();
        if(Yii::$app->request->get('referral_id')){
            $referralId = (int) Yii::$app->request->get('referral_id');
        } else {
            Yii::$app->session->setFlash('error', "Referral request not valid!");
            return $this->redirect(['/referrals/referral']);
        }

        if(Yii::$app->request->get('file')){
            $fileId = (int) Yii::$app->request->get('file');
        } else {
            Yii::$app->session->setFlash('error', "Not a valid file!");
            return $this->redirect(['/referrals/referral/viewreferral','id'=>$referralId]);
        }

        if($fileId > 0){
            $function = new ReferralFunctions();

           // $referral = $this->findReferral($referralId);
            
            $rstlId = (int) Yii::$app->user->identity->profile->rstl_id;
            $referral=$refcomponent->getReferralOne($referralId, $rstlId);

            $file_download = $refcomponent->downloadAttachment($referral['referral_id'],$rstlId,$fileId);
            if($file_download == 'false'){
                Yii::$app->session->setFlash('error', "Can't download file!");
                return $this->redirect(['/referrals/referral/viewreferral','id'=>$referralId]);
            } else {
                return $this->redirect($file_download);
            }
        }
    }
    //find request
    protected function findRequest($id)
    {
        $model = exRequestreferral::find()->where(['request_id'=>$id,'request_type_id'=>2])->one();
        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested Request its either does not exist or you have no permission to view it.');
        }
    }
    
    //find referral request
    protected function findReferral($id)
    {
        $model = Referral::find()->where(['referral_id'=>$id])->one();
        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested Request its either does not exist or you have no permission to view it.');
        }
    }
}
