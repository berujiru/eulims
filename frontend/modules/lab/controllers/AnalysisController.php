<?php
namespace frontend\modules\lab\controllers;

use Yii;
use common\models\lab\Analysis;
use common\models\lab\Sample;
use common\models\lab\Request;
use common\models\lab\RequestSearch;
use common\models\lab\Discount;
use common\models\lab\AnalysisSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use common\models\lab\SampleSearch;
use yii\helpers\Json;
use common\models\finance\Paymentitem;
use common\models\lab\Lab;
use common\models\lab\Labsampletype;
use common\models\lab\Sampletype;
use common\models\lab\Sampletypetestname;
use common\models\lab\Testnamemethod;
use common\models\lab\Methodreference;
use common\models\lab\Testname;
use yii\data\ArrayDataProvider;
use DateTime;

/**
 * AnalysisController implements the CRUD actions for Analysis model.
 */
class AnalysisController extends Controller
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
        $searchModel = new AnalysisSearch();
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

     protected function findRequest($requestId)
     {
         if (($model = Request::findOne($requestId)) !== null) {
             return $model;
         } else {
             throw new NotFoundHttpException('The requested page does not exist.');
         }
     }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionGettest() {
        if(isset($_GET['method_reference_id'])){
            $id = (int) $_GET['method_reference_id'];
            $modelmethodreference=  Methodreference::findOne(['method_reference_id'=>$id]);
            if(count($modelmethodreference)>0){
                $references = $modelmethodreference->reference;
                $fee = $modelmethodreference->fee;

            } else {
                $references = "";
                $fee = "";
            }
        } else {
            $method = "Error getting method";
            $references = "Error getting reference";
            $fee = "Error getting fee";
        }

        return Json::encode([
            'references'=>$references,
            'fee'=>$fee,
        ]);
    }

    public function actionGetmethod() {
        $id = $_GET['id'];
         $analysisQuery = Methodreference::find()->where(['method_reference_id' => $id]);
         $testnamemethoddataprovider = new ActiveDataProvider([
                 'query' => $analysisQuery,
                 'pagination' => [
                     'pageSize' => 10,
                 ],
              
         ]);

        return $this->renderPartial('_methodreference', [
              'testnamemethoddataprovider'=>$testnamemethoddataprovider,
           ]);      
    }

    public function actionListtest()
    {
        //error here
        //weird
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $id = end($_POST['depdrop_parents']);
            $list = Methodreference::find()
            ->leftJoin('tbl_testname_method', 'tbl_testname_method.method_id=tbl_methodreference.method_reference_id')
             ->asArray()
            ->Where(['tbl_testname_method.testname_id'=>$id])
            ->all();
            $selected  = null;
            if ($id != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i => $test) {
                    $out[] = ['id' => $test['method_reference_id'], 'name' => $test['method']];
                    if ($i == 0) {
                        $selected = $test['method_reference_id'];
                    }
                }
                \Yii::$app->response->data = Json::encode(['output'=>$out, 'selected'=>'']);
                return;
            }
        }
        echo Json::encode(['output' => '', 'selected'=>'']);
    }

    public function actionGettestnamemethod()
	{
      
        $testname_id = $_GET['testname_id'];
        $sampletype_id = $_GET['sampletype_id'];
        $sample = $_GET['sample'];

        //get the information of the sample
        $sample = Sample::findOne($sample[0]);
        //$sampletype_id = $sample->sampletype_id;

        //get the lab_id of the request
        $lab_id= $sample->request->lab_id;

        $testnamemethod = Testnamemethod::find()
        ->where(['tbl_testname_method.testname_id'=>$testname_id,'tbl_testname_method.sampletype_id'=>$sampletype_id,'tbl_testname_method.lab_id'=>$lab_id])->all();

        
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
  
    public function actionListsampletype() {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            //$sampleids = end($_POST['depdrop_parents']);
            $sampletypeId = end($_POST['depdrop_parents']);


            //**********************************************************
            //unused but important later
            //check if all the selected values are of the same sample type 

            // $sampletypeId="";
            // foreach($sampleids as $id){
            //     $sample = Sample::findOne($id);
            //     if($sampletypeId=="")
            //         $sampletypeId=$sample->sampletype_id;
            //     else{
            //         if($sampletypeId!==$sample->sampletype_id){
            //             //return if empty data because sampleype_id are not the same
            //             echo Json::encode(['output' => '', 'selected'=>'']);
            //             return;
            //         }
            //     }
            // }

            //************************************************

            //this line belows removes the purpose of the sampletype _testname table , gets all the testnamemethod join with testname under certain sampletype_id
            $list = Testnamemethod::find()->with('testname')->where(['sampletype_id'=>$sampletypeId])->groupby('testname_id')->asArray()->all();

            $selected  = null;
            if ($sampletypeId != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i) {
                    if($i['testname']){
                        $out[] = ['id' => $i['testname']['testname_id'], 'name' => $i['testname']['testName']];
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

    public function actionListtype() {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $id = end($_POST['depdrop_parents']);

            $list =  Sampletype::find()
            ->innerJoin('tbl_lab_sampletype', 'tbl_lab_sampletype.sampletype_id=tbl_sampletype.sampletype_id')
            ->innerJoin('tbl_testcategory', 'tbl_testcategory.testcategory_id=tbl_lab_sampletype.testcategory_id')
            ->Where(['tbl_lab_sampletype.testcategory_id'=>$id])
            ->asArray()
            ->all();

            $selected  = null;
            if ($id != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i => $sampletype) {
                    $out[] = ['id' => $sampletype['sampletype_id'], 'name' => $sampletype['type']];
                    if ($i == 0) {
                        $selected = $sampletype['sampletype_id'];
                    }
                }
                \Yii::$app->response->data = Json::encode(['output'=>$out, 'selected'=>'']);
                return;
            }
        }
        echo Json::encode(['output' => '', 'selected'=>'']);
    }

    /**
     * Creates a new Analysis model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($id)
    {
        $model = new Analysis;
        $session = Yii::$app->session;
        $searchModel = new AnalysisSearch();
        $samplesearchmodel = new SampleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $samplesQuery = Sample::find()->where(['request_id' => $id])->all();

        $request_id = $_GET['id'];
        $request = $this->findRequest($request_id);
        $labId = $request->lab_id;
        $testcategory = [];
        $sampletype = [];
        $test = [];     
        if ($model->load(Yii::$app->request->post())) {
           $requestId = (int) Yii::$app->request->get('request_id');
                 $sample_ids= $_POST['base_samples'];
                 $post= Yii::$app->request->post();

                foreach ($sample_ids as $sample_id){                
                    $modeltest=  Testname::findOne(['testname_id'=>$post['Analysis']['test_id']]);
                    $modelmethod=  Testnamemethod::findOne(['testname_method_id'=>$post['Analysis']['method']]);
                    $method=  Methodreference::findOne(['method_reference_id'=>$modelmethod->method_id]);
                    $analysis = new Analysis();
                    $date = new DateTime();
                    date_add($date,date_interval_create_from_date_string("1 day"));
                    $analysis->sample_id = $sample_id;
                    $analysis->cancelled = 0;
                    $analysis->pstcanalysis_id = (int) $post['Analysis']['pstcanalysis_id'];
                    $analysis->request_id = $request_id;
                    $analysis->type_fee_id = 1;
                    $analysis->rstl_id = Yii::$app->user->identity->profile->rstl_id;
                    $analysis->test_id = (int) $post['Analysis']['test_id'];
                    //$analysis->sample_type_id = (int) $post['Analysis']['sample_type_id'];
                    $analysis->testcategory_id = $method->method_reference_id;
                    $analysis->is_package = (int) $post['Analysis']['is_package'];
                    $analysis->method = $method->method;
                    $analysis->fee = $method->fee;
                    $analysis->testname = $modeltest->testName;
                    $analysis->references = $method->reference;
                    $analysis->quantity = 1;
                    $analysis->sample_code = $post['Analysis']['sample_code'];
                    $analysis->date_analysis = date("Y-m-d h:i:s");
                    $analysis->save(); 
                    
                    $requestquery = Request::find()->where(['request_id' => $request_id])->one();
                    $discountquery = Discount::find()->where(['discount_id' => $requestquery->discount_id])->one();
                    $rate =  $discountquery->rate;       
                    $sql = "SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id=$request_id";
                    $Connection = Yii::$app->labdb;
                    $command = $Connection->createCommand($sql);
                    $row = $command->queryOne();
                    $subtotal = $row['subtotal'];
                    $total = $subtotal - ($subtotal * ($rate/100));

                    $Connection= Yii::$app->labdb;
                    $sql="UPDATE `tbl_request` SET `total`='$total' WHERE `request_id`=".$request_id;
                    $Command=$Connection->createCommand($sql);
                    $Command->execute();
                }     
               // Yii::$app->session->setFlash('success', 'Analysis Successfully Added'); 
                return $this->redirect(['/lab/request/view', 'id' =>$request_id]);

       }//else if (Yii::$app->request->isAjax) {
                $model->rstl_id = $GLOBALS['rstl_id'];
                $model->pstcanalysis_id = $GLOBALS['rstl_id'];
                $model->request_id = $request_id;
                $model->testname = $GLOBALS['rstl_id'];
                $model->cancelled = 0;
                $model->sample_id = $GLOBALS['rstl_id'];
                $model->sample_code = $GLOBALS['rstl_id'];
                $model->date_analysis = date("Y-m-d h:i:s");;

            return $this->renderAjax('_form', [
                'model' => $model,
                'request_id'=>$request_id,
                'searchModel' => $searchModel,
                'samplesearchmodel'=>$samplesearchmodel,
                'dataProvider' => $dataProvider,
                'testcategory' => $testcategory,
                'test' => $test,
                'sampletype'=>$sampletype,
                'base_sample'=>$samplesQuery,
                'sampletypewithlab'=>$this->listSampletype($labId),
            ]);
       // }   
    }
  
    /**
     * Updates an existing Analysis model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
     protected function listTestcategory($labId)
     {
         $testcategory = ArrayHelper::map(
            Sampletype::find()
            ->leftJoin('tbl_lab_sampletype', 'tbl_lab_sampletype.sampletype_id=tbl_sampletype.sampletype_id')
            ->andWhere(['lab_id'=>$labId])
            ->all(), 'sampletype_id', 
            function($testcategory, $defaultValue) {
                return $testcategory->type;
         });
         return $testcategory;
     }
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        $analysisquery = Analysis::find()->where(['analysis_id' => $id])->one();
        $samplesQuery = Sample::find()->where(['sample_id' => $analysisquery->sample_id])->all();
        $requestquery = Request::find()->where([ 'request_id'=> $analysisquery->request_id])->one();
        $paymentitem = Paymentitem::find()->where([ 'request_id'=> $analysisquery->request_id])->one();
        $request_id = $requestquery->request_id;

        
                $url = \Yii::$app->request->url;
                $rid = substr($url, 24);
                $labId =  $requestquery->lab_id;
                $testcategory = $this->listTestcategory($labId);
                $sampletype = [];
                $test = [];

            if ($model->load(Yii::$app->request->post())) { 
                        
                       
                        
                    if($model->save(false)){
                        $post= Yii::$app->request->post();
                        $modelmethod=  Testnamemethod::findOne(['testname_method_id'=>$post['Analysis']['method']]);
                        $method=  Methodreference::findOne(['method_reference_id'=>$modelmethod->method_id]);
                        $modeltest=  Testname::findOne(['testname_id'=>$post['Analysis']['test_id']]);
                        $Connection= Yii::$app->labdb;
                        $sql="UPDATE `tbl_analysis` SET `testname`='$modeltest->testName'
                        WHERE `analysis_id`=".$id;
                        $Command=$Connection->createCommand($sql);
                        $Command->execute();  
                        $post= Yii::$app->request->post();

                        $Connection= Yii::$app->labdb;
                        $sql="UPDATE `tbl_analysis` SET `method`='$method->method', `fee`='$method->fee' WHERE `analysis_id`=".$id;
                        $Command=$Connection->createCommand($sql);
                        $Command->execute();

                        $requestquery = Request::find()->where(['request_id' =>$analysisquery->request_id])->one();
                        $discountquery = Discount::find()->where(['discount_id' => $requestquery->discount_id])->one();
                        $rate =  $discountquery->rate;       
                        $sql = "SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id=$analysisquery->request_id";
                        $Connection = Yii::$app->labdb;
                        $command = $Connection->createCommand($sql);
                        $row = $command->queryOne();
                        $subtotal = $row['subtotal'];
                        $total = $subtotal - ($subtotal * ($rate/100));

                        $Connection= Yii::$app->labdb;
                        $sql="UPDATE `tbl_request` SET `total`='$total' WHERE `request_id`=".$analysisquery->request_id;
                        $Command=$Connection->createCommand($sql);
                        $Command->execute();

                    //    Yii::$app->session->setFlash('success', 'Analysis Successfully Update'); 
                        return $this->redirect(['/lab/request/view', 'id' =>$requestquery->request_id]);
                    }
                } elseif (Yii::$app->request->isAjax) {
        return $this->renderAjax('_form', [
            'model' => $model,
             'testcategory' => $testcategory,
             'request_id'=>$request_id,
            'test' => $test,
            'sampletype'=>$sampletype,
            'base_sample'=>$samplesQuery,
            'sampletypewithlab'=>$this->listSampletype($labId),
        ]);
    }else{
        return $this->render('_form', [
            'model' => $model,
             'request_id'=>$request_id,
             'testcategory' => $testcategory,
            'sampletype' => $sampletype,
            'test' => $test,
            'base_sample'=>$samplesQuery
        ]);
      }   
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
        $session = Yii::$app->session;    
            if($model->delete()) {
                $analysisquery = Analysis::find()->where(['analysis_id' => $id])->one();
                $requestquery = Request::find()->where(['request_id' =>$model->request_id])->one();

                
                $discountquery = Discount::find()->where(['discount_id' => $requestquery->discount_id])->one();
                $rate =  $discountquery->rate;       
                $sql = "SELECT SUM(fee) as subtotal FROM tbl_analysis WHERE request_id=$model->request_id";
                $Connection = Yii::$app->labdb;
                $command = $Connection->createCommand($sql);
                $row = $command->queryOne();
                $subtotal = $row['subtotal'];
                $total = $subtotal - ($subtotal * ($rate/100));
        
                $Connection= Yii::$app->labdb;
                $sql="UPDATE `tbl_request` SET `total`='$total' WHERE `request_id`=".$model->request_id;
                $Command=$Connection->createCommand($sql);
                $Command->execute();
              //  Yii::$app->session->setFlash('warning', 'Analysis Successfully Deleted'); 

                return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
            } else {
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
        if (($model = Analysis::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected function listSampletype($labId)
    {
        return $sampletype = ArrayHelper::map(Testnamemethod::find()->joinWith('sampletype')->where(['lab_id'=>$labId])->groupby('sampletype_id')->all(), 'sampletype_id', 
            function($sampletype, $defaultValue) {
                return $sampletype->sampletype['type'];
        });
    }
   
}
