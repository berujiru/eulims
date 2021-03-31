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
                AND r.rstl_id = 11
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
                AND r.rstl_id = 11
                AND r.status_id > 0 
                AND r.request_ref_num != ''
                AND r.request_type_id = 1
                AND r.payment_type_id != 2
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
                AND r.rstl_id = 11
                AND r.status_id > 0 
                AND r.request_ref_num != ''
                AND r.request_type_id = 1
                AND r.payment_type_id = 2
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
                AND r.rstl_id = 11
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
    
}
