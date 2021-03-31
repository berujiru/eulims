<?php

namespace frontend\modules\reports\modules\lab\controllers;

use Yii;
use yii\web\Controller;
use common\models\lab\Sample;
use common\models\lab\SampleSearch;
use common\models\lab\Request;
use frontend\modules\reports\modules\models\Requestextension;
use frontend\modules\reports\modules\models\Requestextend;
use common\models\lab\Lab;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\Json;
use Datetime;
use frontend\modules\reports\modules\models\AccomplishmentRstl;
use frontend\modules\reports\modules\models\AccomplishmentRstlRealtime;
use frontend\modules\reports\modules\models\AccomplishmentOverall;
use frontend\modules\reports\modules\models\AccomplishmentOverallRealtime;
use common\models\lab\Reportsummary;

class AccomplishmentController extends \yii\web\Controller
{
	/*
	Created By: Bergel T. Cutara
	Contacts:

	Email: b.cutara@gmail.com
	Tel. Phone: (062) 991-1024
	Mobile Phone: (639) 956200353

	Description: looks up all the request made in a certain time
	**/

    public function actionIndex()
    {
        set_time_limit(0);
        $model = new Requestextension;
    	$rstlId = Yii::$app->user->identity->profile->rstl_id;
    	
		if (Yii::$app->request->get())
		{
			$labId = (int) Yii::$app->request->get('lab_id');
			$year = (int) Yii::$app->request->get('year');
			
		} else {
			$labId = 1;
			$year = date('Y'); //current year
		}

		$modelRequest = Requestextension::find()
		->select([
			'monthnum'=>'DATE_FORMAT(`request_datetime`, "%m")',
			'month'=>'DATE_FORMAT(`request_datetime`, "%M")',
			'totalrequests' => 'count(request_id)',
			'total'=>'SUM(total)',
			'request_datetime',
		])
		->where('rstl_id =:rstlId AND status_id > :statusId AND lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y") = :year AND request_ref_num != "" AND request_type_id = 1', [':rstlId'=>$rstlId,':statusId'=>0,':labId'=>$labId,':year'=>$year])
		->groupBy(['DATE_FORMAT(request_datetime, "%Y-%m")'])
		->orderBy('request_datetime ASC');

         
		$dataProvider = new ActiveDataProvider([
            'query' => $modelRequest,
            'pagination' => false,
        ]);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('index', [
                'dataProvider' => $dataProvider,
                'lab_id' => $labId,
                'year' => $year,
                'model'=>$modelRequest,
	            'laboratories' => $this->listLaboratory(),
            ]);
        } else {
			return $this->render('index', [
	            'dataProvider' => $dataProvider,
	            'lab_id' => $labId,
	            'model'=>$modelRequest,
                'year' => $year,
	            'laboratories' => $this->listLaboratory(),
	        ]);
		}
	}

    public function actionFirms(){



        $model = new Requestextend;
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        
        if (Yii::$app->request->get())
        {
           
            $year = (int) Yii::$app->request->get('year');
            
        } else {
           
            $year = date('Y'); //current year
        }

        $modelRequest = Requestextend::find()
        ->select([
            'monthnum'=>'DATE_FORMAT(`request_datetime`, "%m")',
            'month'=>'DATE_FORMAT(`request_datetime`, "%M")',
            'totalrequests' => 'count(request_id)',
            'total'=>'SUM(total)',
            'request_datetime',
            'yearmonth'=>'DATE_FORMAT(request_datetime, "%Y-%m")',
        ])
        ->where('rstl_id =:rstlId AND status_id > :statusId AND DATE_FORMAT(`request_datetime`, "%Y") = :year AND request_ref_num != ""', [':rstlId'=>$rstlId,':statusId'=>0,':year'=>$year])
        ->groupBy(['month'])
        ->orderBy('monthnum ASC');


    
        // var_dump($modelRequest); exit;
        $dataProvider = new ActiveDataProvider([
            'query' => $modelRequest,
            'pagination' => false,
        ]);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('firm', [
                'dataProvider' => $dataProvider,
                'year' => $year,
                'model'=>$modelRequest,
                'laboratories' => $this->listLaboratory(),
            ]);
        } else {
            return $this->render('firm', [
                'dataProvider' => $dataProvider,
                'model'=>$modelRequest,
                'year' => $year,
                'laboratories' => $this->listLaboratory(),
            ]);
        }
    }

    public function actionValidate($data){  
        $data=json_decode($data);   
        return $this->renderAjax('validate',['data'=>$data]);   
    }   

    public function actionSaveaccomplishment($data){    
        $data=json_decode($data);   
        // var_dump($); exit;   
        $summary = new Reportsummary;   
        $summary->rstl_id =Yii::$app->user->identity->profile->rstl_id; 
        $summary->year =$data->year;    
        // $summary->month =$data->month;   
        // $date = 'July';  
        $date = date('m', strtotime($data->month)); 
        $summary->month =$date; 
        $summary->request =$data->requests; 
        $summary->sample =$data->samples;   
        $summary->test =$data->analyses;    
        $summary->actualfees =$data->fees;  
        $summary->gratis =$data->gratis;    
        $summary->discount =$data->discounts;   
        $summary->gross =$data->gross;  
        $summary->lab_id=$data->labid;  
        if($summary->save(false)){  
            // echo "saved"; exit;  
            Yii::$app->session->setFlash('success', 'Accomplishment Successfully Added');   

        }else{  
            Yii::$app->session->setFlash('danger', 'Accomplishment Failed to Save!');   

        }   

        return $this->redirect(['/reports/lab/accomplishment/']);   

    }

    public function actionShows($monthyear,$type=null){
        $reqs =  Requestextend::find()->where(['DATE_FORMAT(`request_datetime`, "%Y-%m")' => $monthyear])->andWhere(['>','status_id',0])->leftJoin('tbl_customer', 'tbl_request.customer_id=tbl_customer.customer_id')->andWhere(['tbl_customer.classification_id'=>$type]);
         $dataProvider = new ActiveDataProvider([
            'query' => $reqs,
            'pagination' => false,
        ]);
            return $this->render('customerlist', [
                'dataProvider'=>$dataProvider,
                'monthyear'=>$monthyear
            ]);
        
    }
	
	public function actionMontly($id)
    {
        // $taggingmodel = Tagging::find()->where(['analysis_id'=>$id])->one();
        // $analysis = Analysis::find()->where(['analysis_id'=>$id])->one();      
        // $model = new Tagging();
        
        // if ($taggingmodel){
            return $this->renderAjax('monthly');
        // }else{
        //     return $this->renderAjax('monthly', [
        //         'taggingmodel' => $taggingmodel,
        //     ]);
        // }

      
    }
    
    public function actionRealtimedata()
    {
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        $now = new DateTime();
                $currentyear=$now->format('Y');
                $currentmonth= $now->format('m');
                $currentmonthchar=strtolower($now->format('M'));
         //   return $this->renderAjax('monthly');
      
        $kpi = ['samples','tests','customers','newcustomers','firms','fees','csi'];
        
        foreach ($kpi as $kpirec)
        {
         $accorealtime = AccomplishmentRstlRealtime::find()->andWhere('indicator = "' . $kpirec . '"')->andWhere('yeardata = ' . $currentyear)->andWhere('rstl_id = '. $rstlId)->andWhere('type = "Accomplishments"')->one();
     
         Yii::$app->labdb->createCommand("CALL spPerformanceDashboardRealtime('" . $kpirec . "'," . $currentmonth . ",'" . $currentmonthchar  . "',". $currentyear .",'Accomplishments','". $rstlId ."');")->execute();
         $acco = AccomplishmentRstl::find()->andWhere('indicator = "' . $kpirec . '"')->andWhere('yeardata = ' . $currentyear)->andWhere('rstl_id = '. $rstlId)->andWhere('type = "Accomplishments"')->one();
           
            
            $accorealtime->chem_all = $acco->chem_all;
            $accorealtime->micro_all = $acco->micro_all;
            $accorealtime->metro_all= $acco->metro_all;
            
            $accorealtime->halal_all = $acco->halal_all;
            $accorealtime->chemmicro_all = $acco->chemmicro_all;
            $accorealtime->all = $acco->all;
            
         
            $accorealtime->janchem = $acco->janchem;
            $accorealtime->janmicro = $acco->janmicro;
            $accorealtime->janmetro= $acco->janmetro;
            
            $accorealtime->febchem = $acco->febchem;
            $accorealtime->febmicro = $acco->febmicro;
            $accorealtime->febmetro= $acco->febmetro;
            
            $accorealtime->marchem = $acco->marchem;
            $accorealtime->marmicro = $acco->marmicro;
            $accorealtime->marmetro= $acco->marmetro;
            
            $accorealtime->aprchem = $acco->aprchem;
            $accorealtime->aprmicro = $acco->aprmicro;
            $accorealtime->aprmetro= $acco->aprmetro;
            
            $accorealtime->maychem = $acco->maychem;
            $accorealtime->maymicro = $acco->maymicro;
            $accorealtime->maymetro= $acco->maymetro;
            
            $accorealtime->junchem = $acco->junchem;
            $accorealtime->junmicro = $acco->junmicro;
            $accorealtime->junmetro= $acco->junmetro;
            
            $accorealtime->julchem = $acco->julchem;
            $accorealtime->julmicro = $acco->julmicro;
            $accorealtime->julmetro= $acco->julmetro;
            
            $accorealtime->augchem = $acco->augchem;
            $accorealtime->augmicro = $acco->augmicro;
            $accorealtime->augmetro= $acco->augmetro;
            
            $accorealtime->sepchem = $acco->sepchem;
            $accorealtime->sepmicro = $acco->sepmicro;
            $accorealtime->sepmetro= $acco->sepmetro;
            
            $accorealtime->octchem = $acco->octchem;
            $accorealtime->octmicro = $acco->octmicro;
            $accorealtime->octmetro= $acco->octmetro;
            
            $accorealtime->novchem = $acco->novchem;
            $accorealtime->novmicro = $acco->novmicro;
            $accorealtime->novmetro= $acco->novmetro;
            
            $accorealtime->decchem = $acco->decchem;
            $accorealtime->decmicro = $acco->decmicro;
            $accorealtime->decmetro= $acco->decmetro;
         
         
            
            $accorealtime->save();
            
            
            $overrealtime = AccomplishmentOverallRealtime::find()->andWhere('indicator = "' . $kpirec . '"')->andWhere('yeardata = ' . $currentyear)->andWhere('type = "Accomplishments"')->one();
     
            
            Yii::$app->labdb->createCommand("CALL spUpdateAllTotalPerformance('" . $kpirec . "'," . $currentyear . ",'Accomplishments','". $rstlId ."');")->execute();
            
            $over = AccomplishmentOverall::find()->andWhere('indicator = "' . $kpirec . '"')->andWhere('yeardata = ' . $currentyear)->andWhere('type = "Accomplishments"')->one();
       
            
            $overrealtime->region1 = $over->region1;
            $overrealtime->region2 = $over->region2;
            $overrealtime->region3 = $over->region3;
            $overrealtime->region4 = $over->region4;
            $overrealtime->region4b = $over->region4b;
            $overrealtime->region5 = $over->region5;
            $overrealtime->region6 = $over->region6;
            $overrealtime->region7 = $over->region7;
            $overrealtime->region8 = $over->region8;
            $overrealtime->region9 = $over->region9;
            $overrealtime->region10 = $over->region10;
            $overrealtime->region11 = $over->region11;
            $overrealtime->region12 = $over->region12;
            $overrealtime->car = $over->car;
            $overrealtime->caraga = $over->caraga;
            $overrealtime->barmm = $over->barmm;
            
            $overrealtime->totalrstl = $over->totalrstl;
            $overrealtime->totalrdi = $over->totalrdi;
            $overrealtime->total = $over->total;
           // $accorealtime->janmicro = $acco->janmicro;
          //  $accorealtime->janmetro= $acco->janmetro;
            $overrealtime->save();
            
         
        }
        
        
//         Yii::$app->labdb->createCommand("CALL spPerformanceDashboardRealtime('tests'," . $currentmonth . ",'" . $currentmonthchar  . "',". $currentyear .",'Accomplishments','".  $rstlId ."');")->execute();
//         Yii::$app->labdb->createCommand("CALL spPerformanceDashboardRealtime('customers'," . $currentmonth . ",'" . $currentmonthchar  . "',". $currentyear .",'Accomplishments','".  $rstlId ."');")->execute();
//         Yii::$app->labdb->createCommand("CALL spPerformanceDashboardRealtime('newcustomers'," . $currentmonth . ",'" . $currentmonthchar  . "',". $currentyear .",'Accomplishments','".  $rstlId ."');")->execute();
//         Yii::$app->labdb->createCommand("CALL spPerformanceDashboardRealtime('firms'," . $currentmonth . ",'" . $currentmonthchar  . "',". $currentyear .",'Accomplishments','".  $rstlId ."');")->execute();
//         Yii::$app->labdb->createCommand("CALL spPerformanceDashboardRealtime('fees'," . $currentmonth . ",'" . $currentmonthchar  . "',". $currentyear .",'Accomplishments','".  $rstlId ."');")->execute();
//         Yii::$app->labdb->createCommand("CALL spPerformanceDashboardRealtime('csi'," . $currentmonth . ",'" . $currentmonthchar  . "',". $currentyear .",'Accomplishments','".  $rstlId ."');")->execute();
        
       //  $pMonth = Yii::$app->request->get('csfmonth');
        
//        $acco = AccomplishmentRstl::find()->andWhere('indicator = "' . "samples" . '"')->andWhere('yeardata = ' . $currentyear)->andWhere('rstl_id = '. $rstlId)->andWhere('type = "Accomplishments"')->one();
//   //     $accorealtime = AccomplishmentRstlRealtime::find()->andWhere('yeardata =2020')->andWhere('region = "DOST-IX"')->andWhere('type = "Accomplishments"')->andWhere('indicator = "samples"')->one();
//     
//      $accorealtime->marchem = $acco->marchem;
//      $accorealtime->marmicro = $acco->marmicro;
//      $accorealtime->marmetro= $acco->marmetro;
//        $accorealtime->save();
//        
   //     return $this->render('index');
     //   return $this->asJson([$accorealtime]); 
      
        $model = new Requestextend;
    	$rstlId = Yii::$app->user->identity->profile->rstl_id;
    	
		if (Yii::$app->request->get())
		{
			$labId = (int) Yii::$app->request->get('lab_id');
			$year = (int) Yii::$app->request->get('year');
			
		} else {
			$labId = 1;
			$year = date('Y'); //current year
		}

		$modelRequest = Requestextend::find()
		->select([
			'monthnum'=>'DATE_FORMAT(`request_datetime`, "%m")',
			'month'=>'DATE_FORMAT(`request_datetime`, "%M")',
			'totalrequests' => 'count(request_id)',
			'total'=>'SUM(total)',
			'request_datetime'
		])
		->where('rstl_id =:rstlId AND status_id > :statusId AND lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y") = :year AND request_ref_num != ""', [':rstlId'=>$rstlId,':statusId'=>0,':labId'=>$labId,':year'=>$year])
		->groupBy(['DATE_FORMAT(request_datetime, "%Y-%m")'])
		->orderBy('request_datetime ASC');


	
		// var_dump($modelRequest); exit;
		$dataProvider = new ActiveDataProvider([
            'query' => $modelRequest,
            'pagination' => false,
        ]);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('index', [
                'dataProvider' => $dataProvider,
                'lab_id' => $labId,
                'year' => $year,
                'model'=>$modelRequest,
	            'laboratories' => $this->listLaboratory(),
            ]);
        } else {
			return $this->render('index', [
	            'dataProvider' => $dataProvider,
	            'lab_id' => $labId,
	            'model'=>$modelRequest,
                'year' => $year,
	            'laboratories' => $this->listLaboratory(),
	        ]);
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
