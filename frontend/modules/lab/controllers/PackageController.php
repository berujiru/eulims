<?php

namespace frontend\modules\lab\controllers;

use Yii;
use common\models\lab\Package;
use common\models\lab\PackageSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Json;

/**
 * PackageController implements the CRUD actions for Package model.
 */
class PackageController extends Controller
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
     * Lists all Package models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PackageSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Package model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        if(Yii::$app->request->isAjax){
            return $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]);
        }
    }

    /**
     * Creates a new Package model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Package();
        $GLOBALS['rstl_id']=Yii::$app->user->identity->profile->rstl_id;
        $post= Yii::$app->request->post();
        $sampletype = [];
        if ($model->load(Yii::$app->request->post())) {
                   $model = new Package();
                    $model->rstl_id= $GLOBALS['rstl_id'];
                    $model->testcategory_id=11;
                    $model->sampletype_id= $post['Package']['sampletype_id'];
                    $model->name= $post['Package']['name'];
                    $model->rate= $post['Package']['rate'];
                    $model->tests= $post['sample_ids'];
                    $model->save(); 
                    Yii::$app->session->setFlash('success', 'Package Successfully Created'); 
                    return $this->runAction('index');
                }          
                if(Yii::$app->request->isAjax){
                    $GLOBALS['rstl_id']=Yii::$app->user->identity->profile->rstl_id;
                    $model->rstl_id= $GLOBALS['rstl_id'];
                    
                    $model->testcategory_id=1;
                    return $this->renderAjax('_form', [
                        'model' => $model,
                        'sampletype'=>$sampletype
                    ]);
               }
    }
    /**
     * Updates an existing Package model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        // $model = $this->findModel($id);

        // if ($model->load(Yii::$app->request->post()) && $model->save()) {
        //     return $this->redirect(['view', 'id' => $model->id]);
        // }

        // return $this->render('update', [
        //     'model' => $model,
        // ]);

        $model = $this->findModel($id);
        $sampletype = [];
                if ($model->load(Yii::$app->request->post()) && $model->save()) {
                    Yii::$app->session->setFlash('success', 'Package Successfully Updated'); 
                    return $this->redirect(['index']);

                } else if (Yii::$app->request->isAjax) {
                    return $this->renderAjax('_form', [
                        'model' => $model,
                        'sampletype'=>$sampletype
                    ]);
                 }
    }

    /**
     * Deletes an existing Package model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id); 
        if($model->delete()) {            
            Yii::$app->session->setFlash('success', 'Package Successfully Deleted'); 
            return $this->redirect(['index']);
        } else {
            return $model->error();
        }
    }

    /**
     * Finds the Package model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Package the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Package::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    public function actionGetpackage() {
        if(isset($_GET['packagelist_id'])){
            $id = (int) $_GET['packagelist_id'];
            $modelpackagelist =  Package::findOne(['id'=>$id]);
            if(count($modelpackagelist)>0){
                $rate = $modelpackagelist->rate;
                $tet = $modelpackagelist->tests;
                $sql = "SELECT GROUP_CONCAT(testName) FROM tbl_testname WHERE testname_id IN ($tet)";             
                $Connection = Yii::$app->labdb;
                $command = $Connection->createCommand($sql);
                $row = $command->queryOne();    
                $tests = $row['GROUP_CONCAT(testName)'];                
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
    }
}
