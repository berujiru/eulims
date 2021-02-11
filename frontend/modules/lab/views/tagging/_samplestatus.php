<?php

use common\models\lab\Sample;
use common\models\lab\Analysis;
use kartik\grid\GridView;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use common\models\lab\Tagging;
use common\models\lab\Tagginganalysis;
use common\models\lab\Workflow;
use common\models\lab\Testnamemethod;
use common\models\lab\Methodreference;
use common\models\lab\Request;
use common\models\lab\Procedure;
use common\models\TaggingSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Query;
use common\models\system\Profile;

?>

<span style="display:inline-block;">

<?php echo "<font color='#666666'>Request Reference #:</font><br \><b style='font-size:1.25em;'>". $request->request_ref_num."</b>";?>

</span>

<?php

$samples_count= Sample::find() 
->leftJoin('tbl_request', 'tbl_request.request_id=tbl_sample.request_id')    
->where(['tbl_request.request_id'=>$request->request_id ])
->all();

$sampletagged= Sample::find()
->leftJoin('tbl_analysis', 'tbl_sample.sample_id=tbl_analysis.sample_id')
->leftJoin('tbl_tagging', 'tbl_analysis.analysis_id=tbl_tagging.analysis_id') 
->leftJoin('tbl_request', 'tbl_request.request_id=tbl_analysis.request_id')    
->where(['tbl_tagging.tagging_status_id'=>2, 'tbl_request.request_id'=>$request->request_id])
->all();  
//$st = count($sampletagged);
// $requestcount= Sample::find()
// ->leftJoin('tbl_request', 'tbl_sample.request_id=tbl_request.request_id')
// ->leftJoin('tbl_analysis', 'tbl_sample.sample_id=tbl_analysis.sample_id')
// ->leftJoin('tbl_tagging', 'tbl_analysis.analysis_id=tbl_tagging.cancelled_by')
// ->where(['tbl_tagging.tagging_status_id'=>2, 'tbl_request.request_id'=>$request->request_id ])   
// ->all();

// $requestcount= Request::find()
// ->where(['tbl_request.request_id'=>$request->request_id ]) 
// ->all();  

$scount = count($samples_count); 
$rcount = count($sampletagged); 

//echo $rcount."<br>".$scount."<br>";

if ($rcount==0){
    echo "<span class='payment alert-default' style='float:right; min-width:80px; min-height:30px; line-height:30px;text-align:center;display:inline-block;font-weight:bold;'>PENDING</span><br><br>";
}elseif ($scount>$rcount){
    echo "<span class='payment alert-info' style='float:right; min-width:80px; min-height:30px; line-height:30px;text-align:center;display:inline-block;font-weight:bold;'>ONGOING</span><br><br>";
}elseif ($scount==$rcount){
    echo "<span class='payment alert-success' style='float:right; min-width:80px; min-height:30px; line-height:30px;text-align:center;display:inline-block;font-weight:bold;'>COMPLETED</span><br><br>";
}




?>

<?php
//  GridView::widget([
//     'dataProvider' => $sampledataprovider,
//     'pjax'=>true,
//     'headerRowOptions' => ['class' => 'kartik-sheet-style'],
//     'filterRowOptions' => ['class' => 'kartik-sheet-style'],
//     'bordered' => true,
//     'id'=>'sample-grid',
//     'striped' => true,
//     'condensed' => true,
//     'responsive'=>false,
//     'containerOptions'=>[
//         'style'=>'overflow:auto; height:320px',
//     ],
//     'pjaxSettings' => [
//         'options' => [
//             'enablePushState' => false,
//         ]
//     ],
//     'floatHeaderOptions' => ['scrollingTop' => true],
//     'columns' => [
//      [
//         'header'=>'Sample Code',
//         'hAlign'=>'left',
//         'format' => 'raw',
//         'enableSorting' => false,
//         'value'=> function ($model){ 
//             $sample = Sample::find()->where(['sample_id' => $model->sample_id])->one();
//             return $sample->sample_code;
//         },
//         'contentOptions' => ['style' => 'width:15%; white-space: normal;'],                   
//     ],
//     [
//         'header'=>'Sample Name',
//         'hAlign'=>'left',
//         'format' => 'raw',
//         'enableSorting' => false,
//         'value'=> function ($model){
//             $sample = Sample::find()->where(['sample_id' => $model->sample_id])->one();
//             return $sample->samplename;
//         },
//         'contentOptions' => ['style' => 'width:15%; white-space: normal;'],                   
//     ],
//     [
//         'header'=>'Progress',
//         'hAlign'=>'left',
//         'format' => 'raw',
//         'enableSorting' => false,
//         'value'=> function ($model){
//           //$workflow= Workflow::find()->where(['workflow_id'=> $model->analysis_id])->one(); 
//          return "";
//         },
//         'contentOptions' => ['style' => 'width:15%; white-space: normal;'],                   
//     ],
//     [
//         'header'=>'Status',
//         'hAlign'=>'left',
//         'format' => 'raw',
//         'enableSorting' => false,
//         'value'=> function ($model){
//           //$workflow= Workflow::find()->where(['workflow_id'=> $model->analysis_id])->one(); 
//          return "";
//         },
//         'contentOptions' => ['style' => 'width:15%; white-space: normal;'],                   
//     ],
         
