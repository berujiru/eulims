<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use common\models\lab\Lab;
use kartik\datetime\DateTimePicker;
use common\models\lab\Customer;
use yii\web\JsExpression;
use kartik\widgets\DatePicker;
use common\models\lab\Paymenttype;
use common\models\lab\Discount;
use common\models\lab\Modeofrelease;
use common\models\lab\Purpose;
use kartik\widgets\SwitchInput;
use common\components\Functions;
use yii\bootstrap\Modal;
use common\models\lab\RequestType;
use common\models\lab\Request;
use common\models\lab\Testcategory;
use common\models\lab\Sampletype;
use kartik\widgets\DepDrop;
use yii\helpers\Url;
use yii\web\View;
use kartik\grid\GridView;
use kartik\dialog\Dialog;
use yii\helpers\Json;

/* @var $this yii\web\View */
/* @var $model common\models\lab\Request */
/* @var $form yii\widgets\ActiveForm */

$disabled= $model->posted ? true:false;
$disabled=$disabled || $model->status_id==2;
if($disabled){
    $Color="#eee";
}else{
    $Color="white";
}
if($model->lab_id==3){
    $PanelStyle='';
}else{
    $PanelStyle='display: none';
}
$js=<<<SCRIPT
    if(this.value==1){//Paid
        $("#erequest-discount_id").val(0).trigger('change');
        $("#erequest-discount_id").prop('disabled',false);
    }else{//Fully Subsidized
        $("#erequest-discount_id").val(0).trigger('change');
        $("#erequest-discount_id").prop('disabled',true);
    }
    $("#erequest-payment_type_id").val(this.value);  
SCRIPT;

        
$rstl_id= Yii::$app->user->identity->profile->rstl_id;
// Check Whether previous date will be disabled
$TRequest=Request::find()->where(["DATE_FORMAT(`request_datetime`,'%Y-%m-%d')"=>date("Y-m-d"),'rstl_id'=>$rstl_id])->count();
if($TRequest>0){
    $RequestStartDate=date("Y-m-d");
}else{
    $RequestStartDate="";
}

$model->modeofreleaseids=$model->modeofrelease_ids;

if(count($testcategory) > 0){
    $dataTestcategory = $testcategory;
} else {
    $dataTestcategory = [];
}

if(count($sampletype) > 0){
    $dataSampletype = $sampletype;
} else {
    $dataSampletype = [];
}
?>

