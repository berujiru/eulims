<?php

namespace frontend\modules\lab\controllers;

use common\models\lab\Sample;
use common\models\lab\Analysis;
use frontend\modules\lab\components\Printing;
use Yii;
use common\models\lab\Csf;
use common\models\lab\CsfSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\lab\Request;
use common\models\lab\Customer;
use yii\web\Response;

/**
 * CsfController implements the CRUD actions for Csf model.
 */
class CsfController extends Controller
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
     * Lists all Csf models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new Csf();

      //  if ($model->load(Yii::$app->request->post()) && $model->save()) 
    //    {
            if ($model->load(Yii::$app->request->post())) { 
                $strFilename = substr(md5(mt_rand()), 0, 10);
                // var_dump($model->signature);exit;   
               //  define('UPLOAD_DIR', 'images/signature');
                 $base64_string = $model->signature;//$_POST['Feedback']['signature'];
                  $data = explode(',', $base64_string);
                  $file = $path = $_SERVER['DOCUMENT_ROOT']. '/images/signature/' . $strFilename .'.png';
                  file_put_contents($file, base64_decode($data[1]));
                  $model->sigfilename =  $strFilename .'.png';
                  $model->rstl_id = 11;
                  
                  $model->save();
                 // $model->signature = $file;
 
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }

    

    public function actionReports()
    {
        $model = new Csf();
        
        $currentYear = date('Y');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
        $arrMonthlyCSF = array();
        
//        for ($x = 1; $x <= 12; $x++) {
//         $intCSF = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(" . $x . ",2020);")->queryAll();
//         array_push($arrMonthlyCSF,$intCSF);
//        } 
        
         $intCSF2 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(1,".date('Y').");")->queryAll();
         
        $intArray=array();
        foreach($arrMonthlyCSF as $csf)
        {
            array_push($intArray,$csf);
        }
        
        $csf1 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(1," . $currentYear . ");")->queryAll();
        $csf2 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(2," . $currentYear . ");")->queryAll();
        $csf3 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(3," . $currentYear . ");")->queryAll();
        $csf4 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(4," . $currentYear . ");")->queryAll();
        $csf5 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(5," . $currentYear . ");")->queryAll();
        $csf6 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(6," . $currentYear . ");")->queryAll();
        $csf7 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(7," . $currentYear . ");")->queryAll();
        $csf8 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(8," . $currentYear . ");")->queryAll();
        $csf9 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(9," . $currentYear . ");")->queryAll();
        $csf10 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(10," . $currentYear . ");")->queryAll();
        $csf11 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(11," . $currentYear . ");")->queryAll();
        $csf12 = Yii::$app->labdb->createCommand("CALL spGetCSIReportValue(12," . $currentYear . ");")->queryAll();
        // Yii::$app->labdb->createCommand("CALL spGetCSIValue(1,2020,@ival);")->execute();
        return $this->render('reports', [
            'model' => $model,'jancsf'=>$intCSF2[0],'intArray'=>$intArray,'csf1'=>$csf1[0],'csf2'=>$csf2[0],'csf3'=>$csf3[0],'csf4'=>$csf4[0],'csf5'=>$csf5[0],'csf6'=>$csf6[0],
            'csf7'=>$csf7[0],'csf8'=>$csf8[0],'csf9'=>$csf9[0],'csf10'=>$csf10[0],'csf11'=>$csf11[0],'csf12'=>$csf12[0],'currentYear'=>$currentYear
        ]);
    }

    public function actionMonthlyreport()
    {
        $model = new Csf();

        // if ($model->load(Yii::$app->request->post()) && $model->save()) {
        //     return $this->redirect(['view', 'id' => $model->id]);
        // }

        return $this->render('day', [
            'model' => $model,
        ]);
    }

    public function actionCustomer()
    {
        $model = new Csf();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('day', [
            'model' => $model,
        ]);
    }

    public function actionResult()
    {
        $model = new Csf();

        $searchModel = new CsfSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('results', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model'=>$model,
        ]);
    }

    public function actionCsireport()
    {
        $searchModel = new CsfSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);


        return $this->render('csireport', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    
    public function actionCsf()
    {  
        $pMonth = Yii::$app->request->get('csfmonth');
        $csf = Csf::find()->andWhere('Month(r_date) =' .  $pMonth)->andWhere('Year(r_date) = '.date('Y'))->all();
        return $this->asJson([$csf]);             
    }

    public function actionCsi()
    {
        $pMonth = Yii::$app->request->get('csfmonth');
        $monthName = date("F", mktime(0, 0, 0, $pMonth, 10));
        $searchModel = new CsfSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $csf = Csf::find()->andWhere('Month(r_date) =' .  $pMonth)->andWhere('Year(r_date) = '.date('Y'))->all();
        $count = count($csf);
        return $this->render('csi', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'csf'=>$csf,'monthName'=>$monthName,'pMonth'=>$pMonth,
            'count'=>$count
        ]);
    }

    public function actionResultmodal($id)
    {
        if(Yii::$app->request->isAjax){
            return $this->renderAjax('results_modal', [
                    'model' => $this->findModel($id),
                ]);
        }
    }

    /**
     * Displays a single Csf model.
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
     * Creates a new Csf model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Csf();

      // if ($model->load(Yii::$app->request->post()) && $model->save()) {

           if ($model->load(Yii::$app->request->post())) { 
               // var_dump($model->signature);exit;   
              //  define('UPLOAD_DIR', 'images/signature');
                $base64_string = $model->signature;//$_POST['Feedback']['signature'];
                 $data = explode(',', $base64_string);
                 $file = 'images/signature/' . $model->name . '.png';
                 file_put_contents($file, base64_decode($data[1]));
                 $model->sigfilename =  $model->name . '.png';
                 $model->save();
                // $model->signature = $file;

            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->service=0;
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionGetcustomer()
	{
        
        $ref_num = $_GET['ref_num'];

         if(isset($_GET['ref_num'])){
            $id = $_GET['ref_num'];

            $request = Request::find()->where(['request_ref_num'=>$id])->all();
            $customer = Customer::find()->where(['customer_id'=>$request->customer_id])->all();
            $customer_name = $customer->customer_name;
        } else {
            $customer_name = "Error getting customer name";
         }
        return Json::encode([
            'custo'=>$title,
        ]);

	
     }

    /**
     * Updates an existing Csf model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Csf model.
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
     * Finds the Csf model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Csf the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Csf::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    

     public function actionPrintreport(){
      $Printing=new Printing();
      $Printing->PrintReportcsi(20);
  }

  public function actionPrintmonthly(){
    $Printing=new Printing();
    $Printing->PrintReportmonthly(20);
}

public function actionPrintcsfmonthly(){
    $imonth=0;
    $pMonth = Yii::$app->request->get('csfmonth');
    $iyear = date("Y"); 
    $Printing=new Printing();
    $Printing->Printcsfmonthly($pMonth,$iyear);
}

public function actionPrintcustomer(){
    $Printing=new Printing();
    $Printing->PrintReportdaily(20);
}

    

}
