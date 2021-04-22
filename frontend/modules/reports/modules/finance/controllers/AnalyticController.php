<?php
namespace frontend\modules\reports\modules\finance\controllers;

use Yii;
use common\models\lab\Reportsummary;
use common\models\lab\Sample;
use common\models\lab\Reportform;
use frontend\modules\reports\modules\models\Requestextend;
use frontend\modules\reports\modules\models\Requestextension;
use common\models\lab\Request;
use common\models\lab\Lab;
use common\models\lab\Businessnature;
use common\models\lab\Reportholder;
use common\models\lab\Sampletype;
use common\models\lab\Factors;
use common\models\lab\Reportfactors;

class AnalyticController extends \yii\web\Controller
{

    public function actionDisplaymonth($data)
    {
    	$exploded = explode("_", $data);
    	$rstlId = Yii::$app->user->identity->profile->rstl_id; //get the rstlid
        $factors = Reportfactors::find()->with('factor')->where(['yearmonth'=>$exploded[0],'lab_id'=>$exploded[1]])->all();//get the factors for this year

        $toguide = 0;
        $session = Yii::$app->session; 
        $session->get('firstload', 'yes');
        if($session->get('firstload')){
            $toguide=1;
            $session->remove('firstload');
        }


        return $this->renderAjax('display-month',['yearmonth'=>$exploded[0],'lab_id'=>$exploded[1],'rstlId'=>$rstlId,'factors'=>$factors,'toguide'=>$toguide]);
    }

    public function actionIndex()
    {
        $session = Yii::$app->session;
        $session->set('hideMenu',true);
    	$reportform = new Reportform();
    	$rstlId = Yii::$app->user->identity->profile->rstl_id;
    	if ($reportform->load(Yii::$app->request->post())) {
    		$labId = $reportform->lab_id;
			$year = $reportform->year;
		}else{
			$labId = 1;
			$year = date('Y'); //current year
		}

    	$actualfees = [];
    	$discounts = [];
        $gratis = [];
    	$finalize = [];
    	$monthlyname =[];
        $factor_up = [];
        $factor_down =[];
		$month = 0;
		
        $prediction = [];
        $income = [];

		while ( $month<= 11) {
            $myear = $year.'-'.sprintf("%02d", $month +1);
            //updated this to use and look in the live data instead on the report summary
            $modelRequest = Requestextension::find()
            ->select([
                'monthnum'=>'DATE_FORMAT(`request_datetime`, "%m")',
                'totalrequests'=>'SUM(total)',
                'request_datetime'
            ])
            ->where('rstl_id =:rstlId AND status_id > :statusId AND lab_id = :labId AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :year AND request_ref_num != ""', [':rstlId'=>$rstlId,':statusId'=>0,':labId'=>$labId,':year'=>$myear])
            ->groupBy(['DATE_FORMAT(request_datetime, "%Y-%m")'])
            ->orderBy('request_datetime ASC')
            ->one();

			if($modelRequest){
				$actualfees[] = (int)$modelRequest->totalrequests;
				$discounts[] = (int)$modelRequest->countTables($myear,$labId,'discount');
                $gratis[]= (int)$modelRequest->countTables($myear,$labId,'gratis');;
				$monthlyname[]  = $myear;

				$finalize[] = "green";

			}
			else{
				$actualfees[] =0;
				$discounts[] = 0;
                $gratis[]=0;

				$finalize[] = "red";
                $income[]=null;
			}


            //get all the ; 
                $factor_up[] = (int)Reportfactors::find()
                ->joinWith(['factor'=>function($query){
                    return $query->andWhere(['type'=>'1']);
                }])
                ->where(['yearmonth'=>$myear,'lab_id'=>$labId])
                ->count();
                // ->all();
                $factor_down[] = (int)Reportfactors::find()
                ->joinWith(['factor'=>function($query){
                    return $query->andWhere(['type'=>'0']);
                }])
                ->where(['yearmonth'=>$myear,'lab_id'=>$labId])
                ->count();


             //prediction  get the 10 year behoavior of the income as base
                //we will just let this avg of 10 years to look on the retport summary for ahile untill we found the faster query to get the avg
                $avg = Reportsummary::find()
                ->select(['request'=>'AVG(gross)'])
                ->where(['lab_id'=>$labId,'rstl_id'=>$rstlId,'month'=>sprintf("%02d", ($month+1))])
                ->andWhere(['<', 'year', $year])
                ->limit(10)
                ->groupBy(['lab_id'])
                ->orderBy('year DESC')
                ->all();
            //adjust the base, according to the factors assigned

                if($avg)
                    $prediction[]=$avg[0]->request;
                else
                    $prediction[]=null;
			$month ++;
		}
        
		$lab = Lab::findOne($labId);//get the lab profile

        $session = Yii::$app->session; 
        $session->set('firstload', 'yes');

		return $this->render('index',['actualfees'=>$actualfees,'discounts'=>$discounts,'gratis'=>$gratis,'finalize'=>$finalize,'labId' => $labId,'year' => $year,'reportform'=>$reportform,'labtitle'=>$lab->labname,'factor_up'=>$factor_up,'factor_down'=>$factor_down,'prediction'=>$prediction,'income'=>$income]);
    }


    public function actionGetsamples($yearmonth,$lab_id){
    	try {
    		//get all the samples in a month
    		$request = new Requestextend;
    		$samples = $request->getStats($yearmonth,$lab_id,1);


    	} catch (Exception $e) {
			return $e;
    	}
    	
     return $samples;
    }

