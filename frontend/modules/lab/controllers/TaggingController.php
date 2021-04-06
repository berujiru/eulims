<?php

namespace frontend\modules\lab\controllers;

use Yii;
use common\models\lab\Tagging;
use common\models\lab\Analysis;
use common\models\lab\Request;
use common\models\lab\Sample;
use common\models\TaggingSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Query;
use yii\data\ActiveDataProvider;
use common\models\system\Profile;

/**
 * TaggingController implements the CRUD actions for Tagging model.
 */
class TaggingController extends Controller
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
     * Lists all Tagging models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TaggingSearch();
       // $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model = new Sample();
        
        $samplesQuery = Sample::find()->where(['sample_id' =>0]);
        $dataProvider = new ActiveDataProvider([
                'query' => $samplesQuery,
                'pagination' => [
                    'pageSize' => false,
                ],
             
        ]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model'=>$model,
        ]);
    }

    /**
     * Displays a single Tagging model.
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
     * Creates a new Tagging model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Tagging();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->tagging_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Tagging model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->tagging_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionUpdateanalysis($id)
    {
        $taggingmodel = Tagging::find()->where(['analysis_id'=>$id])->one();
        $analysis = Analysis::find()->where(['analysis_id'=>$id])->one();      
        $model = new Tagging();
		if(!$taggingmodel->disposed_date)
			$taggingmodel->disposed_date = date('Y-m-d',strtotime("+1 month"));
        if ($taggingmodel){
            return $this->renderAjax('updateanalysis', [
                'taggingmodel' => $taggingmodel,
            ]);
        }else{
            return $this->renderAjax('nostatus', [
                'taggingmodel' => $taggingmodel,
            ]);
        }
    }

    public function actionMonthlyreport($month, $year, $lab_id)
    {
      $nmonth = date('m', strtotime($month));
        $searchkey =  $year.'-'.$nmonth;
        $request_query = Request::find()
        ->where(['like', 'request_datetime', $searchkey ])
        ->andWhere(['lab_id'=> $lab_id,'status_id'=>1,'request_type_id'=>1])
        ->with(['samples' => function($query){
                $query->andWhere(['active'=>'1']);
            }]);

        $requestdataprovider = new ActiveDataProvider([
                'query' => $request_query,
                'pagination' => [
                    'pageSize' => false,
                ],
             
        ]); 

            return $this->renderAjax('monthlyreport', [
               'requestdataprovider' => $requestdataprovider,
                'month'=>$month,
                'year'=>$year,
                'nmonth'=>$nmonth
            ]);
    }

    public function actionReferralmonthlyreport($month, $year, $lab_id)
    {
        $nmonth = date('m', strtotime($month));
        $searchkey =  $year.'-'.$nmonth;
        if($lab_id){
             $request_query = Request::find()
                ->innerJoinWith('referralrequest')
                ->where(['like', 'request_datetime', $searchkey ])
                ->andWhere(['lab_id'=> $lab_id,'status_id'=>1,'request_type_id'=>2])
                ->with(['samples' => function($query){
                        $query->andWhere(['active'=>'1']);
            }]);
        }else{
            $request_query = Request::find()
                ->innerJoinWith('referralrequest')
                ->where(['like', 'request_datetime', $searchkey ])
                ->andWhere(['status_id'=>1,'request_type_id'=>2])
                ->with(['samples' => function($query){
                        $query->andWhere(['active'=>'1']);
            }]);
        }

        $requestdataprovider = new ActiveDataProvider([
                'query' => $request_query,
                'pagination' => [
                    'pageSize' => false,
                ],
             
        ]); 

            return $this->renderAjax('referralmonthlyreport', [
               'requestdataprovider' => $requestdataprovider,
                'month'=>$month,
                'year'=>$year,
                'nmonth'=>$nmonth
            ]);
    }

    public function actionUpdateana()
    {      

        // var_dump($_POST); exit;
        if(isset($_POST['id'])){
            $start = $_POST['start_date'];
            $end = $_POST['end_date'];
            $user_id = $_POST['user_id'];

            $manner = null;
            $disposed = null;

            if(isset($_POST['manner']) && isset($_POST['disposed_date'])){
                $manner = $_POST['manner'];
                $disposed = $_POST['disposed_date'];
            }
			
			

            $profile = Profile::find()->where(['fullname'=> $user_id])->one();

            $name = $profile->user_id;

            $id = $_POST['id'];
            $analysis_id = $_POST['id'];


            $Connection= Yii::$app->labdb;
            $sql="UPDATE `tbl_tagging` SET 
            `start_date`='$start', 
            `end_date`='$end', 
            `user_id`='$name',
            `disposed_date`='$disposed',
            `manner`='$manner'
             WHERE `analysis_id`=".$id;
            $Command=$Connection->createCommand($sql);
            $Command->execute();
			
			//get the sampleid
			$thistagging = Tagging::findOne($id);
			if($thistagging){
				$analysis = Analysis::findOne($analysis_id);
				if($analysis){
					//get all of analyses id from the samples
					$analyses = Analysis::find()->where(['sample_id' => $analysis->sample_id])->all();
					foreach($analyses as $anal){
						//query and update all tagging based on the last disposal
						$tag = Tagging::find()->where(['analysis_id'=>$anal->analysis_id])->one();
						if($tag){
							$tag->disposed_date=$disposed;
							$tag->save(false);
						}
					}
				}
			}
			//check if this is the last test updated

            $searchModel = new TaggingSearch();
            $model = new Sample();


             
            $analysis= Analysis::find()->where(['analysis_id' => $id])->one();      

            $samplesQuery = Sample::find()->where(['sample_id' =>$analysis->sample_id]);
            $sampleDataProvider = new ActiveDataProvider([
                    'query' => $samplesQuery,
                    'pagination' => [
                        'pageSize' => false,
                    ],
                 
            ]);
            $analysis_id = $analysis->sample_id;
            $analysisQuery = Analysis::find()->where(['sample_id' => $analysis->sample_id]);  
            $analysisdataprovider = new ActiveDataProvider([
                    'query' => $analysisQuery,
                    'pagination' => [
                        'pageSize' => false,
                    ],
                 
            ]);
            return $this->renderAjax('_viewAnalysis', [
                'sampleDataProvider' => $sampleDataProvider,
                'analysisdataprovider'=> $analysisdataprovider,
                'analysis_id'=>$analysis_id,
             ]);
         
            
        }else{
            return "<h3>No Data</h3>";  
         }
    }

    /**
     * Deletes an existing Tagging model.
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
     * Finds the Tagging model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Tagging the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Tagging::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionGetsamplecode($q = null, $id = null) {

        
        $GLOBALS['user_id']=Yii::$app->user->identity->profile->user_id;
        $GLOBALS['rstl_id']=Yii::$app->user->identity->profile->rstl_id;
        $profile = Profile::find()->where(['user_id'=> $GLOBALS['user_id']])->one();

        $lab = Yii::$app->user->identity->profile->lab_id;
        $year = date("Y");

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        if (!is_null($q)) {
            $query = new Query;
            $query->select('sample_id as id, sample_code AS text')
                    ->leftJoin('tbl_request', 'tbl_request.request_id=tbl_sample.request_id')
                    ->from('tbl_sample')
                    ->where(['like', 'sample_code', $q])
                    ->Andwhere(['sample_year'=>$year])
                    ->Andwhere(['tbl_sample.rstl_id'=>$GLOBALS['rstl_id']])
                    ->Andwhere(['tbl_request.lab_id'=>$profile->lab_id])
                    ->orderBy(['sample_id'=>SORT_DESC])
                    ->limit(5);
            $command = $query->createCommand();
            $command->db= \Yii::$app->labdb;
            $data = $command->queryAll();
            $out['results'] = array_values($data);
        } elseif ($id > 0) {
            $out['results'] = ['id' => $id, 'text' =>Sample::find()->where(['sample_id'=>$id])->sample_code];
        }
        return $out;
    }

    public function actionStartanalysis()
    {      
        if(isset($_POST['id'])){
			$ids = $_POST['id'];
            $analysisID = explode(",", $ids);
                
			if ($ids){
				foreach ($analysisID as $aid){
                    
                    $taggingmodel = Tagging::find()->where(['analysis_id'=>$aid])->one();
                    if ($taggingmodel){
                    }else{
                        $tagging = new Tagging();
                        $profile= Profile::find()->where(['user_id'=> Yii::$app->user->id])->one();
                        $tagging->user_id = $profile->user_id;
                        $tagging->analysis_id = $aid;
                        $tagging->start_date = date("Y-m-d");
                        $tagging->tagging_status_id = 1;
                        $tagging->reason = 1;
                        $tagging->cancelled_by = 1;
                        $tagging->iso_accredited = 1;
                        $tagging->save(false);                       	
                    }            
            }
        }
            $analysis_id = $_POST['analysis_id'];
   
            $samplesQuery = Sample::find()->where(['sample_id' =>$analysis_id]);
            $sampleDataProvider = new ActiveDataProvider([
                    'query' => $samplesQuery,
                    'pagination' => [
                        'pageSize' => false,
                    ],
                 
            ]);
            $analysisQuery = Analysis::find()->where(['sample_id' => $analysis_id]);      
            $analysisdataprovider = new ActiveDataProvider([
                    'query' => $analysisQuery,
                    'pagination' => [
                        'pageSize' => false,
                    ],
                 
            ]);
            return $this->renderAjax('_viewAnalysis', [
                'sampleDataProvider' => $sampleDataProvider,
                'analysisdataprovider'=> $analysisdataprovider,
                'analysis_id'=>$analysis_id,
             ]);
         
            
        }
            
     }

     public function actionCompletedanalysis()
     {
         
         if(isset($_POST['id'])){
             $ids = $_POST['id'];
             $analysis_id = $_POST['analysis_id'];
             $analysisID = explode(",", $ids);
             $profile= Profile::find()->where(['user_id'=> Yii::$app->user->id])->one();
             if ($ids){
                 foreach ($analysisID as $aid){
                    $tagging= Tagging::find()->where(['analysis_id'=> $aid])->one();
                    $analysis= Analysis::find()->where(['analysis_id'=> $aid])->one();
                
                    if ($tagging){
                        $now = date('Y-m-d');
                        $Connection= Yii::$app->labdb;
                        $sql="UPDATE `tbl_tagging` SET `end_date`='$now', `tagging_status_id`='2' WHERE `tagging_id`=".$tagging->tagging_id;
                        $Command=$Connection->createCommand($sql);
                        $Command->execute();                      
                        $sample= Sample::find()->where(['sample_id'=> $aid])->one();
                        //count number of completed analysis of the sample        
                        $taggingcount= Tagging::find()
                        ->leftJoin('tbl_analysis', 'tbl_tagging.analysis_id=tbl_analysis.analysis_id')
                        ->leftJoin('tbl_sample', 'tbl_analysis.sample_id=tbl_sample.sample_id')    
                        ->where(['tbl_tagging.tagging_status_id'=>2, 'tbl_sample.sample_id'=>$analysis->sample_id ])
                        ->all();                                        
                    }else{

                    }                     
             } 

             if ($taggingcount){
                $counttag = count($taggingcount); 
             } 
              $sql="UPDATE `tbl_sample` SET `completed`='$counttag' WHERE `sample_id`=".$analysis_id;
              $Command=$Connection->createCommand($sql);
              $Command->execute();                 
              $samplesq = Sample::find()->where(['sample_id' =>$analysis_id])->one();             
              $samcount = $samplesq->completed;

              $sampletagged= Sample::find()
              ->leftJoin('tbl_analysis', 'tbl_sample.sample_id=tbl_analysis.sample_id')
              ->leftJoin('tbl_tagging', 'tbl_analysis.analysis_id=tbl_tagging.analysis_id') 
              ->leftJoin('tbl_request', 'tbl_request.request_id=tbl_analysis.request_id')    
              ->where(['tbl_tagging.tagging_status_id'=>2, 'tbl_request.request_id'=>$samplesq->request_id ])
              ->all();  

              $st = count($sampletagged);

              if ($samcount==$counttag){
                $sql="UPDATE `tbl_request` SET `completed`='$st' WHERE `request_id`=".$samplesq->request_id;
                $Command=$Connection->createCommand($sql);
                $Command->execute(); 
              }
            
         }
            
             $samplesQuery = Sample::find()->where(['sample_id' =>$analysis_id]);
             $sampleDataProvider = new ActiveDataProvider([
                     'query' => $samplesQuery,
                     'pagination' => [
                         'pageSize' => false,
                     ],
                  
             ]);
             $analysisQuery = Analysis::find()->where(['sample_id' => $analysis_id]);   
             $analysisdataprovider = new ActiveDataProvider([
                     'query' => $analysisQuery,
                     'pagination' => [
                         'pageSize' => false,
                     ],
                  
             ]);
 
             return $this->renderAjax('_viewAnalysis', [
                 'sampleDataProvider' => $sampleDataProvider,
                 'analysisdataprovider'=> $analysisdataprovider,
                 'analysis_id'=>$analysis_id,
              ]);
          
             
         }
             
      }

    public function actionGetanalysis()
	{
        $id = $_GET['analysis_id'];
        $analysis_id = $id;
        $model = new Tagging();
         $samplesQuery = Sample::find()->where(['sample_id' => $id]);
         $sampleDataProvider = new ActiveDataProvider([
                 'query' => $samplesQuery,
                 'pagination' => [
                     'pageSize' => false,
                 ],       
         ]);

         $analysisQuery = Analysis::find()->where(['sample_id' => $id]);
         //$request = Request::find()->where(['request_id' =>42]);
         $analysisdataprovider = new ActiveDataProvider([
                 'query' => $analysisQuery,
                 'pagination' => [
                     'pageSize' => false,
                 ],
              
         ]);
         
         return $this->renderAjax('_viewAnalysis', [
           // 'request'=>$request,
            'model'=>$model,
            'sampleDataProvider' => $sampleDataProvider,
            'analysisdataprovider'=> $analysisdataprovider,
            'analysis_id'=>$analysis_id,
            'id'=>$id,
         ]);
	
     }
     
     public function actionSamplestatus($id)
     {
        $id = $_GET['id'];

        $request = Request::find()->where(['request_id' => $id])->one();
        $sample = Sample::find()->where(['request_id' => $id]);

       // $samplesQuery = Sample::find()->where(['sample_id' =>$analysis_id]);

        $sampledataprovider = new ActiveDataProvider([
            'query' => $sample,
            'pagination' => [
                'pageSize' => false,
                    ],                 
        ]);

        if(Yii::$app->request->isAjax){
                 return $this->renderAjax('_samplestatus', [
                'sampledataprovider'=>$sampledataprovider,
                'request'=>$request,
                ]);
        }
           
     }
}
