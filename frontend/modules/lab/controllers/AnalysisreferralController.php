<?php

namespace frontend\modules\lab\controllers;

use Yii;
use common\models\lab\Analysisextend;
use common\models\lab\AnalysisreferralSearch;
use common\models\referral\Packagelist;
use common\models\lab\Request;
use common\models\lab\Sample;
use common\models\lab\Methodreference;
use common\models\lab\Testname;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use linslin\yii2\curl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use yii\db\ActiveQuery;
use common\components\ReferralComponent;

/**
 * AnalysisreferralController implements the CRUD actions for Analysis model.
 */
class AnalysisreferralController extends Controller
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
     * Lists all Analysis models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AnalysisreferralSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Analysis model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        /*return $this->render('view', [
            'model' => $this->findModel($id),
        ]);*/
        $model = $this->findModel($id);
        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view', [
                'model' => $model,
            ]);
        } else {
            return $this->render('view', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Creates a new Analysis model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Analysisextend();
        $component = new ReferralComponent();

        if(Yii::$app->request->get('request_id'))
        {
            $requestId = (int) Yii::$app->request->get('request_id');
        }

        $query = Sample::find()->where('request_id = :requestId', [':requestId' => $requestId]);
        
        $sampleDataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        $request = $this->findRequest($requestId);
        $labId = $request->lab_id;
        $rstlId = Yii::$app->user->identity->profile->rstl_id;

        $testname = []; //$this->listsampletypereferrals($requestId);

        if(Yii::$app->request->get('test_id')>0){
            $testnameId = Yii::$app->request->get('test_id');
            $methods = json_decode($this->listReferralmethodref($testnameId,$labId,[],null),true);
        } else {
            $methods = [];
        }

        $methodrefDataProvider = new ArrayDataProvider([
            //'key'=>'sample_id',
            'allModels' => $methods,
            'pagination' => [
                'pageSize' => 10,
            ],
            //'pagination'=>false,
        ]);

        if ($model->load(Yii::$app->request->post())) {
            $connection = Yii::$app->labdb;
            $transaction = $connection->beginTransaction();

            $postData = Yii::$app->request->post();
            foreach ($postData['sample_ids'] as $sample) {
                $testId = $postData['Analysisextend']['test_id'];
                $methodrefId = $postData['methodref_id'];
                $test = $component->getTestnameOne($testId);
                $method = $component->getMethodrefOne($methodrefId);
                $analysis = new Analysisextend();
                $analysis->rstl_id = (int) $rstlId;
                $analysis->date_analysis = date('Y-m-d');
                $analysis->request_id = (int) $requestId;
                $analysis->sample_id = (int) $sample;
                $analysis->testname = $test->test_name;
                $analysis->methodref_id = $method->methodreference_id;
                $analysis->method = $method->method;
                $analysis->references = $method->reference;
                $analysis->fee =$method->fee;
                $analysis->quantity = 1; 
                $analysis->test_id = (int) $testId;
                $analysis->is_package = 0;
                //$model->sample_type_id = null;
                //$model->testcategory_id = null;
                //$analysis->save(false);
                if(!$analysis->save(false)){
                    goto analysisfail;
                }
            }
            $discount = $component->getDiscountOne($request->discount_id);

            $rate = $discount->rate;
            $fee = $connection->createCommand('SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id =:requestId')
            ->bindValue(':requestId',$requestId)->queryOne();
            $subtotal = $fee['subtotal'];
            $total = $subtotal - ($subtotal * ($rate/100));

            $request->total = $total;
            //$request->save();
            if($request->save(false)){
                $transaction->commit();
                Yii::$app->session->setFlash('success', "Analysis successfully saved.");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            } else {
                goto analysisfail;
            }
            analysisfail: {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Analysis failed to save.");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            }
        } elseif (Yii::$app->request->isAjax) {
            $rcomp = new ReferralComponent;
            $agencies = $rcomp->getAgencies();

            $datagency = ArrayHelper::map($agencies, 'rstl_id', 'name');
            return $this->renderAjax('_form', [
                'model' => $model,
                'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'methodrefDataProvider' => $methodrefDataProvider,
                'agencies'=>$datagency,
            ]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'methodrefDataProvider' => $methodrefDataProvider,
            ]);
        }
    }

    /**
     * Updates an existing Analysis model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $component = new ReferralComponent();

        if(Yii::$app->request->get('request_id'))
        {
            $requestId = (int) Yii::$app->request->get('request_id');
        }

        $query = Sample::find()->where('request_id = :requestId', [':requestId' => $requestId]);

        $sampleDataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        $request = $this->findRequest($requestId);
        $labId = $request->lab_id;
        $rstlId = Yii::$app->user->identity->profile->rstl_id;

        $testname = $this->listsampletypereferrals($requestId);

        if (Yii::$app->request->get('test_id')>0){
            $testnameId = Yii::$app->request->get('test_id');
            $methods = json_decode($this->listReferralmethodref($testnameId, $labId,[],null),true);
        } 
        elseif ($model->test_id>0){
            $methods = json_decode($this->listReferralmethodref($model->test_id, $labId,[],null),true);
        } 
        else {
            $methods = [];
        }

        $methodrefDataProvider = new ArrayDataProvider([
            'allModels' => $methods,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        if ($model->load(Yii::$app->request->post())) {
            $connection = Yii::$app->labdb;
            $transaction = $connection->beginTransaction();
            $postData = Yii::$app->request->post();
            
            $testId = $postData['Analysisextend']['test_id'];
            $methodrefId = $postData['methodref_id'];
            $test = $component->getTestnameOne($testId);
            $method = $component->getMethodrefOne($methodrefId);
            //start saving
            $model->rstl_id = (int) $rstlId;
            $model->date_analysis = date('Y-m-d');
            $model->request_id = (int) $requestId;
            $model->sample_id = (int) $postData['sample_id'];
            $model->testname = $test->test_name;
            $model->methodref_id = $method->methodreference_id;
            $model->method = $method->method;
            $model->references = $method->reference;
            $model->fee =$method->fee;
            $model->quantity = 1; 
            $model->test_id = (int) $testId;
            //$model->sample_type_id = null;
            //$model->testcategory_id = null;
            if($model->save(false)){
                //$transaction->commit();
                //Yii::$app->session->setFlash('success', "Analysis Successfully Updated.");
                //return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                $discount = $component->getDiscountOne($request->discount_id);
                $rate = $discount->rate;
                $fee = $connection->createCommand('SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id =:requestId')
                ->bindValue(':requestId',$requestId)->queryOne();
                $subtotal = $fee['subtotal'];
                $total = $subtotal - ($subtotal * ($rate/100));

                $request->total = $total;
                //$request->save();
                if($request->save(false)){
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', "Analysis successfully updated.");
                    return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                } else {
                    goto analysisfail;
                }
            } else {
                goto analysisfail;
            }
            analysisfail: {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Analysis failed to update.");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            }
        } elseif (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
                'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'methodrefDataProvider' => $methodrefDataProvider,
            ]);
        } else {
            //$model->testname_id = 1;
            return $this->render('update', [
                'model' => $model,
                'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'methodrefDataProvider' => $methodrefDataProvider,
            ]);
        }

        //return $this->render('update', [
        //    'model' => $model,
        //]);
    }

    /**
     * Deletes an existing Analysis model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $connection = Yii::$app->labdb;
        $component = new ReferralComponent();
        $transaction = $connection->beginTransaction();
        $requestId = $model->request_id;
        if($model->delete()) {
            $request = $this->findRequest($requestId);
            $discount = $component->getDiscountOne($request->discount_id);
            $rate = $discount->rate;
            $fee = $connection->createCommand('SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id =:requestId')
            ->bindValue(':requestId',$requestId)->queryOne();
            $subtotal = $fee['subtotal'];
            $total = $subtotal - ($subtotal * ($rate/100));
            $request->total = $total;

            if($request->save(false)){
                $transaction->commit();
                Yii::$app->session->setFlash('warning', 'Analysis Successfully Deleted');
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            } else {
                $transaction->rollBack();
                Yii::$app->session->setFlash('warning', 'Analysis Successfully Deleted');
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            }
        } else {
            $transaction->rollBack();
            return $model->error();
        }
    }

    /**
     * Finds the Analysis model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Analysis the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Analysisextend::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected function findRequest($requestId)
    {
        if (($model = Request::findOne($requestId)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    //get referral sample type list
    protected function listSampletypereferral($labId)
    {
        $refcomponent = new ReferralComponent();
        $list = $refcomponent->getSampletype($labId);
        $data = [];
        if($list)
            $data = ArrayHelper::map(json_decode($list), 'sampletype_id', 'type');
        return $data;
    }

    //get referral sample type list //btc updated to the restreferral working
    protected function listsampletypereferrals($requestId)
    {
        $sample = Sample::find()
                    ->select('sampletype_id')
                    ->where('request_id = :requestId', [':requestId' => $requestId])
                    ->groupBy('sampletype_id')
                    ->asArray()->all();

        $sampletypeId = implode(',', array_map(function ($data) {
            return $data['sampletype_id'];
        }, $sample));

        //added this line here to get all the testnames by sampletype ids
        $refcomponent = new ReferralComponent();
        $list = $refcomponent->getTestnamesbysampletypeidsonly($sampletypeId);
        $data = [];
        if($list)
            $data = ArrayHelper::map($list, 'testname_id', 'test_name');
        return $data;
    }
    
    //get referral sample type list by sampletype_id
    public function actionReferraltestname()
    {

        if(Yii::$app->request->get('sampletype_id'))
        {
            //$sampletypeId = (int) Yii::$app->request->get('sampletype_id');
            //$sampletypeId = json_encode(Yii::$app->request->get('sampletype_id'));
            if(count(Yii::$app->request->get('sampletype_id'))>1){
                //Yii::$app->request->get('sampletype_id')
                $sampletypeId = implode(",",Yii::$app->request->get('sampletype_id'));
            } else {
                $sampletypeId = (int) Yii::$app->request->get('sampletype_id');
            }
        }
        //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/labsampletypebylab?lab_id='.$labId;
        //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/testnamebysampletype?sampletype_id='.$sampletypeId;
        $apiUrl='https://eulimsapi.onelab.ph/api/web/referral/listdatas/testnamebysampletype?sampletype_id='.$sampletypeId;
        $curl = new curl\Curl();
        $list = $curl->get($apiUrl);

        $data = ArrayHelper::map(json_decode($list), 'testname_id', 'test_name');
        
        return $data;

        //echo "<pre>";
        //print_r(json_decode($list));
        //echo "</pre>";
    }
    //btc needed when the samples are clicked in the add analysis , updated and reflected in the restreferral
    public function actionGetreferraltestname() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if(Yii::$app->request->get('sample_id'))
        {
            $lab_id= Yii::$app->request->get('labId');
            $sampleId = explode(",",Yii::$app->request->get('sample_id'));
            $sample = (new Query)
                ->select('sampletype_id')
                ->from('eulims_lab.tbl_sample')
                ->where([
                    'sample_id' => $sampleId,
                ])
                ->groupBy('sampletype_id')
                ->all();

            $sampletypeId = implode(',', array_map(function ($data) {
                return $data['sampletype_id'];
            }, $sample));

            //added this line here to het all the testnames by sampletype ids
            $refcomponent = new ReferralComponent();
            $lists = $refcomponent->getTestnamesbysampletypeidsonly($sampletypeId,$lab_id);

            if(count($lists)>0)
            {
                foreach($lists as $list) {

                    $data[] = ['id' => $list['testname_id'], 'text' => $list['test_name']];
                }
                
            } else {
                $data = [['id' => '', 'text' => 'No results found']];
            }
        } else {
            $data = 'No sample selected.';
        }
        return ['data' => $data];
    }

    //btc updated this action
    public function actionGettestnamemethod()
    {   //gets the analysis record for the update purpose otherwise new 
        if (Yii::$app->request->get('analysis_id')>0){
            $analysisId = (int) Yii::$app->request->get('analysis_id');
            $model = $this->findModel($analysisId);
        } else {
            $model = new Analysisextend();
        }
        //code below is responsible for getting the testmethod reference , fee
        if (Yii::$app->request->get('test_id')>0){
            $testnameId = (int) Yii::$app->request->get('test_id');
            $lab_id= Yii::$app->request->get('lab_id');
            $sampleId = Yii::$app->request->get('sample_id');
            $test_rstl_id = Yii::$app->request->get('rstl_id');
            $sample = (new Query)
                ->select('sampletype_id')
                ->from('eulims_lab.tbl_sample')
                ->where([
                    'sample_id' => $sampleId,
                ])
                ->groupBy('sampletype_id')
                ->all();

            $sampletypeId = implode(',', array_map(function ($data) {
                return $data['sampletype_id'];
            }, $sample));

            $methods = $this->listReferralmethodref($testnameId,$lab_id,$sampletypeId,$test_rstl_id);
        }
        else {
                $methods = [];
        }

        if (Yii::$app->request->isAjax) {
            $methodrefDataProvider = new ArrayDataProvider([
                'allModels' => $methods,
                'pagination' => [
                    'pageSize' => 10,
                ],
            ]);
            return $this->renderAjax('_methodreference', [
                'methodProvider' => $methodrefDataProvider,
                'model' => $model,
            ]);
        }
    }

    //get referral method reference btc updated this action
    protected function listReferralmethodref($testnameId,$lab_id,$sampletypeId,$test_rstl_id)
    {

        if(isset($testnameId,$lab_id,$sampletypeId))
        {
            if($testnameId > 0){
                //added this line here to get all the testnamemethods from the referral component
                $refcomponent = new ReferralComponent();
                //named this function like this because it has the same function with different number of args
                $data = $refcomponent->getMethodrefbytestnameidonly($testnameId,$lab_id,$sampletypeId,$test_rstl_id);
            } else {
                $data = [];
            }
        } else {
            $data =[];
        }
        return $data;
    }

    //get default pagination page on load for the checked method reference
    public function actionGetdefaultpage()
    {
        if (Yii::$app->request->get('analysis_id')){
            $analysisId = (int) Yii::$app->request->get('analysis_id');
        } else {
            $analysisId = null;
        }

        //per page pagination should be the same 
        //default page size of the method reference dataprovider
        $perpage = 10;

        if($analysisId > 0){
            $model = $this->findModel($analysisId);
            //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/setpagemethodref?testname_id='.$model->test_id.'&methodref_id='.$model->methodref_id.'&perpage='.$perpage;
            $apiUrl='https://eulimsapi.onelab.ph/api/web/referral/listdatas/setpagemethodref?testname_id='.$model->test_id.'&methodref_id='.$model->methodref_id.'&perpage='.$perpage;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
            $curl->setOption(CURLOPT_TIMEOUT, 180);
            $list = $curl->get($apiUrl);
            $cpage = json_decode($list,true);
            $data = $cpage['count_page'];
        } else {
            $data = [];
        }
        return $data;
    }

    /**********for package**********/
    public function actionPackage()
    {
        $model = new Analysisextend();
        $component = new ReferralComponent();

        if(Yii::$app->request->get('request_id'))
        {
            $requestId = (int) Yii::$app->request->get('request_id');
        }

        $query = Sample::find()->where('request_id = :requestId', [':requestId' => $requestId]);
        
        $sampleDataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        $request = $this->findRequest($requestId);
        $labId = $request->lab_id;
        $rstlId = Yii::$app->user->identity->profile->rstl_id;

        $getSampletype = Sample::find()->where('request_id = :requestId', [':requestId' => $requestId])->groupBy('sampletype_id')->all();

        $sampletypeId = implode(',', array_map(function ($data) {
            return $data['sampletype_id'];
        }, $getSampletype));

        $listpackage = json_decode($component->getPackages($labId,$sampletypeId),true);

        if($labId > 0 && $listpackage != 0){
            $packageDataProvider = new ArrayDataProvider([
                'key'=>'package_id',
                'allModels' => $listpackage,
                'pagination' => [
                    'pageSize' => 10,
                ],
            ]);
        } else {
            $packageDataProvider = new ArrayDataProvider([
                'key'=>'package_id',
                'allModels' => [],
                'pagination' => [
                    'pageSize' => 10,
                ],
            ]);
        }
        if (Yii::$app->request->post()) {
            $connection = Yii::$app->labdb;
            $transaction = $connection->beginTransaction();
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();

            if($request->discount > 0) {
                goto packagefail;
            }

            $postData = Yii::$app->request->post();
            foreach ($postData['sample_ids'] as $sampleId) {

                $packageId = (int) $postData['package_id'];
                $package_details = json_decode($component->getPackageOne($packageId),true);
                $sample = Sample::findOne($sampleId);

                $package = new Analysisextend();
                $package->rstl_id = (int) $rstlId;
                $package->date_analysis = date('Y-m-d');
                $package->request_id = (int) $requestId;
                $package->sample_id = (int) $sampleId;
                $package->testname = $package_details['package']['name'];
                $package->package_id = $packageId;
                // $package->package_name = $package_details['package']['name'];
                $package->methodref_id = 0;
                $package->method = '-';
                $package->references = '-';
                $package->fee = $package_details['package']['rate'];
                $package->quantity = 1; 
                $package->test_id = 0;
                $package->is_package = 1;
                $package->type_fee_id = 2;
                // $package->is_package_name = 1;
                $package->sampletype_id = (int) $sample->sampletype_id;

                if($package->save(false)){
                    foreach ($package_details['testmethod_data'] as $test_method) {
                        $analysis = new Analysisextend();
                        $analysis->rstl_id = (int) $rstlId;
                        $analysis->date_analysis = date('Y-m-d');
                        $analysis->request_id = (int) $requestId;
                        $analysis->sample_id = (int) $sampleId;
                        $analysis->testname = $test_method['testname'];
                        $analysis->methodref_id = (int) $test_method['methodreference_id'];
                        $analysis->method = $test_method['method'];
                        $analysis->references = $test_method['reference'];
                        $analysis->package_id = $packageId;
                        $analysis->fee = 0; //since package
                        $analysis->quantity = 1;
                        $analysis->test_id = (int) $test_method['testname_id'];
                        $analysis->is_package = 1;
                        $analysis->type_fee_id = 2;
                        $analysis->sampletype_id = (int) $sample->sampletype_id;

                        if($analysis->save(false)){
                            $analysisSave = 1;
                        } else {
                            $analysisSave = 0;
                            goto packagefail;
                        }
                    }
                } else {
                    $analysisSave = 0;
                    goto packagefail;
                }
            }
            $discount = $component->getDiscountOne($request->discount_id);
            $rate = $discount->rate;
            $fee = $connection->createCommand('SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id =:requestId')
            ->bindValue(':requestId',$requestId)->queryOne();
            $subtotal = $fee['subtotal'];
            $total = $subtotal - ($subtotal * ($rate/100));

            $request->total = $total;
            if($request->save(false) && $analysisSave == 1){
                $transaction->commit();
                Yii::$app->session->setFlash('success', "Package successfully saved.");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            } else {
                goto packagefail;
            }
            packagefail: {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Package failed to save!");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            }
        } elseif (Yii::$app->request->isAjax) {
            return $this->renderAjax('_formPackage', [
                'model' => $model,
                //'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'packageDataProvider' => $packageDataProvider,
                //'methodrefDataProvider' => $methodrefDataProvider,
            ]);
        } else {
            /*return $this->render('createPackage', [
                'model' => $model,
                //'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'packageDataProvider' => $packageDataProvider,
                //'methodrefDataProvider' => $methodrefDataProvider,
            ]);*/
            return $this->renderAjax('_formPackage', [
                'model' => $model,
                //'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'packageDataProvider' => $packageDataProvider,
                //'methodrefDataProvider' => $methodrefDataProvider,
            ]);
        }
    }

    public function actionUpdatepackage($analysis_id,$sample_id,$package_id,$request_id)
    {
        //$model = new Analysisextend();

        $model = $this->findModel($analysis_id);
        $component = new ReferralComponent();

        if(Yii::$app->request->get('request_id')){
            $requestId = (int) Yii::$app->request->get('request_id');
        } else {
            $requestId = (int) $request_id;
        }

        $query = Sample::find()->where('request_id = :requestId', [':requestId' => $requestId]);
        
        $sampleDataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        $request = $this->findRequest($requestId);
        $labId = $request->lab_id;
        $rstlId = Yii::$app->user->identity->profile->rstl_id;

        $getSampletype = Sample::find()->where('request_id = :requestId', [':requestId' => $requestId])->groupBy('sampletype_id')->all();

        $sampletypeId = implode(',', array_map(function ($data) {
            return $data['sampletype_id'];
        }, $getSampletype));

        $listpackage = json_decode($component->getPackages($labId,$sampletypeId),true);

        if($labId > 0 && $listpackage != 0){
            $packageDataProvider = new ArrayDataProvider([
                'key'=>'package_id',
                'allModels' => $listpackage,
                'pagination' => [
                    'pageSize' => 10,
                ],
            ]);
        } else {
            $packageDataProvider = new ArrayDataProvider([
                'key'=>'package_id',
                'allModels' => [],
                'pagination' => [
                    'pageSize' => 10,
                ],
            ]);
        }
        if (Yii::$app->request->post()) {
            $connection = Yii::$app->labdb;
            $transaction = $connection->beginTransaction();
            $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();

            if($sample_id > 0 && $package_id > 0){
                $delete = Analysisextend::deleteAll('sample_id =:sampleId AND package_id =:packageId AND type_fee_id =:typeFee', [':sampleId' => $sample_id,':packageId'=>$package_id,':typeFee'=>2]);
                if($delete){
                    $postData = Yii::$app->request->post();

                    $sampleId = (int) $postData['sample_id'];
                    $packageId = (int) $postData['package_id'];
                    $package_details = json_decode($component->getPackageOne($packageId),true);
                    $sample = Sample::findOne($sampleId);

                    $package = new Analysisextend();
                    $package->rstl_id = (int) $rstlId;
                    $package->date_analysis = date('Y-m-d');
                    $package->request_id = (int) $requestId;
                    $package->sample_id = (int) $sampleId;
                    $package->testname = '-';
                    $package->package_id = $packageId;
                    $package->package_name = $package_details['package']['name'];
                    $package->methodref_id = NULL;
                    $package->method = '-';
                    $package->references = '-';
                    $package->fee = $package_details['package']['rate'];
                    $package->quantity = 1; 
                    $package->test_id = 0;
                    $package->is_package = 1;
                    $package->type_fee_id = 2;
                    $package->is_package_name = 1;
                    $package->sampletype_id = (int) $sample->sampletype_id;

                    if($package->save(false)){
                        foreach ($package_details['testmethod_data'] as $test_method) {
                            $analysis = new Analysisextend();
                            $analysis->rstl_id = (int) $rstlId;
                            $analysis->date_analysis = date('Y-m-d');
                            $analysis->request_id = (int) $requestId;
                            $analysis->sample_id = (int) $sampleId;
                            $analysis->testname = $test_method['testname'];
                            $analysis->methodref_id = (int) $test_method['methodreference_id'];
                            $analysis->method = $test_method['method'];
                            $analysis->references = $test_method['reference'];
                            $analysis->package_id = $packageId;
                            $analysis->fee = 0; //since package
                            $analysis->quantity = 1;
                            $analysis->test_id = (int) $test_method['testname_id'];
                            $analysis->is_package = 1;
                            $analysis->type_fee_id = 2;
                            $analysis->sampletype_id = (int) $sample->sampletype_id;

                            if($analysis->save(false)){
                                $analysisSave = 1;
                            } else {
                                $analysisSave = 0;
                                goto packagefail;
                            }
                        }
                    } else {
                        $analysisSave = 0;
                        goto packagefail;
                    }
                    
                    $discount = $component->getDiscountOne($request->discount_id);
                    $rate = $discount->rate;
                    $fee = $connection->createCommand('SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id =:requestId')
                    ->bindValue(':requestId',$requestId)->queryOne();
                    $subtotal = $fee['subtotal'];
                    $total = $subtotal - ($subtotal * ($rate/100));

                    $request->total = $total;
                    if($request->save(false) && $analysisSave == 1){
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', "Package successfully updated.");
                        return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                    } else {
                        goto packagefail;
                    }
                } else {
                    goto packagefail;
                }
            } else {
                goto packagefail;
            }
            packagefail: {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Failed to update package!");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            }
        } elseif (Yii::$app->request->isAjax) {
            return $this->renderAjax('_formPackage', [
                'model' => $model,
                //'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'packageDataProvider' => $packageDataProvider,
                //'methodrefDataProvider' => $methodrefDataProvider,
            ]);
        } else {
            return $this->renderAjax('_formPackage', [
                'model' => $model,
                //'testname' => $testname,
                'labId' => $labId,
                'sampleDataProvider' => $sampleDataProvider,
                'packageDataProvider' => $packageDataProvider,
                //'methodrefDataProvider' => $methodrefDataProvider,
            ]);
        }
    }

    public function actionDeletepackage($sample_id,$package_id,$request_id)
    {
        if(Yii::$app->request->post()){
            $sampleId = (int) $sample_id;
            $packageId = (int) $package_id;

            if($sampleId > 0 && $packageId > 0){
                $delete = Analysisextend::deleteAll('sample_id =:sampleId AND package_id =:packageId AND type_fee_id =:typeFee', [':sampleId' => $sampleId,':packageId'=>$packageId,':typeFee'=>2]);
                if($delete){
                    $component = new ReferralComponent();
                    $connection = Yii::$app->labdb;

                    $request = Request::find()->where(['request_id' =>(int) $request_id])->one();
                    $discount = $component->getDiscountOne($request->discount_id);
                    $rate = $discount->rate;
                    $fee = $connection->createCommand('SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id =:requestId')
                    ->bindValue(':requestId',(int) $request_id)->queryOne();
                    $subtotal = $fee['subtotal'];
                    $total = $subtotal - ($subtotal * ($rate/100));
                    $request->total = $total;
                    if($request->save(false)){
                        Yii::$app->session->setFlash('success', "Package successfully removed.");
                        return $this->redirect(['/lab/request/view', 'id' => $request_id]);
                    } else {
                        Yii::$app->session->setFlash('error', "Failed to update total!");
                        return $this->redirect(['/lab/request/view', 'id' => $request_id]);
                    }
                } else {
                    Yii::$app->session->setFlash('error', "Failed to delete package!");
                    return $this->redirect(['/lab/request/view', 'id' => $request_id]);
                }
            } else {
                Yii::$app->session->setFlash('error', "Failed to delete package!");
                return $this->redirect(['/lab/request/view', 'id' => $request_id]);
            }
        } else {
            Yii::$app->session->setFlash('error', "Invalid action!");
            return $this->redirect(['/lab/request/view', 'id' => $request_id]);
        }
    }

    public function actionPackagedetail() {
        if (Yii::$app->request->post('expandRowKey')) {
            $packageId = (int) Yii::$app->request->post('expandRowKey');
            $component = new ReferralComponent();
            $testname_method = json_decode($component->getPackageOne($packageId),true);
            if(!empty($testname_method['testmethod_data']) && $testname_method != 0){
                $testname_methodDataProvider = new ArrayDataProvider([
                    'key'=>'testname_method_id',
                    'allModels' => $testname_method['testmethod_data'],
                    'pagination' => false,
                ]);
                return $this->renderPartial('_package-details', ['testname_methodDataProvider'=>$testname_methodDataProvider]);
            } else {
                return '<div class="alert alert-danger">No data found</div>';
            }
        } else {
            return '<div class="alert alert-danger">No data found</div>';
        }
    }

    public function actionGetpackage()
    {
        if (Yii::$app->request->get('analysis_id')>0){
            $analysisId = (int) Yii::$app->request->get('analysis_id');
            $model = $this->findModel($analysisId);
           //test_id = $model->test_id;
        } else {
            $model = new Analysisextend();
        }

        if (Yii::$app->request->get('package_id')>0){
            $packageId = (int) Yii::$app->request->get('package_id');
            $methods = json_decode($this->listReferralmethodref($packageId),true);
        }
        else {
            //if ($test_id > 0){
                //$methods = json_decode($this->listReferralmethodref($test_id),true);
            //} else {
                $methods = [];
            //}
        }

        if (Yii::$app->request->isAjax) {
            $methodrefDataProvider = new ArrayDataProvider([
                //'key'=>'sample_id',
                'allModels' => $methods,
                'pagination' => [
                    'pageSize' => 10,
                ],
                //'pagination'=>false,
            ]);
            return $this->renderAjax('_methodreference', [
                'methodProvider' => $methodrefDataProvider,
                'model' => $model,
            ]);
        }
        //return $this->renderAjax('_methodreference', [
        //    'methodProvider' => $methodrefDataProvider,
        //]);
        /*return $this->renderPartial('_methodreference', [
           'methodProvider' => $methodrefDataProvider,
        ]);*/
        //return $this->render('_methodreference', ['methodProvider' => $methodrefDataProvider,]);
    }

    //get packages
    protected function listPackage($packageId)
    {
        if(isset($packageId))
        {
            if($packageId > 0){
                //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/testnamemethodref?testname_id='.$testnameId;
                $apiUrl='https://eulimsapi.onelab.ph/api/web/referral/listdatas/testnamemethodref?testname_id='.$testnameId;
                $curl = new curl\Curl();
                $curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
                $curl->setOption(CURLOPT_TIMEOUT, 180);
                //$list = $curl->get($apiUrl);
                $data = $curl->get($apiUrl);
            } else {
                $data = [];
            }

        } else {
            $data =[];
        }
        return $data;
    }

    public function actionGetreferralpackage()
    {

        if(Yii::$app->request->get('sample_id') && Yii::$app->request->get('lab_id') > 0)
        {
            $model = new Analysisextend();
            $component = new ReferralComponent();
            $sampleId = explode(",",Yii::$app->request->get('sample_id'));
            $labId = (int) Yii::$app->request->get('lab_id');
            $sample = (new Query)
                ->select('sampletype_id')
                ->from('eulims_lab.tbl_sample')
                ->where([
                    'sample_id' => $sampleId,
                ])
                ->groupBy('sampletype_id')
                ->all();

            $sampletypeId = implode(',', array_map(function ($data) {
                return $data['sampletype_id'];
            }, $sample));

            $listpackage = json_decode($component->getPackages($labId,$sampletypeId),true);

            if($labId > 0 && $listpackage != 0){
                $packageDataProvider = new ArrayDataProvider([
                    'key'=>'package_id',
                    'allModels' => $listpackage,
                    'pagination' => [
                        'pageSize' => 10,
                    ],
                ]);
            } else {
                $packageDataProvider = new ArrayDataProvider([
                    'key'=>'package_id',
                    'allModels' => [],
                    'pagination' => [
                        'pageSize' => 10,
                    ],
                ]);
            }
            if (Yii::$app->request->isAjax) {
                return $this->renderAjax('_packages', [
                    'packageDataProvider' => $packageDataProvider,
                    'model' => $model,
                ]);
            }
        } else {
            return 'No sample selected!';
        }
    }
}
