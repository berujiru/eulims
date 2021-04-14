<?php

namespace frontend\modules\reports\modules\referral\controllers;

use Yii;
use yii\web\Controller;
//use common\models\lab\Sample;
//use common\models\lab\SampleSearch;
//use common\models\lab\Request;
use common\models\referral\Referralextend;
use common\models\referral\Lab;
use common\models\referral\Referral;
use common\models\referral\Agency;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
//use yii\data\SqlDataProvider;
use yii\data\ArrayDataProvider;
use frontend\modules\referrals\template\Accomplishmentreportagency;
use yii2tech\spreadsheet\Spreadsheet;
use yii2tech\spreadsheet\SerialColumn;
use arturoliveira\ExcelView;
use kartik\grid\GridView;
use common\components\ReferralComponent;
use common\models\lab\ReferralRequest;
use frontend\modules\reports\modules\models\Requestextension;


class AccomplishmentcroController extends \yii\web\Controller
{
    public function actionIndex()
    {
    	$model = new Referralextend;
    	$rstlId = Yii::$app->user->identity->profile->rstl_id;
    	
		if (Yii::$app->request->get('report_type'))
		{
			$labId = (int) Yii::$app->request->get('lab_id');

			$report_type = (int) Yii::$app->request->get('report_type');
			
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
			$fromDate = date('Y-01-01'); //first day of the year
			$toDate = date('Y-m-d'); //as of today
			$report_type = 1;
		}

		if($report_type==1){
			$modelReferral = Requestextension::find()
				->select([
					'monthnum'=>'DATE_FORMAT(`request_datetime`, "%m")',
					'month'=>'DATE_FORMAT(`request_datetime`, "%M")',
					'totalrequests' => 'count(request_id)',
					'request_datetime',
				])
                ->where('rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate', [':rstlId'=>$rstlId,':statusId'=>0,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate])
                ->groupBy(['DATE_FORMAT(request_datetime, "%Y-%m")'])
                ->orderBy([
                    'DATE_FORMAT(request_datetime, "%Y")' => SORT_DESC,
                    'DATE_FORMAT(request_datetime, "%m")' => SORT_ASC,
                ]);
		}elseif($report_type==2){
			$modelReferral = Requestextension::find()
				->select([
					'monthnum'=>'DATE_FORMAT(`request_datetime`, "%m")',
					'month'=>'DATE_FORMAT(`request_datetime`, "%M")',
					'totalrequests' => 'count(request_id)',
					'request_datetime',
				])
                ->where('lab_id=:labId AND rstl_id =:rstlId AND status_id > :statusId AND request_ref_num != "" AND request_type_id = 2 AND DATE_FORMAT(`request_datetime`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate', [':rstlId'=>$rstlId,':statusId'=>0,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate,':labId'=>$labId])
                ->groupBy(['DATE_FORMAT(request_datetime, "%Y-%m")'])
                ->orderBy([
                    'DATE_FORMAT(request_datetime, "%Y")' => SORT_DESC,
                    'DATE_FORMAT(request_datetime, "%m")' => SORT_ASC,
                ]);
		}


        $dataProvider = new ActiveDataProvider([
            'query' => $modelReferral,
            'pagination' => false,
        ]);

		if(isset($response)){
			$modelReferral=$response->modelReferral;
			$dataProvider=$response->dataProvider;
		}

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('index', [
                'dataProvider' => $dataProvider,
                'lab_id' => $labId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'model'=>$modelReferral,
	            'laboratories' => $this->listLaboratory(),
	            'report_type' => $report_type,
            ]);
        } else {
			return $this->render('index', [
	            'dataProvider' => $dataProvider,
	            'lab_id' => $labId,
	            'model'=>$modelReferral,
                'from_date' => $fromDate,
                'to_date' => $toDate,
	            'laboratories' => $this->listLaboratory(),
	            'report_type' => $report_type,
	        ]);
		}

        //return $this->render('index');
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

