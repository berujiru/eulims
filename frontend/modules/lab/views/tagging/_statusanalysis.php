<?php
use yii\helpers\Html;
use kartik\widgets\DatePicker;
use common\models\lab\Tagging;
use common\models\lab\Analysis;
use yii\widgets\ActiveForm;
use kartik\widgets\DateTimePicker;
use kartik\export\ExportMenu;
use kartik\grid\GridView;
use yii\helpers\ArrayHelper;
use common\models\services\Testcategory;
use common\components\Functions;
use yii\data\ActiveDataProvider;
use kartik\detail\DetailView;

use kartik\widgets\Select2;
use kartik\widgets\DepDrop;

use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\widgets\TypeaheadBasic;
use kartik\widgets\Typeahead;

use common\models\lab\Lab;

use common\models\lab\Workflow;
use common\models\lab\Labsampletype;
use common\models\lab\Sampletype;
use common\models\lab\Sampletypetestname;
use common\models\lab\Testnamemethod;
use common\models\lab\Methodreference;
use common\models\lab\Testname;
use common\models\lab\Tagginganalysis;
use kartik\money\MaskMoney;
use common\models\system\Profile;

?>

<?= GridView::widget([
        'dataProvider' => $analysisdataprovider,
        'id'=>'analysis-grid',
        'pjax' => true,
        'toolbar' => false,
        'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-analysis']],
            'pjaxSettings' => [
                'options' => [
                    'enablePushState' => false,
                ]
            ],
            'floatHeaderOptions' => ['scrollingTop' => true],
            'columns' => [
                     [
                        'header'=>'Test Name',
                        'format' => 'raw',
                        'enableSorting' => false,
                        'value' => function($model) {
                            return "<b>".$model->testname."</b>";
                        },
                        'contentOptions' => ['style' => 'width:20%; white-space: normal;'],                 
                    ],
                    [
                        'header'=>'Method',
                        'format' => 'raw',
                        'enableSorting' => false,
                        'value' => function($model) {
                            return $model->method;
                        },
                        'contentOptions' => ['style' => 'width:40%; white-space: normal;'],                      
                    ],
                    [
                        'header'=>'Analyst',
                        'format' => 'raw',
                        'enableSorting' => false,
                        'value'=> function ($model){
                           
                                // $tagging = Tagging::find()->where(['cancelled_by'=> $model->analysis_id])->one(); 
                                // if ($tagging)
                                // {
                                // $profile= Profile::find()->where(['user_id'=> $tagging->user_id])->one();
                                //     if ($profile){
                                //         return $profile->firstname.' '. strtoupper(substr($profile->middleinitial,0,1)).'. '.$profile->lastname;
                                //     }else{
                                //         return '';
                                //     }
                                    
                                // }else{
                                //     return '';
                                // }
                                if ($model->tagging){
                                    $profile= Profile::find()->where(['user_id'=> $model->tagging->user_id])->one();
                                    return $profile->firstname.' '. strtoupper(substr($profile->middleinitial,0,1)).'. '.$profile->lastname;
                                }else{
                                    return "";
                                }
                        
                           
                        },
                        'contentOptions' => ['style' => 'width:20%; white-space: normal;'],                   
                    ],
                    // [
                    //     'header'=>'Progress',
                    //     'hAlign'=>'center',
                    //     'format' => 'raw',
                    //     'enableSorting' => false,
                    //     'value'=> function ($model){
                    //         $analysis = Analysis::findOne(['analysis_id' => $model->analysis_id]);
                    //         $modelmethod=  Methodreference::findOne(['method'=>$analysis->method]);                              
                    //         $testnamemethod = Testnamemethod::findOne(['testname_id'=>$analysis->test_id, 'method_id'=>$analysis->testcategory_id]);                           
                    //         if ($testnamemethod){
                    //             $count = Workflow::find()->where(['testname_method_id'=>$testnamemethod->testname_method_id])->count();     
                                
                              
                    //             if ($count==0){
                    //                 return $analysis->completed.'/'.$count;
                    //             }else{
                    //                 $percent = $analysis->completed / $count * 100;
                    //                 $formattedNum = number_format($percent);
                                    
                    //                 return $analysis->completed.'/'.$count." = ".$formattedNum."%";  
                    //             }
                    //         }else{
                    //             return "";
                    //         }
                            
                                           
                    //     },
                    //     'contentOptions' => ['style' => 'width:8%; white-space: normal;'],                   
                    // ],
                //     // [
                //     //     'header'=>'Cycle Time',
                //     //     'format' => 'raw',
                //     //     'enableSorting' => false,
                //     //     'value'=> function ($model){
                //     //         return "";
                           
                //     //     },
                //     //     'contentOptions' => ['style' => 'width:40px; white-space: normal;'],                   
                //     // ],
                    [
                          'header'=>'Status',
                          'hAlign'=>'center',
                          'format'=>'raw',
                          'value' => function($model) {
                        if($model->references == "-")
                            return "N/A";

                        if ($model->tagging){

                            if ($model->tagging->tagging_status_id==1) {
                                   return "<span class='badge btn-primary' style='width:90px;height:20px'>ONGOING</span>";
                               }else if ($model->tagging->tagging_status_id==2) {
                                   return "<span class='badge btn-success' style='width:90px;height:20px'>COMPLETED</span>";
                               }
                               else if ($model->tagging->tagging_status_id==3) {
                                   return "<span class='badge btn-warning' style='width:90px;height:20px'>ASSIGNED</span>";
                               }
                               else if ($model->tagging->tagging_status_id==4) {
                                   return "<span class='badge btn-danger' style='width:90px;height:20px'>CANCELLED</span>";
                               }
                                
                         
                           }else{
                               return "<span class='badge btn-default' style='width:80px;height:20px'>PENDING</span>";
                           }


                            }
                      ],
                    [
                        'header'=>'Remarks',
                        'format' => 'raw',
                        'width' => '100px',
                        'value' => function($model) {

                            $tagginganalysis = Tagginganalysis::findOne(['cancelled_by' => $model->analysis_id]);
                           if ($tagginganalysis){


                                return "<b>Start Date:&nbsp;&nbsp;</b>".$tagginganalysis->start_date."
                                <br><b>End Date:&nbsp;&nbsp;</b>".$tagginganalysis->end_date;
                                return "";
                            }else{
                                return "<b>Start Date: <br>End Date:</b>";
                            }
                           
                    },
                        'enableSorting' => false,
                        'contentOptions' => ['style' => 'width:10%; white-space: normal;'],
                ],
            
        ],
    ]); 
    ?>