    public function actionGetcustomers($yearmonth,$lab_id){
    	try {

    		$reqs =  Request::find()
    		->select(['total'=>'count(tbl_customer.customer_id)','customer_id'=>'business_nature_id'])
    		->where(['DATE_FORMAT(`request_datetime`, "%Y-%m")' => $yearmonth])
            ->andWhere(['lab_id'=> $lab_id,'status_id'=>1,'request_type_id'=>1])
    		->andWhere(['>','status_id',0])
			->joinWith(['customer' => function($query){ return $query;}])
    		->groupBy(['business_nature_id'])
    		->orderBy('business_nature_id ASC')
    		->all();

    		$series = [];
    		foreach ($reqs as $req) {
    			$bn = Businessnature::findOne($req->customer?$req->customer->business_nature_id:null);
                if($bn){
                    $new = new Reportholder;
                    $new->name = $bn->nature;
                    $new->y = (int)$req->total;
                    $series[]=$new;
                }
    		}


    	} catch (Exception $e) {
			return $e;
    	}

    	return $this->renderAjax('businessnature',['data'=>$series]);
    
    }

    public function actionGettestsperformed($yearmonth,$lab_id){
    	try {

    		$reqs =  Request::find()
    		->select(['conforme'=>'sampletype_id'])
    		->where(['DATE_FORMAT(`request_datetime`, "%Y-%m")' => $yearmonth,'lab_id'=>$lab_id])
    		->andWhere(['>','status_id',0])
            ->andWhere(['status_id'=>1,'request_type_id'=>1])
			->joinWith(['samples'])
    		->groupBy(['sampletype_id'])
    		->orderBy('sampletype_id ASC')
    		->all();

    		$series = [];
    		foreach ($reqs as $req) {
    			$st= Sampletype::findOne($req->conforme);
      
                $inner_reqs = Sample::find()
                ->select(['tbl_sample.sample_id','sampletype_id','tbl_sample.request_id','total'=>'count(analysis_id)','conforme'=>'testname'])
                ->where(['active'=>'1','sampletype_id'=>$req->conforme])
                ->joinWith(['request'=>function($query)use($yearmonth,$lab_id){
                    return $query->select(['request_id','request_ref_num','request_datetime'])->andWhere(['DATE_FORMAT(`request_datetime`, "%Y-%m")' => $yearmonth,'lab_id'=>$lab_id])->andWhere(['>','status_id',0]);
                }])
                 ->joinWith(['analyses'=>function($query){
                     return $query->select(['sample_id','testname'])->andWhere(['<>','references','-'])->andWhere(['cancelled'=>'0']);
                    }])
                 ->groupBy(['testname'])
                 ->orderBy('testname ASC')
                ->asArray()
                ->all();

	    		$data=[];
	    		foreach ($inner_reqs as $inner_req) {
	    			$data[]=['name'=>$inner_req['conforme'],'value'=>(int)$inner_req['total']];
	    		}
    			$series[]=['name'=>$st?$st->type:'unknown','data'=>$data];
            }
            

    	} catch (Exception $e) {
			return $e;
    	}
    	$series= json_encode($series); 
    	 // return $series;
    	return $this->renderAjax('testperformed',['data'=>$series]);
    
    }

    public function actionAddfactors($yearmonth,$labid){
        $reportfactor = new Reportfactors;
        $reportfactor->yearmonth = $yearmonth;
        $reportfactor->lab_id=$labid;
        $factors = Factors::find()->all();

        if ($reportfactor->load(Yii::$app->request->post())) {
            if($reportfactor->save(false))
                Yii::$app->session->setFlash('success', 'Factor Successfully Added');
            else
                Yii::$app->session->setFlash('danger', 'Linking Factor Failed');

            return $this->redirect(['/reports/finance/analytic/']);
        }

        return $this->renderAjax('linkfactor',['model'=>$reportfactor,'factors'=>$factors,'lab_id'=>$labid]);
    }

    public function actionCreatefactor($yearmonth,$labid){
        $reportfactor = new Reportfactors;
        $reportfactor->yearmonth = $yearmonth;
        $reportfactor->lab_id=$labid;
        $factor =  new Factors;

        if (($factor->load(Yii::$app->request->post()))&&($reportfactor->load(Yii::$app->request->post()))) {
            if($factor->save()){
                $reportfactor->factor_id = $factor->factor_id;
                if($reportfactor->save(false)){
                    Yii::$app->session->setFlash('success', 'Factor Successfully Added');
                    return $this->redirect(['/reports/finance/analytic/']);
                }
                else{
                    Yii::$app->session->setFlash('danger', 'Linking Factor Failed');
                }
            }
            else{
                Yii::$app->session->setFlash('danger', 'Linking Factor Failed');
            }
        }

        return $this->renderAjax('linkfactorcomplete',['model'=>$reportfactor,'factor'=>$factor]);
    }

    public function actionRemovefactor($factor_id){
        $reportfactor = Reportfactors::findOne($factor_id)->delete();
        if ($reportfactor)
            Yii::$app->session->setFlash('success', 'Factor Successfully Deleted!');
        else
            Yii::$app->session->setFlash('error', 'Factor Failed to Delete!');
        return $this->redirect(['/reports/finance/analytic/']);
    }
}