	public function actionPp()
	{
		$rstlId = Yii::$app->user->identity->profile->rstl_id;
    	$agency = Agency::find()->where(['agency_id'=>$rstlId])->one();
    	
		if (Yii::$app->request->get())
		{
			$labId = (int) Yii::$app->request->get('lab_id');

			$report_type = (int) Yii::$app->request->get('report_type');
			
			if($this->checkValidDate(Yii::$app->request->get('from_date')) == true)
			{
		        $startDate = Yii::$app->request->get('from_date');
			} else {
				$startDate = date('Y-m-d');
				Yii::$app->session->setFlash('error', "Not a valid date!");
			}

			if($this->checkValidDate(Yii::$app->request->get('to_date')) == true){
				$endDate = Yii::$app->request->get('to_date');
			} else {
				$endDate = date('Y-m-d');
				Yii::$app->session->setFlash('error', "Not a valid date!");
			}
		} else {
			$labId = 1;
			$startDate = date('2017-01-01'); //first day of the year
			$endDate = date('Y-m-d'); //as of today
			$report_type = 2;
		}

		//$startDate = 

		//$labId, $startDate,$endDate,$report_type

		if($report_type == 1){
			$modelReferral = Referralextend::find()
				->where('(testing_agency_id =:testingAgencyId OR receiving_agency_id =:receivingAgencyId) AND cancelled =:cancel AND DATE_FORMAT(`referral_date_time`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND testing_agency_id != 0', [':testingAgencyId'=>$rstlId,':receivingAgencyId'=>$rstlId,':cancel'=>0,':fromRequestDate'=>$startDate,':toRequestDate'=>$endDate])
				->groupBy(['DATE_FORMAT(referral_date_time, "%Y-%m")'])
				//->groupBy(['DATE_FORMAT(referral_date_time, "%m")'])
				//->addGroupBy(['DATE_FORMAT(referral_date_time, "%Y")'])
				//->orderBy('referral_date_time DESC');
				// ->orderBy([
				//     'DATE_FORMAT(referral_date_time, "%m")' => SORT_ASC,
				//     'DATE_FORMAT(referral_date_time, "%Y")' => SORT_DESC,
				// ]);
				->orderBy([
					'DATE_FORMAT(referral_date_time, "%Y")' => SORT_DESC,
				    'DATE_FORMAT(referral_date_time, "%m")' => SORT_ASC,
				]);
		} else {
			$modelReferral = Referralextend::find()
				->where('(testing_agency_id =:testingAgencyId OR receiving_agency_id =:receivingAgencyId) AND cancelled =:cancel AND lab_id = :labId AND DATE_FORMAT(`referral_date_time`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND testing_agency_id != 0', [':testingAgencyId'=>$rstlId,':receivingAgencyId'=>$rstlId,':cancel'=>0,':labId'=>$labId,':fromRequestDate'=>$startDate,':toRequestDate'=>$endDate])
				->groupBy(['DATE_FORMAT(referral_date_time, "%Y-%m")'])
				//->groupBy(['DATE_FORMAT(referral_date_time, "%m")'])
				//->addGroupBy(['DATE_FORMAT(referral_date_time, "%Y")'])
				//->orderBy('referral_date_time DESC');
				//->orderBy(['DATE_FORMAT(referral_date_time, "%m")'=>]);
				->orderBy([
					'DATE_FORMAT(referral_date_time, "%Y")' => SORT_DESC,
				    'DATE_FORMAT(referral_date_time, "%m")' => SORT_ASC,
				]);
		}

		$dataProvider = new ActiveDataProvider([
            'query' => $modelReferral,
            'pagination' => false,
            // 'pagination' => [
            //     'pagesize' => 10,
            // ],
        ]);

		echo GridView::widget([
			'dataProvider' => $dataProvider,
			//'fullExportType' => 'xlsx',
			//'grid_mode' => 'export',
			/*'columns' => [
				['attribute' => 'referral_date_time', 'header' => 'A'], 
				['attribute' => 'referral_id', 'header' => 'B'], 
				['attribute' => 'referral_code', 'header' => 'C']
			]*/
			'columns' => [
				[
			            'attribute'=>'referral_date_time', 
			            'header' => 'Year',
			            //'width'=>'310px',
			            'value'=>function ($model, $key, $index, $widget) {
		                    return Yii::$app->formatter->asDate($model->referral_date_time, 'php:Y');
		                },
		                'contentOptions' => ['class' => 'bg-info text-primary','style'=>'font-weight:bold;font-size:15px;'],
			            'group'=>true,  // enable grouping,
			            'groupedRow'=>true,                    // move grouped column to a single grouped row
			            //'groupOddCssClass'=>'kv-grouped-row',  // configure odd group cell css class
			            //'groupEvenCssClass'=>'kv-grouped-row', // configure even group cell css class
			            'groupFooter'=>function ($model, $key, $index, $widget) { // Closure method
			                return [
			                    'mergeColumns'=>[[1]], // columns to merge in summary
			                    'content'=>[             // content to show in each summary cell
			                        1=>'SUB-TOTAL ('.Yii::$app->formatter->asDate($model->referral_date_time, 'php:Y').')',
			                        2=>GridView::F_SUM,
			                        3=>GridView::F_SUM,
			                        4=>GridView::F_SUM,
			                        5=>GridView::F_SUM,
			                        6=>GridView::F_SUM,
			                        7=>GridView::F_SUM,
			                        8=>GridView::F_SUM,
			                        9=>GridView::F_SUM,
			                        10=>GridView::F_SUM,
			                        11=>GridView::F_SUM,
			                        12=>GridView::F_SUM,
			                    ],
			                    'contentFormats'=>[      // content reformatting for each summary cell
			                        2=>['format'=>'number', 'decimals'=>0],
			                        3=>['format'=>'number', 'decimals'=>0],
			                        4=>['format'=>'number', 'decimals'=>0],
			                        5=>['format'=>'number', 'decimals'=>0],
			                        6=>['format'=>'number', 'decimals'=>0],
			                        7=>['format'=>'number', 'decimals'=>0],
			                        8=>['format'=>'number', 'decimals'=>0],
			                        9=>['format'=>'number', 'decimals'=>2],
			                        10=>['format'=>'number', 'decimals'=>2],
			                        11=>['format'=>'number', 'decimals'=>2],
			                        12=>['format'=>'number', 'decimals'=>2],
			                    ],
			                    'contentOptions'=>[      // content html attributes for each summary cell
			                        1=>['style'=>'font-variant:small-caps'],
			                        2=>['style'=>'text-align:center'],
			                        3=>['style'=>'text-align:center'],
			                        4=>['style'=>'text-align:center'],
			                        5=>['style'=>'text-align:center'],
			                        6=>['style'=>'text-align:center'],
			                        7=>['style'=>'text-align:center'],
			                        8=>['style'=>'text-align:center'],
			                        9=>['style'=>'text-align:right'],
			                        10=>['style'=>'text-align:right'],
			                        11=>['style'=>'text-align:right'],
			                        12=>['style'=>'text-align:right'],
			                    ],
			                    // html attributes for group summary row
			                    'options'=>['class'=>'text-success bg-warning']
			                ];
			            }
			        ],
		            [
		                'attribute'=>'referral_date_time',
		                'header' => 'Month',
		                'value'=>function ($model, $key, $index, $widget) {
		                    return strtoupper(Yii::$app->formatter->asDate($model->referral_date_time, 'php:M'));
		                },
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
		                'pageSummary'=>'GRAND TOTAL',
		                'pageSummaryOptions'=>['class'=>'text-left text-primary bg-success'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of referral request sent to TCL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $countCustomer =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,2,$report_type),['decimal',0]);
			            	return ($countCustomer > 0) ? $countCustomer : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of samples referred to TCL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $countRequest =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,4,$report_type),['decimal',0]);
			            	return ($countRequest > 0) ? $countRequest : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of tests referred to TCL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $countSample =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,6,$report_type),['decimal',0]);
			            	return ($countSample > 0) ? $countSample : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of referral request received from RL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $countCustomer =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,1,$report_type),['decimal',0]);
			            	return ($countCustomer > 0) ? $countCustomer : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of customers served from referral as TCL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $countCustomer =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,7,$report_type),['decimal',0]);
			            	return ($countCustomer > 0) ? $countCustomer : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of samples received from RL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $countCustomer =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,3,$report_type),['decimal',0]);
			            	return ($countCustomer > 0) ? $countCustomer : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of tests conducted from referred sample',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $countAnalysis =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,5,$report_type),['decimal',0]);
			            	return ($countAnalysis > 0) ? $countAnalysis : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'Income Generated (Actual Fees Collected)',
		                'headerOptions' => ['class' => 'text-center','style'=>'width:15%; vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-right'],
			            'format'=>['decimal', 2],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $totalIncome = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,11,$report_type);
			            	return ($totalIncome > 0) ? $totalIncome : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'Value of Assistance (Gratis)',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-right'],
			            'format'=>['decimal', 2],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $totalGratis = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,9,$report_type);
			            	return ($totalGratis > 0) ? $totalGratis : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'Value of Assistance (Discount)',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-right'],
			            'format'=>['decimal', 2],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   $totalDiscount = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,10,$report_type);
			            	return ($totalDiscount > 0) ? $totalDiscount : 0;
		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
		            ],
		            [
		            	//'attribute'=>'request_ref_num',
		                'header' => 'Gross (Fees Collected)',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-right'],
			            'format'=>['decimal', 2],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   //$totalGrossFee = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,5,$report_type);
			            	//return ($totalGrossFee > 0) ? $totalGrossFee : 0;
			            	//$totalIncome = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,8,$report_type);
			            	//$totalDiscount = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,7,$report_type);
			            	//$totalGratis = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,6,$report_type);

			            	//$totalGrossFee = $totalIncome + $totalDiscount + $totalGratis;
			            	//return ($totalGrossFee > 0) ? $totalGrossFee : 0;

			            	$totalGrossFee = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,8,$report_type);
			            	return ($totalGrossFee > 0) ? $totalGrossFee : 0;

		                },
		                'pageSummary'=>true,
        				//'pageSummaryFunc'=>ExcelView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
		            ]
			]
		]);
	}

	public function actionExport1()
    {
    	$rstlId = Yii::$app->user->identity->profile->rstl_id;
    	$agency = Agency::find()->where(['agency_id'=>$rstlId])->one();
    	
		if (Yii::$app->request->get())
		{
			$labId = (int) Yii::$app->request->get('lab_id');

			$report_type = (int) Yii::$app->request->get('report_type');
			
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
			$fromDate = date('2017-01-01'); //first day of the year
			$toDate = date('Y-m-d'); //as of today
			$report_type = 2;
		}

		if($report_type == 1){
			$modelReferral = Referralextend::find()
				->where('(testing_agency_id =:testingAgencyId OR receiving_agency_id =:receivingAgencyId) AND cancelled =:cancel AND DATE_FORMAT(`referral_date_time`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND testing_agency_id != 0', [':testingAgencyId'=>$rstlId,':receivingAgencyId'=>$rstlId,':cancel'=>0,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate])
				->groupBy(['DATE_FORMAT(referral_date_time, "%Y-%m")'])
				//->groupBy(['DATE_FORMAT(referral_date_time, "%m")'])
				//->addGroupBy(['DATE_FORMAT(referral_date_time, "%Y")'])
				//->orderBy('referral_date_time DESC');
				// ->orderBy([
				//     'DATE_FORMAT(referral_date_time, "%m")' => SORT_ASC,
				//     'DATE_FORMAT(referral_date_time, "%Y")' => SORT_DESC,
				// ]);
				->orderBy([
					'DATE_FORMAT(referral_date_time, "%Y")' => SORT_DESC,
				    'DATE_FORMAT(referral_date_time, "%m")' => SORT_ASC,
				]);
		} else {
			$modelReferral = Referralextend::find()
				->where('(testing_agency_id =:testingAgencyId OR receiving_agency_id =:receivingAgencyId) AND cancelled =:cancel AND lab_id = :labId AND DATE_FORMAT(`referral_date_time`, "%Y-%m-%d") BETWEEN :fromRequestDate AND :toRequestDate AND testing_agency_id != 0', [':testingAgencyId'=>$rstlId,':receivingAgencyId'=>$rstlId,':cancel'=>0,':labId'=>$labId,':fromRequestDate'=>$fromDate,':toRequestDate'=>$toDate])
				->groupBy(['DATE_FORMAT(referral_date_time, "%Y-%m")'])
				//->groupBy(['DATE_FORMAT(referral_date_time, "%m")'])
				//->addGroupBy(['DATE_FORMAT(referral_date_time, "%Y")'])
				//->orderBy('referral_date_time DESC');
				//->orderBy(['DATE_FORMAT(referral_date_time, "%m")'=>]);
				->orderBy([
					'DATE_FORMAT(referral_date_time, "%Y")' => SORT_DESC,
				    'DATE_FORMAT(referral_date_time, "%m")' => SORT_ASC,
				]);
		}

/*
		$gridColumns = [
		    [
	            'attribute'=>'referral_date_time', 
	            'header' => 'Year',
	            //'width'=>'310px',
	            'value'=>function ($model, $key, $index, $widget) {
                    return Yii::$app->formatter->asDate($model->referral_date_time, 'php:Y');
                },
                'contentOptions' => ['class' => 'bg-info text-primary','style'=>'font-weight:bold;font-size:15px;'],
	            'group'=>true,  // enable grouping,
	            'groupedRow'=>true,                    // move grouped column to a single grouped row
	            //'groupOddCssClass'=>'kv-grouped-row',  // configure odd group cell css class
	            //'groupEvenCssClass'=>'kv-grouped-row', // configure even group cell css class
	            'groupFooter'=>function ($model, $key, $index, $widget) { // Closure method
	                return [
	                    'mergeColumns'=>[[1]], // columns to merge in summary
	                    'content'=>[             // content to show in each summary cell
	                        1=>'SUB-TOTAL ('.Yii::$app->formatter->asDate($model->referral_date_time, 'php:Y').')',
	                        2=>GridView::F_SUM,
	                        3=>GridView::F_SUM,
	                        4=>GridView::F_SUM,
	                        5=>GridView::F_SUM,
	                        6=>GridView::F_SUM,
	                        7=>GridView::F_SUM,
	                        8=>GridView::F_SUM,
	                        9=>GridView::F_SUM,
	                        10=>GridView::F_SUM,
	                        11=>GridView::F_SUM,
	                        12=>GridView::F_SUM,
	                    ],
	                    'contentFormats'=>[      // content reformatting for each summary cell
	                        2=>['format'=>'number', 'decimals'=>0],
	                        3=>['format'=>'number', 'decimals'=>0],
	                        4=>['format'=>'number', 'decimals'=>0],
	                        5=>['format'=>'number', 'decimals'=>0],
	                        6=>['format'=>'number', 'decimals'=>0],
	                        7=>['format'=>'number', 'decimals'=>0],
	                        8=>['format'=>'number', 'decimals'=>0],
	                        9=>['format'=>'number', 'decimals'=>2],
	                        10=>['format'=>'number', 'decimals'=>2],
	                        11=>['format'=>'number', 'decimals'=>2],
	                        12=>['format'=>'number', 'decimals'=>2],
	                    ],
	                    'contentOptions'=>[      // content html attributes for each summary cell
	                        1=>['style'=>'font-variant:small-caps'],
	                        2=>['style'=>'text-align:center'],
	                        3=>['style'=>'text-align:center'],
	                        4=>['style'=>'text-align:center'],
	                        5=>['style'=>'text-align:center'],
	                        6=>['style'=>'text-align:center'],
	                        7=>['style'=>'text-align:center'],
	                        8=>['style'=>'text-align:center'],
	                        9=>['style'=>'text-align:right'],
	                        10=>['style'=>'text-align:right'],
	                        11=>['style'=>'text-align:right'],
	                        12=>['style'=>'text-align:right'],
	                    ],
	                    // html attributes for group summary row
	                    'options'=>['class'=>'text-success bg-warning']
	                ];
	            }
	        ],
            [
                'attribute'=>'referral_date_time',
                'header' => 'Month',
                'value'=>function ($model, $key, $index, $widget) {
                    return strtoupper(Yii::$app->formatter->asDate($model->referral_date_time, 'php:M'));
                },
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-center'],
                'pageSummary'=>'GRAND TOTAL',
                'pageSummaryOptions'=>['class'=>'text-left text-primary bg-success'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'No. of referral request sent to TCL',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-center'],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $countCustomer =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,2,$report_type),['decimal',0]);
	            	return ($countCustomer > 0) ? $countCustomer : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'No. of samples referred to TCL',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-center'],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $countRequest =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,4,$report_type),['decimal',0]);
	            	return ($countRequest > 0) ? $countRequest : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'No. of tests referred to TCL',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-center'],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $countSample =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,6,$report_type),['decimal',0]);
	            	return ($countSample > 0) ? $countSample : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'No. of referral request received from RL',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-center'],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $countCustomer =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,1,$report_type),['decimal',0]);
	            	return ($countCustomer > 0) ? $countCustomer : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'No. of customers served from referral as TCL',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-center'],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $countCustomer =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,7,$report_type),['decimal',0]);
	            	return ($countCustomer > 0) ? $countCustomer : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'No. of samples received from RL',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-center'],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $countCustomer =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,3,$report_type),['decimal',0]);
	            	return ($countCustomer > 0) ? $countCustomer : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'No. of tests conducted from referred sample',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-center'],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $countAnalysis =  Yii::$app->formatter->format($model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,5,$report_type),['decimal',0]);
	            	return ($countAnalysis > 0) ? $countAnalysis : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'Income Generated (Actual Fees Collected)',
                'headerOptions' => ['class' => 'text-center','style'=>'width:15%; vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-right'],
	            'format'=>['decimal', 2],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $totalIncome = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,11,$report_type);
	            	return ($totalIncome > 0) ? $totalIncome : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'Value of Assistance (Gratis)',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-right'],
	            'format'=>['decimal', 2],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $totalGratis = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,9,$report_type);
	            	return ($totalGratis > 0) ? $totalGratis : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
            ],
            [
                //'attribute'=>'request_ref_num',
                'header' => 'Value of Assistance (Discount)',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-right'],
	            'format'=>['decimal', 2],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   $totalDiscount = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,10,$report_type);
	            	return ($totalDiscount > 0) ? $totalDiscount : 0;
                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
            ],
            [
            	//'attribute'=>'request_ref_num',
                'header' => 'Gross (Fees Collected)',
                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
	            'contentOptions' => ['class' => 'text-right'],
	            'format'=>['decimal', 2],
	            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
                   //$totalGrossFee = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,5,$report_type);
	            	//return ($totalGrossFee > 0) ? $totalGrossFee : 0;
	            	//$totalIncome = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,8,$report_type);
	            	//$totalDiscount = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,7,$report_type);
	            	//$totalGratis = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,6,$report_type);

	            	//$totalGrossFee = $totalIncome + $totalDiscount + $totalGratis;
	            	//return ($totalGrossFee > 0) ? $totalGrossFee : 0;

	            	$totalGrossFee = $model->computeAccomplishment($labId,date('Y-m-d',strtotime($model->referral_date_time)),$startDate,$endDate,8,$report_type);
	            	return ($totalGrossFee > 0) ? $totalGrossFee : 0;

                },
                'pageSummary'=>true,
				'pageSummaryFunc'=>GridView::F_SUM,
				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
            ],
	    ];
*/
		/*$exporter = new Spreadsheet([
		    'dataProvider' => new ArrayDataProvider([
		        'allModels' => [
		            [
		                'name' => 'some name',
		                'price' => '9879',
		            ],
		            [
		                'name' => 'name 2',
		                'price' => '79',
		            ],
		        ],
		    ]),
		    'columns' => [
		        [
		            'attribute' => 'name',
		            'contentOptions' => [
		                'alignment' => [
		                    'horizontal' => 'center',
		                    'vertical' => 'center',
		                ],
		            ],
		        ],
		        [
		            'attribute' => 'price',
		        ],
		    ],
		]);
		$exporter->save('/referrals/template/Accomplishmentreportagency.xls');*/

		//echo "<pre>";
		//print_r($modelReferral->all());
		//echo "</pre>";
		//exit;

		//foreach ($modelReferral->all() as $data) {
		//	echo strtoupper(Yii::$app->formatter->asDate($data->referral_date_time, 'php:Y'));
		//}
		//exit;

    	$exporter = new Accomplishmentreportagency([
    		'model' => $modelReferral->all(),
    		//'start_date' => date("M-d-Y",strtotime($fromDate)),
    		//'end_date' => date("M-d-Y",strtotime($toDate)),
    		//'rstlId' => $rstlId,
    		//'agency1' => $agency,
    	]);
    }

    public function actionExport()
    {
    	$exporter = new Spreadsheet([
		    'dataProvider' => new ArrayDataProvider([
		        'allModels' => [
		            [
		                'name' => 'some name',
		                'price' => '9879',
		            ],
		            [
		                'name' => 'name 2',
		                'price' => '79',
		            ],
		        ],
		    ]),
		    'columns' => [
		        [
		            'attribute' => 'name',
		            'contentOptions' => [
		                'alignment' => [
		                    'horizontal' => 'center',
		                    'vertical' => 'center',
		                ],
		            ],
		        ],
		        [
		            'attribute' => 'price',
		        ],
		    ],
		]);
		$exporter->send('/referrals/template/files.xls');

		// $exporter = new Spreadsheet([
		//     'dataProvider' => new ArrayDataProvider([
		//         'allModels' => [
		//             [
		//                 'column1' => '1.1',
		//                 'column2' => '1.2',
		//                 'column3' => '1.3',
		//                 'column4' => '1.4',
		//                 'column5' => '1.5',
		//                 'column6' => '1.6',
		//                 'column7' => '1.7',
		//             ],
		//             [
		// 		        /*[
		// 		            'header' => 'Skip 1 column and group 2 next',
		// 		            'offset' => 0,
		// 		            'length' => 7,
		// 		        ],*/
		// 		        'headerColumnUnions' => [
		// 			        [
		// 			            'header' => 'Skip 1 column and group 2 next',
		// 			            'offset' => 0,
		// 			            'length' => 7,
		// 			        ],
		// 			    ],
		//             ],
		//             /*[
		//                 'column1' => '2.1',
		//                 'column2' => '2.2',
		//                 'column3' => '2.3',
		//                 'column4' => '2.4',
		//                 'column5' => '2.5',
		//                 'column6' => '2.6',
		//                 'column7' => '2.7',
		//             ],*/
		//         ],
		//     ]),
		//     /*'headerColumnUnions' => [
		//         [
		//             'header' => 'Skip 1 column and group 2 next',
		//             'offset' => 0,
		//             'length' => 7,
		//         ],
		//         /*[
		//             'header' => 'Skip 2 columns and group 2 next',
		//             'offset' => 2,
		//             'length' => 2,
		//         ],*/
		//     /*],*/
		// ]);
		// $exporter->send('/referrals/template/files.xls');
	}

	public function actionExpo()
	{
		$exporter = new Spreadsheet([
		    'dataProvider' => new ArrayDataProvider([
		        'allModels' => [
		            [
		                'id' => 1,
		                'name' => 'first',
		            ],
		            [
		                'id' => 2,
		                'name' => 'second',
		            ],
		        ],
		    ]),
		    'columns' => [
		        [
		            'class' => SerialColumn::class,
		        ],
		        [
		            'attribute' => 'id',
		        ],
		        [
		            'attribute' => 'name',
		        ],
		    ],
		]); //->render(); // render the document

		// override serial column header :
		$exporter->renderCell('A1', 'Overridden serial column header');

		// add custom footer :
		$exporter->renderCell('A4', 'Custom A4', [
		    'font' => [
		        'color' => [
		            'rgb' => '#FF0000',
		        ],
		    ],
		]);

		// merge footer cells :
		$exporter->mergeCells('A4:B4');

		$exporter->send('/referrals/template/files.xls');
	}
}
