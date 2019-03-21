<?php

namespace frontend\modules\lab\controllers;

use Yii;
use common\models\lab\Analysisextend;
use common\models\lab\AnalysisreferralSearch;
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

        $testname = $this->listsampletypereferrals($requestId);

        if(Yii::$app->request->get('test_id')>0){
            $testnameId = Yii::$app->request->get('test_id');
            $methods = json_decode($this->listReferralmethodref($testnameId),true);
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
                Yii::$app->session->setFlash('success', "Analysis Successfully Saved.");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            } else {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Analysis fail to save.");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            }
            analysisfail: {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Analysis fail to save.");
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
            $methods = json_decode($this->listReferralmethodref($testnameId),true);
        } 
        elseif ($model->test_id>0){
            $methods = json_decode($this->listReferralmethodref($model->test_id),true);
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
                    Yii::$app->session->setFlash('success', "Analysis Successfully Updated.");
                    return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                } else {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', "Analysis fail to update.");
                    return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                }
            } else {
                goto analysisfail;
            }
            analysisfail: {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Analysis fail to update.");
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
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
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
        //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/labsampletypebylab?lab_id='.$labId;
        $apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/sampletypebylab?lab_id='.$labId;
        $curl = new curl\Curl();
        $list = $curl->get($apiUrl);

        $data = ArrayHelper::map(json_decode($list), 'sampletype_id', 'type');
        
        return $data;
    }

    //get referral sample type list
    protected function listsampletypereferrals($requestId)
    {
        $sample = Sample::find()
                    ->select('sampletype_id')
                    //->joinWith('labSampletypes')
                    //->where(['tbl_labsampletype.lab_id' => $labId])
                    ->where('request_id = :requestId', [':requestId' => $requestId])
                    ->groupBy('sampletype_id')
                    ->asArray()->all();


         /*$data = (new \yii\db\Query())
            ->from('eulims_referral_lab.tbl_sampletypetestname')
            ->join('INNER JOIN', 'eulims_referral_lab.tbl_testname', 'tbl_sampletypetestname.testname_id = tbl_testname.testname_id')
            ->where([
                'sampletype_id' => [1,2],
            ])
            //->where('sampletype_id=:sampletypeId', [':sampletype_id' => [1,2]])
            ->orderBy('sampletype_id,tbl_sampletypetestname.testname_id')
            //->asArray()
            ->all();*/


        //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/labsampletypebylab?lab_id='.$labId;
        //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/sampletypebylab?lab_id='.$labId;
        //$curl = new curl\Curl();
        //$list = $curl->get($apiUrl);

        //$data = ArrayHelper::map(json_decode($list), 'sampletype_id', 'type');
        
        //return $data;

        //$datas = implode(',',$data);

        $sampletypeId = implode(',', array_map(function ($data) {
            return $data['sampletype_id'];
        }, $sample));

        //echo $a;

        //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/testnamebysampletype?sampletype_id='.$sampletypeId;
        $apiUrl='https://eulimsapi.onelab.ph/api/web/referral/listdatas/testnamebysampletype?sampletype_id='.$sampletypeId;
        $curl = new curl\Curl();
        $list = $curl->get($apiUrl);

        $data = ArrayHelper::map(json_decode($list), 'testname_id', 'test_name');
        
        return $data;

        //echo "<pre>";
        //print_r($list);
        //echo $datas;
        //echo "</pre>";
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

    public function actionGetreferraltestname() {

        if(Yii::$app->request->get('sample_id'))
        {
            /*if(count(Yii::$app->request->get('sample_id'))>1){
                //Yii::$app->request->get('sampletype_id')
                $sampleId = implode(",",Yii::$app->request->get('sample_id'));

                $sample = Sample::find()
                    ->select('sampletype_id')
                    //->joinWith('labSampletypes')
                    //->where(['tbl_labsampletype.lab_id' => $labId])
                    //->where('request_id = :requestId', [':requestId' => $requestId])
                    ->where('sample_id = :sampleId', [':sampleId' => $sampleId])
                    ->groupBy('sampletype_id')
                    ->asArray()->all();

                $sampletypeId = implode(', ', array_map(function ($data) {
 
                    return $data['sampletype_id'];
                     
                }, $sample));

            } else {
                $sampletypeId = Yii::$app->request->get('sample_id');
            }*/

            //$sampleId = Yii::$app->request->get('sample_id');
            //$sampleId = implode(",",Yii::$app->request->get('sample_id'));
            $sampleId = explode(",",Yii::$app->request->get('sample_id'));
            /*
            //only single record
            $sample = Sample::find()
                ->select('sampletype_id')
                //->joinWith('labSampletypes')
                //->where(['tbl_labsampletype.lab_id' => $labId])
                //->where('request_id = :requestId', [':requestId' => $requestId])
                ->where('sample_id = :sampleId', [':sampleId' => $sampleId])
                //->where(['in',['sample_id'],[['sample_id' => $sampleId],]])
                ->groupBy('sampletype_id')
                ->asArray()
                ->all();
            */
            $sample = (new Query)
                ->select('sampletype_id')
                ->from('eulims_lab.tbl_sample')
                //->join('INNER JOIN', 'eulims_referral_lab.tbl_testname', 'tbl_sampletypetestname.testname_id = tbl_testname.testname_id')
                ->where([
                    //'sampletype_id' => [1,2],
                    'sample_id' => $sampleId,
                ])
                //->where('sampletype_id=:sampletypeId', [':sampletype_id' => [1,2]])
                //->groupBy('tbl_testname.testname_id')
                //->orderBy('sampletype_id,tbl_sampletypetestname.testname_id')
                ->groupBy('sampletype_id')
                //->orderBy('tbl_sampletypetestname.testname_id')
                //->asArray()
                ->all();

            $sampletypeId = implode(',', array_map(function ($data) {
                return $data['sampletype_id'];
            }, $sample));

            //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/testnamebysampletype?sampletype_id='.$sampletypeId;
            $apiUrl='https://eulimsapi.onelab.ph/api/web/referral/listdatas/testnamebysampletype?sampletype_id='.$sampletypeId;
            $curl = new curl\Curl();
            $lists = $curl->get($apiUrl);

            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            if(count($lists)>0)
            {
                //$data = json_decode($list);
                //$data = ArrayHelper::map(json_decode($list), 'testname_id', 'test_name');
                //echo "<option value='' selected></option>";
                /*    foreach($data as $value=>$testname){
                        echo CHtml::tag('option', array('value'=>$value),
                            CHtml::encode($testname),true);
                    }*/
               // echo 'gg';
                //$clientCodes = ClientCode::find()->andWhere(['client_id' => $id])->all();
                //$data = [['id' => '', 'text' => '']];
                foreach(json_decode($lists) as $list) {
                    $data[] = ['id' => $list->testname_id, 'text' => $list->test_name];
                }
                //print_r($lists);
            } else {
                //$data = null;
                $data = [['id' => '', 'text' => 'No results found']];
            }
            //$data =  $data;
            //echo "<pre>";
            //print_r($sampletypeId);
            //echo "</pre>";
        } else {
            //$data = Json::encode(["error"=>"No sample selected."]);
            $data = 'No sample selected.';
        }
        //return $data;
        //return json_encode();
        return ['data' => $data];
    }

    public function actionGettestnamemethod()
    {
        if (Yii::$app->request->get('analysis_id')>0){
            $analysisId = (int) Yii::$app->request->get('analysis_id');
            $model = $this->findModel($analysisId);
           //test_id = $model->test_id;
        } else {
            $model = new Analysisextend();
        }

        if (Yii::$app->request->get('test_id')>0){
            $testnameId = (int) Yii::$app->request->get('test_id');
            $methods = json_decode($this->listReferralmethodref($testnameId),true);
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

    //get referral method reference
    protected function listReferralmethodref($testnameId)
    {

        //if(Yii::$app->request->get('testname_id'))
        if(isset($testnameId))
        {
            //$sampletypeId = (int) Yii::$app->request->get('sampletype_id');
            //$sampletypeId = json_encode(Yii::$app->request->get('sampletype_id'));
            /*if(count(Yii::$app->request->get('sampletype_id'))>1){
                //Yii::$app->request->get('sampletype_id')
                $sampletypeId = implode(",",Yii::$app->request->get('sampletype_id'));
            } else {
                $sampletypeId = (int) Yii::$app->request->get('sampletype_id');
            }*/
            //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/labsampletypebylab?lab_id='.$labId;
            //$testnameId = Yii::$app->request->get('testname_id');

            if($testnameId > 0){
                //$apiUrl='http://localhost/eulimsapi.onelab.ph/api/web/referral/listdatas/testnamemethodref?testname_id='.$testnameId;
                $apiUrl='https://eulimsapi.onelab.ph/api/web/referral/listdatas/testnamemethodref?testname_id='.$testnameId;
                $curl = new curl\Curl();
                //$list = $curl->get($apiUrl);
                $data = $curl->get($apiUrl);
            } else {
                $data = [];
            }

            //$data = ArrayHelper::map(json_decode($list), 'testname_id', 'test_name');
            //$query = Sample::find()->where('request_id = :requestId', [':requestId' => $requestId]);
            
            /*$data = new ActiveDataProvider([
                'query' => json_decode($list),
            ]);*/
            //$data = json_decode($list);

        } else {
            $data =[];
        }
        return $data;

        //echo "<pre>";
        //print_r(json_decode($list));
        //echo "</pre>";
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
            $list = $curl->get($apiUrl);
            $cpage = json_decode($list,true);
            $data = $cpage['count_page'];
        } else {
            $data = [];
        }
        return $data;
    }
}