// ],
// ]); 
?>
<div class="testnamemethod-index">
<?php
               $gridColumns = [
              
                [
                    'class' => 'kartik\grid\ExpandRowColumn',
                    'width' => '1%',
                    'value' => function ($model, $key, $index, $column) {
                        return GridView::ROW_COLLAPSED;
                    },
                    'detail' => function ($model, $key, $index, $column) {
         
                        $analysisQuery = Analysis::find()->where(['sample_id' => $model->sample_id]);
                        $analysis_id = $model->sample_id;
                        $id = $model->sample_id;
                        $model = new Tagging();

                        
                                    $analysisdataprovider = new ActiveDataProvider([
                                            'query' =>  $analysisQuery,
                                            'pagination' => [
                                                'pageSize' => false,
                                                    ],                 
                                    ]);
                                        
                                    if(Yii::$app->request->isAjax){
                                        return $this->renderAjax('_statusanalysis', [
                                            'model'=>$model,
                                            'analysisQuery'=>$analysisQuery,
                                            'analysisdataprovider'=> $analysisdataprovider,
                                            'analysis_id'=>$analysis_id,
                                            'id'=>$id,
                                            ]);
                     }
                    },
                    'headerOptions' => ['class' => 'kartik-sheet-style'],
                    'expandOneOnly' => true
                ],
                [
                            'header'=>'Sample Code',
                            'hAlign'=>'left',
                            'format' => 'raw',
                            'enableSorting' => false,
                            'value'=> function ($model){ 
                                $sample = Sample::find()->where(['sample_id' => $model->sample_id])->one();
                                return $sample->sample_code;
                            },
                            'contentOptions' => ['style' => 'width:15%; white-space: normal;'],                   
                        ],
                        [
                            'header'=>'Sample Name',
                            'hAlign'=>'left',
                            'format' => 'raw',
                            'enableSorting' => false,
                            'value'=> function ($model){
                                $sample = Sample::find()->where(['sample_id' => $model->sample_id])->one();
                                return $sample->samplename;
                            },
                            'contentOptions' => ['style' => 'width:15%; white-space: normal;'],                   
                        ],
                        [
                            'header'=>'Progress',
                            'hAlign'=>'center',
                            'format' => 'raw',
                            'enableSorting' => false,
                            'value'=> function ($model){
                             
                            $samples = Sample::find()->where(['sample_id' =>$model->sample_id])->one();
                            $count = Analysis::find()->where(['sample_id' =>$model->sample_id])->andWhere(['<>','references', '-'])->count();
                             
                            if ($count==0){
                                return $samples->completed.'/'.$count;
                            }else{
                                $percent = $samples->completed / $count * 100;
                                $formattedNum = number_format($percent);
                                
                                return "<b>".$samples->completed.'/'.$count." = ".$formattedNum."%</b>";
                                
                               // return 
                            }

                            },
                            'contentOptions' => ['style' => 'width:15%; white-space: normal;'],                   
                        ],
                        [
                            'header'=>'Status',
                            'hAlign'=>'center',
                            'format' => 'raw',
                            'enableSorting' => false,
                            'value'=> function ($model){
                                $samples = Sample::find()->where(['sample_id' =>$model->sample_id])->one();
                                $count = Analysis::find()->where(['sample_id' =>$model->sample_id])->andWhere(['<>','references', '-'])->count();
                             
                                if ($samples->completed==0) {
                                    return "<span class='badge btn-default' style='width:90px;height:20px'>PENDING</span>";
                                   
                                }else if ($samples->completed==$count) {
                                        return "<span class='badge btn-success' style='width:90px;height:20px'>COMPLETED</span>";
                                        
                                    }
                                    else if ($samples->completed>=1) {
                                        return "<span class='badge btn-primary' style='width:90px;height:20px'>ONGOING</span>";
                                    }
                                    else if ($samples->completed==0) {
                                        
                                    }
                             
                              
                            },
                            'contentOptions' => ['style' => 'width:15%; white-space: normal;'],                   
                        ],
            ];   
            echo GridView::widget([
                'id'=>'testname-grid',
                'dataProvider' => $sampledataprovider,
                'toolbar' => false,
                'pjax'=>true,
                
                'pjaxSettings' => [
                    'options' => [
                        'enablePushState' => false,
                    ]
                ],
               'responsive'=>true,
                'striped'=>true,
                'hover'=>true,
                'panel' => [
                    //'heading'=>'<h3 class="panel-title"> <i class="glyphicon glyphicon-file"></i> Workflow Management</h3>',
                    'type'=>'primary',
                ],
                'columns' => $gridColumns,
            
            ]);
        ?>

</div>