<div class="pstc-request-form">
    <?php $form = ActiveForm::begin(['action' => ['savetolocal'],'options' => ['method' => 'post']]); ?>
    <?php if($model->request_ref_num!=NULL){ ?>
    <?= $form->field($model, 'request_ref_num')->hiddenInput(['maxlength' => true])->label(false) ?>
    <?php } ?>
    <div class="row">
       <?php echo Html::hiddenInput('request_id', $_GET['request_id']); ?>
        <div class="col-md-6">
        <?= $form->field($model, 'request_type_id')->widget(Select2::classname(), [
            'data' => ArrayHelper::map(RequestType::find()->all(),'request_type_id','request_type'),
            'language' => 'en',
            'options' => ['placeholder' => 'Select Request Type','disabled'=>$disabled],
            'pluginOptions' => [
                'allowClear' => false
            ]
        ])->label('Request Type'); ?>
        </div>
        <div class="col-md-6">
        <?= $form->field($model, 'request_datetime')->widget(DateTimePicker::classname(), [
            'readonly'=>true,
            'disabled' => $disabled,
            'options' => ['placeholder' => 'Enter Date'],
            'value'=>function($model){
                return date("m/d/Y h:i:s P", strtotime($model->request_datetime));
            },
            'convertFormat' => true,
    	'pluginOptions' => [
                'autoclose' => true,
                'removeButton' => false,
                'todayHighlight' => true,
                'todayBtn' => true,
                'format' => 'php:Y-m-d H:i:s',
                'startDate'=>$RequestStartDate,
    	],
            'pluginEvents'=>[
                "changeDate" => "function(e) { 
                    var dv=$('#erequest-request_datetime').val();
                    var d=dv.split(' ');
                    $('#erequest-request_date').val(d[0]);
                }",
            ]
        ])->label('Request Date'); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?php /*= $form->field($model, 'lab_id')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Lab::find()->all(),'lab_id','labname'),
                //'type'=>DepDrop::TYPE_SELECT2,
                'language' => 'en',
                'options' => ['placeholder' => 'Select Laboratory','disabled'=>$disabled],
                'pluginOptions'=>[
                   //'depends'=>['rstlid','erequest-request_type_id'],
                   'placeholder'=>'Select Laboratory',
                   //'url'=>Url::to(['/api/ajax/getlab']),
                   'LoadingText'=>'Loading...',
                   'allowClear' => true
                ],
                'pluginEvents'=>[
                    "change" => "function() { 
                        if(this.value==3){//Metrology
                           $('#div_met').show();
                        }else{
                           $('#div_met').hide();
                        }

                    }",
                ]
            ])->label('Laboratory');*/ ?>
            <?php
                $testcategoryOptions = [
                    'language' => 'en-US',
                    'width' => '100%',
                    'theme' => Select2::THEME_KRAJEE,
                    'placeholder' => 'Select Test Category',
                    'allowClear' => true,
                ];

                $sampletypeOptions = [
                    'language' => 'en-US',
                    'width' => '100%',
                    'theme' => Select2::THEME_KRAJEE,
                    'placeholder' => 'Select Sample Type',
                    'allowClear' => true,
                ];

                echo $form->field($model,'lab_id')->widget(Select2::classname(),[
                    //'data' => $testcategory,
                    'data' => ArrayHelper::map(Lab::find()->all(),'lab_id','labname'),
                    'theme' => Select2::THEME_KRAJEE,
                    //'theme' => Select2::THEME_BOOTSTRAP,
                    //'options' => $testcategoryOptions,
                    //'pluginOptions' => [
                    //    'allowClear' => true,
                    //],
                    'language' => 'en',
                    'options' => ['placeholder' => 'Select Laboratory','disabled'=>$disabled],
                    'pluginOptions'=>[
                       //'depends'=>['rstlid','erequest-request_type_id'],
                       'placeholder'=>'Select Laboratory',
                       //'url'=>Url::to(['/api/ajax/getlab']),
                       'LoadingText'=>'Loading...',
                       'allowClear' => true
                    ],
                    // 'pluginEvents' => [
                    //     "change" => "function() {
                    //         if(this.value == 3){//Metrology
                    //             $('#div_met').show();
                    //         } else {
                    //             $('#div_met').hide();
                    //         }

                    //         var labId = this.value;
                    //         var select = $('#erequest-testcategory_id');
                    //         select.find('option').remove().end();
                    //         if (labId > 0){
                    //             $.ajax({
                    //                 url: '".Url::toRoute("pstcrequest/get_testcategory")."',
                    //                 method: 'GET',
                    //                 data: {lab_id:labId},
                    //                 success: function (data) {
                    //                     var select2options = ".Json::encode($testcategoryOptions).";
                    //                     select2options.data = data.data;
                    //                     select.select2(select2options);
                    //                     select.val(data.selected).trigger('change');
                    //                     $('.image-loader').removeClass('img-loader');
                    //                 },
                    //                 beforeSend: function (xhr) {
                    //                     $('.image-loader').addClass('img-loader');
                    //                 },
                    //                 error: function (jqXHR, textStatus, errorThrown) {
                    //                     alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>Error Encountered!</p>\");
                    //                 }
                    //             });
                    //         } else {
                    //             //alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No sample type selected!</p>\");
                    //             console.log('No lab selected!');
                    //             select.val('').trigger('change');
                    //         }
                    //     }",
                    // ],
                ])->label('Laboratory');
            ?>
        </div>
        <div class="col-md-6">
            <label class="control-label">Payment Type</label>
            <div class="col-md-12">
            <?php echo $form->field($model, 'payment_type_id')->radioList(
                ArrayHelper::map(Paymenttype::find()->all(),'payment_type_id','type'),
                ['itemOptions' => ['required' => true,'disabled' => $disabled,'onchange'=>$js]]
            )->label(false); ?>
            </div>
        </div>
    </div>
    <!-- <div class="panel panel-success" id="div_met" style="padding-bottom: 10px;<?= $PanelStyle ?>">
        <div class="panel-heading">Metrology Request Details</div>
        <div class="row" style="padding-left: 5px">*
            <div class="col-md-6">
                <label class="control-label">Recommended Due Date</label>
                <div class="col-md-12">
                    <?php
                    echo DatePicker::widget([
                        'model' => $model,
                        'attribute' => 'recommended_due_date',
                        'readonly' => true,
                        'disabled' => $disabled,
                        'options' => ['placeholder' => 'Enter Date'],
                        'value' => function($model) {
                            return date("m/d/Y", $model->recommended_due_date);
                        },
                        'pluginOptions' => [
                            'autoclose' => true,
                            'removeButton' => false,
                            'format' => 'yyyy-mm-dd'
                        ],
                        'pluginEvents' => [
                            "change" => "function() {  }",
                        ]
                    ]);
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="control-label">Estimated Date Completion</label>
                <div class="col-md-12">
                    <?php
                    echo DatePicker::widget([
                        'model' => $model,
                        'attribute' => 'est_date_completion',
                        'readonly' => true,
                        'disabled' => $disabled,
                        'options' => ['placeholder' => 'Enter Date'],
                        'value' => function($model) {
                            return date("m/d/Y", $model->est_date_completion);
                        },
                        'pluginOptions' => [
                            'autoclose' => true,
                            'removeButton' => false,
                            'format' => 'yyyy-mm-dd'
                        ],
                        'pluginEvents' => [
                            "change" => "function() {  }",
                        ]
                    ]);
                    ?>
                </div>
            </div>
        </div>
        <div class="row" style="padding-left: 5px">
            <div class="col-md-6">
                <label class="control-label">Date Release of Equipment</label>
                <div class="col-md-12">
                    <?php
                    echo DatePicker::widget([
                        'model' => $model,
                        'attribute' => 'equipment_release_date',
                        'readonly' => true,
                        'disabled' => $disabled,
                        'options' => ['placeholder' => 'Enter Date'],
                        'value' => function($model) {
                            return date("m/d/Y", $model->equipment_release_date);
                        },
                        'pluginOptions' => [
                            'autoclose' => true,
                            'removeButton' => false,
                            'format' => 'yyyy-mm-dd'
                        ],
                        'pluginEvents' => [
                            "change" => "function() {  }",
                        ]
                    ]);
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="control-label">Date Release of Certificate</label>
                <div class="col-md-12">
                    <?php
                    echo DatePicker::widget([
                        'model' => $model,
                        'attribute' => 'certificate_release_date',
                        'readonly' => true,
                        'disabled' => $disabled,
                        'options' => ['placeholder' => 'Enter Date'],
                        'value' => function($model) {
                            return date("m/d/Y", $model->certificate_release_date);
                        },
                        'pluginOptions' => [
                            'autoclose' => true,
                            'removeButton' => false,
                            'format' => 'yyyy-mm-dd'
                        ],
                        'pluginEvents' => [
                            "change" => "function() {  }",
                        ]
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div> -->
    <div class="row">
        <div class="col-md-6">
            <label class="control-label">Customer</label>
            <div class="alert alert-info" style="background: #d4f7e8 !important;margin-top: 1px !important;">
                <a href="#" class="close" data-dismiss="alert">×</a>
                <p class="note" style="color:#265e8d"><b><?=$customer['customer_name']?></b><br/><i>Customer Information will be downloaded too.</i></p>
            </div>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'modeofreleaseids')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Modeofrelease::find()->all(),'modeofrelease_id','mode'),
                //'initValueText'=>$model->modeofrelease_ids,
                'language' => 'en',
                 'options' => [
                    'placeholder' => 'Select Mode of Release...',
                    'multiple' => true,
                    'required' => true,
                    'disabled'=>$disabled
                ],
                'pluginEvents' => [
                    "change" => "function() { 
                        $('#modeofrelease_ids').val($(this).val());
                    }
                    ",
                ]
            ])->label('Mode of Release'); ?> 
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'discount_id')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Discount::find()->all(),'discount_id','type'),
                'language' => 'en',
                'options' => ['placeholder' => 'Select Discount','disabled'=>$disabled || $model->payment_type_id==2],
                'pluginOptions' => [
                    'allowClear' => true
                ],
                'pluginEvents'=>[
                    "change" => 'function() { 
                        var discountid=this.value;
                        console.log(discountid);
                        $.post("/ajax/getdiscount/", {
                                discountid: discountid
                            }, function(result){
                            if(result){
                               $("#erequest-discount").val(result.rate);
                            }
                        });
                    }
                ',]
            ])->label('Discount'); ?>   
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'discount')->textInput(['maxlength' => true,'readonly'=>true,'style'=>'background-color: '.$Color])->label('Discount(%)') ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'purpose_id')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Purpose::find()->all(),'purpose_id','name'),
                'language' => 'en',
                'options' => ['placeholder' => 'Select Purpose','disabled'=>$disabled],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ])->label('Purpose'); ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'report_due')->widget(DatePicker::classname(), [
                'readonly'=>true,
                'disabled' => $disabled,
                'options' => ['placeholder' => 'Report Due'],
                'value'=>function($model){
                    return date("m/d/Y",$model->report_due);
                },
                'pluginOptions' => [
                    'autoclose' => true,
                    'removeButton' => false,
                    'format' => 'yyyy-mm-dd'
                ],
                'pluginEvents'=>[
                    "change" => "",
                ]
            ])->label('Report Due'); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'conforme')->textInput(['readonly' => $disabled]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'receivedBy')->textInput(['readonly' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
        <?php
            $sampleGridColumns = [
                [
                    'header' => 'Sample Code',
                    'attribute'=>'sample_code',
                    'enableSorting' => false,
                    'format' => 'raw',
                    'contentOptions' => [
                        'style'=>'max-width:70px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                [
                    'header' => 'Sample Name',
                    'attribute'=>'sample_name',
                    'enableSorting' => false,
                    'format' => 'raw',
                    'contentOptions' => [
                        'style'=>'max-width:70px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                [
                    'header' => 'Sample Description',
                    'attribute'=>'sample_description',
                    'format' => 'raw',
                    'enableSorting' => false,
                    //'value' => function($data) use ($request){
                    //    return ($request->lab_id == 2) ? "Sampling Date: <span style='color:#000077;'><b>".date("Y-m-d h:i A",strtotime($data->sampling_date))."</b></span>,&nbsp;".$data->description : $data->description;
                    //},
                    'contentOptions' => [
                        'style'=>'max-width:180px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                /*[
                    'header' => 'Test Category',
                    'attribute'=>'testcategory_id',
                    'format' => 'raw',
                    'enableSorting' => false,
                    'contentOptions' => [
                        'style'=>'max-width:180px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                [
                    'header' => 'Sample Type',
                    'attribute'=>'sampletype_id',
                    'format' => 'raw',
                    'enableSorting' => false,
                    'contentOptions' => [
                        'style'=>'max-width:180px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],*/
            ];

            // echo GridView::widget([
            //     'id' => 'sample-grid',
            //     //'dataProvider'=> $sampleDataProvider,
            //     'dataProvider'=> $sampleDataProvider,
            //     'pjax'=>true,
            //     'pjaxSettings' => [
            //         'options' => [
            //             'enablePushState' => false,
            //         ]
            //     ],
            //     'responsive'=>true,
            //     'striped'=>true,
            //     'hover'=>true,
            //     'panel' => [
            //         'heading'=>'<h3 class="panel-title">Samples</h3>',
            //         'type'=>'primary',
            //         //'before'=>null,
            //         'before' => 'List of samples that will be saved.',
            //         'after'=>false,
            //         'footer'=>false,
            //         //'before'=> Html::button('<i class="glyphicon glyphicon-plus"></i> Add Sample', ['value' => Url::to(['/pstc/pstcsample/create','request_id'=>$request['pstc_request_id']]),'title'=>'Add Sample', 'onclick'=>'addSample(this.value,this.title)', 'class' => 'btn btn-success','id' => 'modalBtn']),
            //     ],
            //     'columns' => $sampleGridColumns,
            //     'toolbar' => [
            //         // 'content'=> Html::a('<i class="glyphicon glyphicon-repeat"></i> Refresh Grid', [Url::to(['/pstc/pstcrequest/view','request_id'=>$request['pstc_request_id']])], [
            //         //             'class' => 'btn btn-default', 
            //         //             'title' => 'Refresh Grid'
            //         //         ]),
            //     ],
            // ]);
        ?>
        </div>
    </div>
    <!-- <div class="row">
        <div class="col-md-12">
        <?php
            $analysisgridColumns = [
                [
                    'attribute'=>'sample_code',
                    'header'=>'Sample Code',
                    //'value' => function($data) use ($sample) {
                    //    return !empty($sample) ? $sample['sample_code'] : null;
                    //},
                    'format' => 'raw',
                    'enableSorting' => false,
                    'contentOptions' => ['style' => 'width:10%; white-space: normal;'],
                ],
                [
                    'attribute'=>'sample_name',
                    'header'=>'Sample Name',
                    'format' => 'raw',
                    'enableSorting' => false,
                    //'value' => function($data) use ($sample) {
                    //    return !empty($data['sample']) ? $sample['sample_name'] : null;
                    //},
                    'contentOptions' => ['style' => 'width:10%; white-space: normal;'],
                ],
                [
                    //'attribute'=>'testnames.testName',
                    'format' => 'raw',
                    'header'=>'Test/ Calibration Requested',
                    'contentOptions' => ['style' => 'width: 15%;word-wrap: break-word;white-space:pre-line;'],
                    'enableSorting' => false,
                    'value' => function($data) {
                       if($data['is_package_name'] == 1){
                            return $data['package_name'];
                        } elseif($data['is_package_name'] == 0 && $data['is_package'] == 1){
                            return "&nbsp;&nbsp;<span style='font-size:12px;'>".$data['testname']."</span>";
                        } else {
                            return $data['testname'];
                        }
                    },
                    // 'value' => function($data) {
                    //     if($data->is_package_name == 1){
                    //         return $data->package_name;
                    //     } elseif($data->is_package_name == 0 && $data->is_package == 1){
                    //         return "&nbsp;&nbsp;<span style='font-size:12px;'>".(!empty($data->testnames) ? $data->testnames->testName : null)."</span>";
                    //     } else {
                    //         return !empty($data->testnames) ? $data->testnames->testName : null;
                    //     }
                    // },
                ],
                [
                    //'attribute'=>'methodrefs.method',
                    'format' => 'raw',
                    'header'=>'Test Method',
                    'enableSorting' => false,
                    // 'value' => function($request) {
                    //    return !empty($request->methodrefs) ? $request->methodrefs->method : null;
                    // },
                    'value' => function($data) {
                        if($data['is_package_name'] == 1){
                            return '-';
                        } elseif($data['is_package_name'] == 0 && $data['is_package'] == 1){
                            return "&nbsp;&nbsp;<span style='font-size:12px;'>".$data['method']."</span>";
                        } else {
                            return $data['method'];
                        }
                    },
                    'contentOptions' => ['style' => 'width: 50%;word-wrap: break-word;white-space:pre-line;'],
                    'pageSummary' => function() use ($countSample, $countAnalysis) {
                        return ($countAnalysis > 0 && $countSample > 0) ? '<span style="float:right";>SUBTOTAL<BR>DISCOUNT<BR><B>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;TOTAL</B></span>' : '';
                    },
                ],
                [
                    'attribute'=>'fee',
                    'header'=>'Unit Price',
                    'enableSorting' => false,
                    'hAlign'=>'right',
                    'format' => 'raw',
                    // 'value'=>function($request){
                    //     return number_format($request->fee,2);
                    // },
                    'value'=>function($data){
                        return ($data['is_package_name'] == 0 & $data['is_package'] == 1) ? '-' : number_format($data['fee'],2);
                    },
                    'contentOptions' => [
                        'style'=>'max-width:80px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                    'hAlign' => 'right', 
                    'vAlign' => 'left',
                    'width' => '7%',
                    'format' => 'raw',
                    'pageSummary'=> function () use ($subtotal,$discounted,$total,$countSample,$countAnalysis) {
                        return ($countSample > 0 && $countAnalysis > 0) ? '<div id="subtotal">₱ '.number_format($subtotal, 2).'</div><div id="discount">₱ '.number_format($discounted, 2).'</div><div id="total"><b>₱ '.number_format($total, 2).'</b></div>' : '';
                    },
                ],
            ];
            echo GridView::widget([
                'id' => 'analysis-grid',
                'responsive'=>true,
                'dataProvider'=> $analysisDataprovider,
                'pjax'=>true,
                'pjaxSettings' => [
                    'options' => [
                        'enablePushState' => false,
                    ]
                ],
                'responsive'=>true,
                'striped'=>true,
                'hover'=>true,
                'showPageSummary' => true,
                'hover'=>true,
                
                'panel' => [
                    'heading'=>'<h3 class="panel-title">Analysis</h3>',
                    'type'=>'primary',
                    'after' => false,
                    'before' => 'List of analyses that will be saved.',
                    //'footer'=>$actionButtonConfirm.$actionButtonSaveLocal,
                    'footer'=>false,
                ],
                'columns' => $analysisgridColumns,
                'toolbar' => [
                    //'content'=> Html::a('<i class="glyphicon glyphicon-repeat"></i> Refresh Grid', [Url::to(['/pstc/pstcrequest/view','request_id'=>$request['pstc_request_id']])], [
                    //            'class' => 'btn btn-default', 
                    //            'title' => 'Refresh Grid'
                    //        ]),
                ],
            ]);
        ?>
        </div>
    </div> -->
    <div class="row" style="float: right;padding-right: 15px">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['disabled'=>$disabled,'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary','id'=>'btn-save']) ?>
        <?= Html::Button('Close', ['class' => 'btn btn-default', 'id' => 'modalCancel', 'data-dismiss' => 'modal']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<?php
// Warning alert
echo Dialog::widget([
    'libName' => 'alertWarning', // a custom lib name
    'overrideYiiConfirm' => false,
    'options' => [  // customized BootstrapDialog options
        'size' => Dialog::SIZE_SMALL, // large dialog text
        'type' => Dialog::TYPE_DANGER, // bootstrap contextual color
        'title' => "<i class='glyphicon glyphicon-alert' style='font-size:20px'></i> Warning",
        'buttonLabel' => 'Close',
    ]
]);

?>

<script type="text/javascript">
    $('#btn-save').on('click',function() {
        var request_type = $('#erequest-request_type_id').val();
        var lab = $('#erequest-lab_id').val();
        var customer = $('#erequest-customer_id').val();
        var discount = $('#erequest-discount_id').val();
        var purpose = $('#erequest-purpose_id').val();
        var conforme = $('#erequest-conforme').val();
        var request_datetime = $('#erequest-request_datetime').val();
        var modeofrelease = $('#erequest-modeofreleaseids').val();
        var due = $('#erequest-report_due').val();
        var received = $('#erequest-receivedby').val();
        //var test_category = $('#erequest-testcategory_id').val();
        //var sample_type = $('#erequest-sampletype_id').val();
        //if metro lab
        var met_date1 = $('#erequest-recommended_due_date').val();
        var met_date2 = $('#erequest-equipment_release_date').val();
        var met_date3 = $('#erequest-est_date_completion').val();
        var met_date4 = $('#erequest-certificate_release_date').val();

        if (request_type == '' || lab == '' || customer == '' || discount == '' || purpose == '' || conforme == '' || request_datetime == '' || modeofrelease == '' || due == '' || received == '') {
            alertWarning.alert("<p class='text-danger' style='font-weight:bold;'>Field with * is required!</p>");
            return false;
        } else if (lab == 3 && (met_date1 == '' || met_date2 == '' || met_date3 == '' || met_date4 == '')) {
            alertWarning.alert("<p class='text-danger' style='font-weight:bold;'>Please input Metrology Request Details!</p>");
            return false;
        } else {
            $('.pstc-request-form form').submit();
        }
    });
</script>

<style type="text/css">
/* Absolute Center Spinner */
.img-loader {
    position: fixed;
    z-index: 999;
    /*height: 2em;
    width: 2em;*/
    height: 64px;
    width: 64px;
    overflow: show;
    margin: auto;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background-image: url('/images/img-png-loader64.png');
    background-repeat: no-repeat;
}
/* Transparent Overlay */
.img-loader:before {
    content: '';
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.3);
}
</style>
