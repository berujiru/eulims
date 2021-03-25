<?php

namespace frontend\modules\lab\controllers;

use Yii;
use common\models\lab\Sample;
use common\models\lab\SampleSearch;
use common\models\lab\Sampletype;
use common\models\lab\Testcategory;
use common\models\lab\Request;
use common\models\lab\Lab;
use common\models\lab\Labsampletype;
use common\models\lab\Samplecode;
use common\models\lab\SampleName;
use common\models\lab\Testnamemethod;
use common\models\lab\Analysis;
use common\models\finance\Paymentitem;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\web\Response;
use yii\db\ActiveQuery;
use common\components\Functions;
use common\components\ReferralComponent;

/**
 * SampleController implements the CRUD actions for Sample model.
 */
class SampleController extends Controller
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
                    'delete' => ['POST'],
                    'generatesamplecode'=>['GET','POST']
                ],
            ],
        ];
    }

    /**
     * Lists all Sample models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SampleSearch();
        $showData = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider = !empty($showData) ? $showData : $searchModel;
        //$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		$dataProvider->sort = false;
        $dataProvider->pagination->pageSize=10;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'sampletypes' => $this->filterSampletype(),
        ]);
    }

    /**
     * Displays a single Sample model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $searchModel = new SampleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('view', [
            'model' => $this->findModel($id),
            'dataProvider' => $dataProvider,

        ]);
    }

    /**
     * Creates a new Sample model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Sample();

        if(Yii::$app->request->get('request_id'))
        {
            $requestId = (int) Yii::$app->request->get('request_id');
        }

        $request = $this->findRequest($requestId);
        $labId = $request->lab_id;
        //$sampletype = $this->listSampletype($labId);
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        //$sampletypereferral = $this->listSampletypereferral($labId);

        $sampletype = ($request->request_type_id == 2) ? $this->listSampletypereferral($labId) : $this->listSampletype($labId);

        if ($model->load(Yii::$app->request->post())) {
            if(isset($_POST['Sample']['sampling_date'])){
                $model->sampling_date = date('Y-m-d H:i:s', strtotime($_POST['Sample']['sampling_date']));
            } else {
                $model->sampling_date = date('Y-m-d H:i:s');
            }

            $model->rstl_id = $rstlId;
            //$model->sample_code = 0;
            $model->request_id = $request->request_id;
            //$model->sample_month = date_format(date_create($request->request_datetime),'m');
            //$model->sample_year = date_format(date_create($request->request_datetime),'Y');
            //$model->sampling_date = date('Y-m-d');
			
			if($request->request_type_id == 2){
				$model->sample_month = date('n',($request->created_at));
				$model->sample_year = date('Y',($request->created_at));
			} else {
				$model->sample_month = date_format(date_create($request->request_datetime),'m');
				$model->sample_year = date_format(date_create($request->request_datetime),'Y');
			}

            if(isset($_POST['qnty'])){
                $quantity = (int) $_POST['qnty'];
            } else {
                $quantity = 1;
            }

            if(isset($_POST['sample_template']))
            {
                $this->saveSampleTemplate($_POST['Sample']['samplename'],$_POST['Sample']['description']);
            }

            if($quantity>1)
            {
                for ($i=1;$i<=$quantity;$i++)
                {
                    $sample = new Sample();

                    if(isset($_POST['Sample']['sampling_date'])){
                        $sample->sampling_date = date('Y-m-d H:i:s', strtotime($_POST['Sample']['sampling_date']));
                    } else {
                        $sample->sampling_date = date('Y-m-d H:i:s');
                    }
                    $sample->rstl_id = $rstlId;
                    //$sample->sample_code = 0;
                    //$sample->testcategory_id = (int) $_POST['Sample']['testcategory_id'];
                    //$sample->testcategory_id = 0;
                    $sample->sampletype_id = (int) $_POST['Sample']['sampletype_id'];
                    $sample->samplename = $_POST['Sample']['samplename'].' #'.$i;
                    $sample->description = $_POST['Sample']['description'];
                    $sample->customer_description = $_POST['Sample']['customer_description'];
                    $sample->request_id = $request->request_id;
                    //$sample->sample_month = date('m', strtotime($request->request_datetime));
                    //$sample->sample_year = date('Y', strtotime($request->request_datetime));
					if($request->request_type_id == 2){
						$sample->sample_month = date('n',($request->created_at));
						$sample->sample_year = date('Y',($request->created_at));
					} else {
						$sample->sample_month = date('m', strtotime($request->request_datetime));
						$sample->sample_year = date('Y', strtotime($request->request_datetime));
					}
                    $sample->save(false);
                }
				//Yii::$app->session->setFlash('success', "Sample Successfully Created.");
                return $this->redirect(['/lab/request/view', 'id' => $requestId]);
            } else {
                if($model->save(false)){
				//	Yii::$app->session->setFlash('success', "Sample Successfully Created.");
                    return $this->redirect(['/lab/request/view', 'id' => $requestId]);
                }
            }

        } elseif (Yii::$app->request->isAjax) {
		//	$model->sampling_date = date('m/d/Y h:i:s A');
			return $this->renderAjax('_form', [
				'model' => $model,
				//'testcategory' => $testcategory,
                'sampletype' => $sampletype,
				//'sampletypereferral' => $sampletypereferral,
				'labId' => $labId,
				'sampletemplate' => $this->listSampletemplate(),
			]);
        } else {
			$model->sampling_date = date('Y-m-d H:i:s');
            return $this->render('create', [
                'model' => $model,
                //'testcategory' => $testcategory,
                'sampletype' => $sampletype,
                //'sampletypereferral' => $sampletypereferral,
                'labId' => $labId,
                'sampletemplate' => $this->listSampletemplate(),
            ]);
        }
    }

    /**
     * Updates an existing Sample model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $request = $this->findRequest($model->request_id);
        $connection= Yii::$app->labdb;
        $connection->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();

        $labId = $request->lab_id;
        $oldSampletypeId = $model->sampletype_id;
        //$sampletype = $this->listSampletype($labId);
        $sampletype = ($request->request_type_id == 2) ? $this->listSampletypereferral($labId) : $this->listSampletype($labId);

        $analysisCount = Analysis::find()->where('sample_id =:sampleId',[':sampleId'=>$id])->count();

        if ($model->load(Yii::$app->request->post())) {
			
            if(isset($_POST['Sample']['sampling_date'])){
                $model->sampling_date = date('Y-m-d H:i:s', strtotime($_POST['Sample']['sampling_date']));
            } else {
                $model->sampling_date = date('Y-m-d H:i:s');
            }

            if($model->save(false)){
                return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
            } else {
                return $this->redirect(['view', 'id' => $model->request_id]);
            }

        } elseif (Yii::$app->request->isAjax) {
                return $this->renderAjax('_form', [
                    'model' => $model,
                    //'testcategory' => $testcategory,
                    'sampletype' => $sampletype,
                    //'sampletypereferral' => $sampletypereferral,
                    'labId' => $labId,
                    'sampletemplate' => $this->listSampletemplate(),
                ]);
        } else {
            return $this->render('update', [
                'model' => $model,
                //'testcategory' => $testcategory,
                'sampletype' => $sampletype,
                //'sampletypereferral' => $sampletypereferral,
                'labId' => $labId,
                'sampletemplate' => $this->listSampletemplate(),
            ]);
        }
    }

    /**
     * Deletes an existing Sample model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        //$this->findModel($id)->delete();
        //return;

        $model = $this->findModel($id);
        $analyses = Analysis::find()->where(['sample_id' => $id])->all();

        if(count($analyses) > 0){
			Yii::$app->session->setFlash('error', $model->samplename." has analysis.\nRemove first the analysis then delete this sample.");
			return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
        } else {
            if($model->delete()) {
				Yii::$app->session->setFlash('warning', 'Sample Successfully Deleted.');
                return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
            } else {
                return $model->error();
            }
        }
    }

    /**
     * Cancel an existing Sample model.
     * @param integer $id
     * @return mixed
     */
    public function actionCancel($id)
    {
        $model = $this->findModel($id);
        $sampleId = (int) $id;
        $analyses = Analysis::find()->where('sample_id =:sampleId', [':sampleId'=>$sampleId])->all();
		$connection= Yii::$app->labdb;
        $checkForPayment = $model->request->payment_status_id;

		if($checkForPayment != 1){
			Yii::$app->session->setFlash('error', "Cancel not allowed.\nOrder of Payment already created for\n".$model->request->request_ref_num.".");
			return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
		} elseif($checkForPayment > 1 && $model->active > 0) {
            $cancelSample = $this->actionCancelwithOP($model->request_id);
            if($cancelSample){
                Yii::$app->session->setFlash('warning',"Successfully Cancelled.");
                return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
            } else {
                Yii::$app->session->setFlash('error',"Cancel not successful.");
                return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
            }
        } else {
            $transaction = $connection->beginTransaction();
            if (Yii::$app->request->post()){
                if(trim($_POST['Sample']['remarks']) == ""){
                    Yii::$app->session->setFlash('error', "Remarks should not be empty.");
                    return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
                } else {
                    if(count($analyses) > 0)
                    {
                        $model->remarks = $_POST['Sample']['remarks'];
                        $model->active = 0;
                        if ($model->update(false) !== false) {
                            foreach ($analyses as $analysis) {
                                $analysis->cancelled = 1;
                                $analysis->update(false); // skip validation no user input is involved
                            }
                            $transaction->commit();
                            Yii::$app->session->setFlash('warning',"Successfully Cancelled.");
                            return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
                        } else {
                            $transaction->rollBack();
                            $model->getErrors();
                            return false;
                        }
                    } else {

                        $model->remarks = $_POST['Sample']['remarks'];
                        $model->active = 0;

                        if ($model->update(false) !== false) {
                            $transaction->commit();
                            Yii::$app->session->setFlash('warning',"Successfully Cancelled.");
                            return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
                        } else {
                            $transaction->rollBack();
                            $model->getErrors();
                            return false;
                        }
                    }
                }

            } elseif (Yii::$app->request->isAjax) {
				return $this->renderAjax('_cancel', [
					'model' => $model,
				]);
			} else {
				return $this->render('_cancel', [
					'model' => $model,
				]);
			}
		}
    }

    /**
     * Cancel an existing Sample model.
     * @param integer $id
     * @return mixed
     */
    public function actionCancelwithOP($requestId)
    {
        $samples = Sample::find()->where('request_id =:requestId', [':requestId'=>$requestId])->all();
        $connection= Yii::$app->labdb;
        $request = Request::findOne($requestId);
        $checkForPayment = $request->payment_status_id;

        if($checkForPayment > 1 && $request->status_id > 0){
            $transaction = $connection->beginTransaction();
            foreach ($samples as $sample) {
                $sample->remarks = 'Request has been cancelled.';
                $sample->active = 0;

                if ($sample->update(false) !== false) {
                    $analyses = Analysis::find()->where('sample_id =:sampleId', [':sampleId'=>$sample->sample_id])->all();
                    if(count($analyses) > 0)
                    {
                        foreach ($analyses as $analysis) {
                            $analysis->cancelled = 1;
                            $analysis->update(false); // skip validation no user input is involved
                        }
                    } 
                    $transaction->commit();
                    //Yii::$app->session->setFlash('warning',"Successfully Cancelled.");
                    //return $this->redirect(['/lab/request/view', 'id' => $model->request_id]);
                    return true;
                } else {
                    $transaction->rollBack();
                    $sample->getErrors();
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Finds the Sample model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Sample the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Sample::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function listSampletype($labId)
    {
        return $sampletype = ArrayHelper::map(Testnamemethod::find()->joinWith('sampletype')->where(['lab_id'=>$labId])->groupby('sampletype_id')->all(), 'sampletype_id', 
            function($sampletype, $defaultValue) {
                return $sampletype->sampletype['type'];
        });
    }

    protected function filterSampletype()
    {
        $sampletypes = ArrayHelper::map(Sampletype::find()->all(), 'sampletype_id',
            function($sampletypes, $defaultValue) {
                return $sampletypes->type;
        });

        return $sampletypes;
    }

  

    protected function listTestcategory($labId)
    {
        $testcategory = ArrayHelper::map(Testcategory::find()->andWhere(['lab_id'=>$labId])->all(), 'testcategory_id', 
           function($testcategory, $defaultValue) {
               return $testcategory->category_name;
        });

        /*$testcategory = ArrayHelper::map(Testcategory::find()
            ->where(['lab_id' => $labId])
            ->all(), 'testcategory_id', 'category_name');*/

        return $testcategory;
    }

    /*public function actionListsampletype() {
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $id = end($_POST['depdrop_parents']);
            $list = Sampletype::find()->andWhere(['testcategory_id'=>$id])->asArray()->all();
            $selected  = null;
            if ($id != null && count($list) > 0) {
                $selected = '';
                foreach ($list as $i => $sampletype) {
                    $out[] = ['id' => $sampletype['sample_type_id'], 'name' => $sampletype['sample_type']];
                    if ($i == 0) {
                        $selected = $sampletype['sample_type_id'];
                    }
                }
                // Shows how you can preselect a value
                echo Json::encode(['output' => $out, 'selected'=>$selected]);
                return;
            }
        }
        echo Json::encode(['output' => '', 'selected'=>'']);
    }*/

    /*protected function find()
    {
        $sampletype = ArrayHelper::map(Sampletype::find()->all(), 'sample_type_id', 
            function($sampletype, $defaultValue) {
                return $sampletype->sample_type;
        });

        return $sampletype;
    }*/

    protected function findRequest($requestId)
    {
        if (($model = Request::findOne($requestId)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionGeneratesamplecode()
    {
        $requestId = (int) Yii::$app->request->get('request_id');
        $request = $this->findRequest($requestId);
        //$lab = Lab::findOne($request->lab_id);
        $year = date('Y', strtotime($request->request_datetime));
        $connection= Yii::$app->labdb;
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        
        foreach ($request->samples as $samp){
            $transaction = $connection->beginTransaction();
            $return="false";
            try {

                $function = new Functions();
                $proc = 'spGetNextGenerateSampleCode(:rstlId,:labId,:requestId)';
                $params = [':rstlId'=>$rstlId,':labId'=>$request->lab_id,':requestId'=>$requestId];
                $row = $function->ExecuteStoredProcedureOne($proc, $params, $connection);
                $samplecodeGenerated = $row['GeneratedSampleCode'];
                $samplecodeIncrement = $row['SampleIncrement'];

                $sampleId = $samp->sample_id;
                $sample = $this->findModel($sampleId);
                
                //insert to tbl_samplecode
                $samplecode = new Samplecode();
                $samplecode->rstl_id = $rstlId;
                $samplecode->reference_num = $request->request_ref_num;
                $samplecode->sample_id = $sampleId;
                $samplecode->lab_id = $request->lab_id;
                $samplecode->number = $samplecodeIncrement;
                $samplecode->year = $year;
               
                if($samplecode->save())
                {
                    //update samplecode to tbl_sample
                    $sample->sample_code = $samplecodeGenerated;
					$sample->sample_month = date('m', strtotime($request->request_datetime));
					$sample->sample_year = date('Y', strtotime($request->request_datetime));
                    $sample->save(false); //skip validation since only update of sample code is performed
                    $transaction->commit();
                    $return="true";
                } else {
                    //error
                    $transaction->rollBack();
                    $samplecode->getErrors();
                    $return="false";
                }
                
                //$transaction->commit();

            } catch (\Exception $e) {
               $transaction->rollBack();
               $return="false";
            } catch (\Throwable $e) {
               $transaction->rollBack();
               $return="false";
            }
            
        }
        return $return;
    }

    protected function listSampletemplate()
    {
        $sampleTemplate = ArrayHelper::map(SampleName::find()->all(), 'sample_name_id', 
            function($sampleTemplate, $defaultValue) {
                return $sampleTemplate->sample_name;
        });

        return $sampleTemplate;
    }

    public function actionGetlisttemplate() {
        if(isset($_GET['template_id'])){
            $id = (int) $_GET['template_id'];
            $modelSampletemplate =  SampleName::findOne(['sample_name_id'=>$id]);
            if(count($modelSampletemplate)>0){
                $sampleName = $modelSampletemplate->sample_name;
                $sampleDescription = $modelSampletemplate->description;
            } else {
                $sampleName = "";
                $sampleDescription = "";
            }
        } else {
            $sampleName = "Error getting sample name";
            $sampleDescription = "Error getting description";
        }
        return Json::encode([
            'name'=>$sampleName,
            'description'=>$sampleDescription,
        ]);
    }

    protected function saveSampleTemplate($sampleName,$description)
    {
        $modelSampletemplate = new SampleName();

        $modelSampletemplate->sample_name = $sampleName;
        $modelSampletemplate->description = $description;
        $modelSampletemplate->save();

        return $modelSampletemplate;
    }
    //get referral sample type list
    protected function listSampletypereferral($labId)
    {
        $refcomponent = new ReferralComponent();
        $list = $refcomponent->getSampletype($labId);
        $data = [];
        if($list)
            $data = ArrayHelper::map($list, 'sampletype_id', 'type');
        return $data;
    }
}