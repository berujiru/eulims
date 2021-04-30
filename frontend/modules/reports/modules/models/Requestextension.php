<?php

namespace frontend\modules\reports\modules\models;

use Yii;
use yii\db\ActiveRecord;
use common\models\lab\Request;
use common\models\lab\Sample;
use common\models\lab\Analysis;
/**
 * Extended model from lab Request
 * krad was here
 *
**/

class Requestextension extends Request
{
  
    public $totalrequests ,$month, $monthnum,$from_date, $to_date, $lab_id;

    public static function countTables($yearmonth, $lab, $type){
        
        $data = explode("-",$yearmonth);
        $year = $data[0]; 
        $month = $data[1];
        $rstl_id =Yii::$app->user->identity->profile->rstl_id;

        switch($type) {
            case 'request':
                $data =  Requestextension::find()
                    ->select(['request_id'])
                    ->where(['LIKE','request_datetime',$yearmonth])
                    ->andWhere(['lab_id'=>$lab, 'request_type_id' => 1])
                    ->andWhere(['>','status_id',0])
                    ->all(); 

                return count($data);
            break;
            case 'samples':
                $data =  Requestextension::find()
                    ->select(['sample_id'])
                    ->where(['LIKE','request_datetime',$yearmonth])
                    ->andWhere(['lab_id'=>$lab, 'request_type_id' => 1])
                    ->andWhere(['>','status_id',0])
                    ->innerJoinWith('samples', 'tbl_sample.request_id = Requestextension.request_id')
                    ->andWhere(['tbl_sample.active'=>'1'])
                    ->all(); 

                return count($data);
            break;
            case 'analysis':
            
                $count = Yii::$app->labdb->createCommand("SELECT count(analysis_id) FROM tbl_request r
                INNER JOIN tbl_sample s ON s.request_id = r.request_id 
                INNER JOIN tbl_analysis a ON s.sample_id = a.sample_id 
                WHERE r.lab_id =$lab
                AND r.rstl_id = $rstl_id
                AND r.status_id > 0 
                AND r.request_ref_num != ''
                AND r.request_type_id = 1
                AND s.active = 1
                AND a.cancelled = 0
                AND a.references <> '-'
                AND year(r.request_datetime)= $year
                AND month(r.request_datetime)= $month")->queryScalar();

                return $count;
            break;
             
            case 'discount' :
                $count = Yii::$app->labdb->createCommand("SELECT sum((fee * ( discount/100))) as total FROM tbl_request r
                INNER JOIN tbl_sample s ON s.request_id = r.request_id 
                INNER JOIN tbl_analysis a ON s.sample_id = a.sample_id 
                WHERE r.lab_id =$lab
                AND r.rstl_id = $rstl_id
                AND r.status_id > 0 
                AND r.request_ref_num != ''
                AND r.request_type_id = 1
                AND r.discount_id != 8
                AND s.active = 1
                AND a.cancelled = 0
                -- AND a.references <> '-'
                AND year(r.request_datetime)= $year
                AND month(r.request_datetime)= $month")->queryScalar();

                return number_format((float)$count, 2, '.', '');
            break;

            case 'gratis' :
                $count = Yii::$app->labdb->createCommand("SELECT sum(fee) as total FROM tbl_request r
                INNER JOIN tbl_sample s ON s.request_id = r.request_id 
                INNER JOIN tbl_analysis a ON s.sample_id = a.sample_id 
                WHERE r.lab_id =$lab
                AND r.rstl_id = $rstl_id
                AND r.status_id > 0 
                AND r.request_ref_num != ''
                AND r.request_type_id = 1
                AND r.discount_id = 8
                AND s.active = 1
                AND a.cancelled = 0
                -- AND a.references <> '-'
                AND year(r.request_datetime)= $year
                AND month(r.request_datetime)= $month")->queryScalar();

                return number_format((float)$count, 2, '.', '');
            break;

            case 'analysisdaily':
                $count = Yii::$app->labdb->createCommand("SELECT count(analysis_id) FROM tbl_request r
                INNER JOIN tbl_sample s ON s.request_id = r.request_id 
                INNER JOIN tbl_analysis a ON s.sample_id = a.sample_id 
                WHERE r.lab_id = $lab
                AND r.rstl_id = $rstl_id
                AND r.status_id > 0 
                AND r.request_ref_num != ''
                AND r.request_type_id = 1
                AND s.active = 1
                AND a.cancelled = 0
                AND a.references <> '-'
                AND date(r.request_datetime) = '".$yearmonth."'")->queryScalar();

                return $count;
            break;
          } 
    }
    //for referral accomplishment
    public function getreferralsenttotcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['receiving_agency_id' => $rstlId]);
                }])
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }else{
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['receiving_agency_id' => $rstlId]);
                }])
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }
        
    }

    //for referral accomplishment
    public function getsamplessenttotcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['receiving_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('samples')
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }else{
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['receiving_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('samples')
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }
    }
    //for referral accomplishment
    public function gettestsenttotcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['receiving_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }else{
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['receiving_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }
    }
     //for referral accomplishment
    public function getreferralserveastcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }else{
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }
    }
     //for referral accomplishment
    public function getsamplesserveastcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('samples')
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }else{
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('samples')
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }
    }
    //for referral accomplishment
    public function gettestserveastcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }else{
            return $modelReferral = Requestextension::find()
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->count();
        }
    }

     //for referral accomplishment
    public function getreferraltotal($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            $modelReferral = Requestextension::find()
                ->select(['totalrequests'=>'SUM(total)'])
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime AND payment_type_id=1', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->groupBy(['request_type_id'])->one();
        }else{
            $modelReferral = Requestextension::find()
                ->select(['totalrequests'=>'SUM(total)'])
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime AND payment_type_id=1', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->groupBy(['request_type_id'])->one();
        }
        return $modelReferral?$modelReferral->totalrequests:0;
    }

    //for referral accomplishment
    public function getgratisastcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            $modelReferral = Requestextension::find()
                ->select(['totalrequests'=>'SUM(fee)'])
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime AND payment_type_id=2', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->groupBy(['request_type_id'])->one();
        }else{
            $modelReferral = Requestextension::find()
                ->select(['totalrequests'=>'SUM(fee)'])
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime AND payment_type_id=2', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->groupBy(['request_type_id'])->one();
        }
        return $modelReferral?$modelReferral->totalrequests:0;
    }

    //for referral accomplishment
    public function getdiscountastcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            $modelReferral = Requestextension::find()
                ->select(['totalrequests'=>'SUM(fee)','discount'])
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime AND payment_type_id=1 and discount > 0', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->groupBy(['request_type_id'])->one();
        }else{
            $modelReferral = Requestextension::find()
                ->select(['totalrequests'=>'SUM(fee)','discount'])
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime AND payment_type_id=1 and discount > 0', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->groupBy(['request_type_id'])->one();
        }
        return $modelReferral?($modelReferral->totalrequests * ($modelReferral->discount *.01)):0;
    }

     public function getgrossastcl($labId,$request_datetime,$report_type){
        $datetime=date('Y-m',strtotime($request_datetime));
        $rstlId = Yii::$app->user->identity->profile->rstl_id;
        if($report_type==2){
            $modelReferral = Requestextension::find()
                ->select(['totalrequests'=>'SUM(fee)'])
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.lab_id=:labId AND tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':labId'=>$labId,':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->groupBy(['request_type_id'])->one();
        }else{
            $modelReferral = Requestextension::find()
                ->select(['totalrequests'=>'SUM(fee)'])
                ->innerJoinWith(['referralrequest' => function($query)use($rstlId){
                    $query->where(['testing_agency_id' => $rstlId]);
                }])
                ->innerJoinWith('analyses')
                ->where('tbl_request.rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m") = :datetime', [':rstlId'=>$rstlId,':statusId'=>0,':datetime'=>$datetime])
                ->groupBy(['request_type_id'])->one();
        }
        return $modelReferral?$modelReferral->totalrequests:0;
    }
}
