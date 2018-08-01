<?php

namespace frontend\modules\reports\modules\lab\controllers;

use Yii;
use yii\web\Controller;
use common\models\lab\Sample;
use common\models\lab\Customer;
use common\models\lab\Request;
use frontend\modules\reports\modules\models\Requestextend;
use frontend\modules\reports\modules\models\Customerextend;
use common\models\lab\Lab;
use common\models\lab\Businessnature;
use common\models\lab\Industrytype;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\Json;

class StatisticController extends Controller
{
    // public function actionIndex()
    // {
    //     return $this->render('index');
    // }

    public function actionSamples()
    {
    	$model = new Requestextend;
		if (Yii::$app->request->get())
		{
			$labId = (int) Yii::$app->request->get('lab_id');
            $rstlId = Yii::$app->user->identity->profile->rstl_id;
			
			if($this->checkValidDate(Yii::$app->request->get('from_date')) == true)
			{
		        $fromDate = Yii::$app->request->get('from_date');
			} else {
				$fromDate = date('Y-m-d');
				Yii::$app->session->setFlash('error', "Not a valid date!");
			}

			if($this->checkValidDate(Yii::$app->request->get('to_date')) == true){
				$toDate = Yii::$app->request->get('to_date');
			} else {
				$toDate = date('Y-m-d');
				Yii::$app->session->setFlash('error', "Not a valid date!");
			}
		} else {
			$labId = 1;
			$fromDate = date('Y-m-01'); //first day of the month
			$toDate = date('Y-m-d'); //as of today
		}

		$modelRequest = Requestextend::find()
					->where('rstl_id =:rstlId AND status_id != :statusId AND lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate', [':rstlId'=>$rstlId,':statusId'=>2,':labId'=>$labId,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate])
					->groupBy(['DATE_FORMAT(request_datetime, "%Y-%m-%d")'])
					->orderBy('request_datetime DESC');

		$dataProvider = new ActiveDataProvider([
            'query' =>$modelRequest,
            'pagination' => [
                'pagesize' => 10,
            ],
        ]);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('samplestat', [
                'dataProvider' => $dataProvider,
                'lab_id' => $labId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'model'=>$modelRequest,
	            'laboratories' => $this->listLaboratory(),
            ]);
        } else {
			return $this->render('samplestat', [
	            //'searchModel' => $searchModel,
	            'dataProvider' => $dataProvider,
	            'lab_id' => $labId,
	            'model'=>$modelRequest,
                'from_date' => $fromDate,
                'to_date' => $toDate,
	            'laboratories' => $this->listLaboratory(),
	        ]);
		}
    }

    public function actionCustomers()
    {
        //$model = new Customerextend;


        $filtertype = (int) Yii::$app->request->get('filtertype',0);
        $rstlId = Yii::$app->user->identity->profile->rstl_id;

        if (Yii::$app->request->get())
        {
            $labId = (int) Yii::$app->request->get('lab_id');
            $businessnature = (int) Yii::$app->request->get('businessnature_id');
            $industrytype = (int) Yii::$app->request->get('industrytype_id');
            
            if($this->checkValidDate(Yii::$app->request->get('from_date')) == true)
            {
                $fromDate = Yii::$app->request->get('from_date');
            } else {
                $fromDate = date('Y-m-d');
                Yii::$app->session->setFlash('error', "Not a valid date!");
            }

            if($this->checkValidDate(Yii::$app->request->get('to_date')) == true){
                $toDate = Yii::$app->request->get('to_date');
            } else {
                $toDate = date('Y-m-d');
                Yii::$app->session->setFlash('error', "Not a valid date!");
            }
        } else {
            $labId = 1;
            $fromDate = date('Y-01-01'); //first day of the month
            $toDate = date('Y-m-d'); //as of today
        }


        if($filtertype == 1){
            $modelCustomer = Customerextend::find()
                //->leftJoin('tbl_requests', '`tbl_requests`.`customer_id` = `tbl_customer`.`customer_id`')
                ->innerJoinWith('requests')
                ->where('tbl_request.rstl_id =:rstlId AND tbl_request.lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND tbl_request.status_id <> :statusId AND business_nature_id =:businessnatureId', [':rstlId'=>$rstlId,':labId'=>$labId,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate,':statusId'=>2,':businessnatureId'=>$businessnature])
                ->groupBy(['tbl_customer.customer_id']);
        } elseif($filtertype == 2) {
            $modelCustomer = Customerextend::find()
                //->leftJoin('tbl_requests', '`tbl_requests`.`customer_id` = `tbl_customer`.`customer_id`')
                ->innerJoinWith('requests')
                ->where('tbl_request.rstl_id =:rstlId AND tbl_request.lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND tbl_request.status_id <> :statusId AND industrytype_id =:industrytypeId', [':rstlId'=>$rstlId,':labId'=>$labId,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate,':statusId'=>2,':industrytypeId'=>$industrytype])
                ->groupBy(['tbl_customer.customer_id']);
        } else {
            $modelCustomer = Customerextend::find()
                //->leftJoin('tbl_requests', '`tbl_requests`.`customer_id` = `tbl_customer`.`customer_id`')
                ->innerJoinWith('requests')
                ->where('tbl_request.rstl_id =:rstlId AND tbl_request.lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND tbl_request.status_id <> :statusId', [':rstlId'=>$rstlId,':labId'=>$labId,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate,':statusId'=>2])
                ->groupBy(['tbl_customer.customer_id']);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $modelCustomer,
            'pagination' => false,
            // 'pagination' => [
            //     'pagesize' => 10,
            // ],
        ]);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('customer', [
                'dataProvider' => $dataProvider,
                'lab_id' => $labId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'laboratories' => $this->listLaboratory(),
                'businessnature' => $this->listBusinessNature(),
                'industrytype' => $this->listIndustryType(),
            ]);
        } else {
            return $this->render('customer', [
                'dataProvider' => $dataProvider,
                'lab_id' => $labId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'laboratories' => $this->listLaboratory(),
                'businessnature' => $this->listBusinessNature(),
                'industrytype' => $this->listIndustryType(),
            ]);
        }
    }

    public function actionViewrequests()
    {
        $customerId = (int) Yii::$app->request->get('customerId');
        $labId = (int) Yii::$app->request->get('labId');
        $fromDate = Yii::$app->request->get('from_date');
        $toDate = Yii::$app->request->get('to_date');
        $filtertype = (int) Yii::$app->request->get('filtertype',0);
        $filterId = (int) Yii::$app->request->get('filterId',0);

        $rstlId = Yii::$app->user->identity->profile->rstl_id;

        if($customerId > 0){

            $modelCustomer = Customer::findOne($customerId);

            if($filtertype == 1){
                $modelRequest = Request::find()
                ->innerJoinWith('customer')
                ->where('tbl_request.customer_id =:customerId AND tbl_request.rstl_id =:rstlId AND lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND status_id <> :statusId AND business_nature_id =:businessnatureId', [':customerId'=>$customerId,':rstlId'=>$rstlId,':labId'=>$labId,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate,':statusId'=>2,':businessnatureId'=>$filterId]);
            } elseif($filtertype == 2){
                $modelRequest = Request::find()
                ->innerJoinWith('customer')
                ->where('tbl_request.customer_id =:customerId AND tbl_request.rstl_id =:rstlId AND lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND status_id <> :statusId AND industrytype_id =:industrytypeId', [':customerId'=>$customerId,':rstlId'=>$rstlId,':labId'=>$labId,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate,':statusId'=>2,':industrytypeId'=>$filterId]);
            } else {
                $modelRequest = Request::find()
                ->where('customer_id =:customerId AND rstl_id =:rstlId AND lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND status_id <> :statusId',[':customerId'=>$customerId,':rstlId'=>$rstlId,':labId'=>$labId,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate,':statusId'=>2]);
            }

            $dataProvider = new ActiveDataProvider([
                'query' => $modelRequest,
                'pagination' => false,
            ]);

            if (Yii::$app->request->isAjax) {
                return $this->renderAjax('_request', [
                    'model' => $modelCustomer,
                    'dataProvider'=>$dataProvider,
                ]);
            } else {
                // return $this->renderAjax('_request', [
                //     'model' => $modelCustomer,
                //     'dataProvider'=>$dataProvider,
                // ]);
                Yii::$app->session->setFlash('error', "Invalid Request!");
            }
        } else {
            Yii::$app->session->setFlash('error', "Invalid Request!");
        }
    }

    protected function listLaboratory()
    {
        $laboratory = ArrayHelper::map(Lab::find()->all(), 'lab_id', 
            function($laboratory, $defaultValue) {
                return $laboratory->labname;
        });

        return $laboratory;
    }

    protected function listBusinessNature()
    {
        $businessnature = ArrayHelper::map(Businessnature::find()->all(), 'business_nature_id', 
            function($businessnature, $defaultValue) {
                return $businessnature->nature;
        });

        return $businessnature;
    }

    protected function listIndustryType()
    {
        $industrytype = ArrayHelper::map(Industrytype::find()->all(), 'industrytype_id', 
            function($industrytype, $defaultValue) {
                return $industrytype->industry;
        });

        return $industrytype;
    }

	function checkValidDate($date){
		$tempdate = explode('-', $date);

		if(count($tempdate) < 3 || count($tempdate) > 3)
		{
			return false;
		} else {
			$month = (int) $tempdate[1];
			$year = (int) $tempdate[0];
			$day = (int) $tempdate[2];
			// checkdate(month, day, year)
			if(checkdate($month,$day,$year) == true){
				return true;
			} else {
				return false;
			}
		}
	}
}
