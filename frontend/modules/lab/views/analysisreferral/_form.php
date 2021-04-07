<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use kartik\widgets\Select2;
use kartik\widgets\DepDrop;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\View;
use kartik\dialog\Dialog;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model common\models\lab\Analysis */
/* @var $form yii\widgets\ActiveForm */
?>

<?php
    if(count($testname) > 0){
        $data = $testname;
    } else {
        $data = ['' => 'No Testname'] + $testname;
    }

    $checkSample = ($model->sample_id) ? $model->sample_id : null;
?>

<div class="analysisreferral-form">
    <div class="image-loader" style="display: hidden;"></div>
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-lg-12">
        <div class="table-responsive">
        <?php
            //if(Yii::$app->request->get('id') == $model->analysis_id){
            if(Yii::$app->controller->action->id === 'update'){
                $gridColumns = [
                    [
                        'class' => '\kartik\grid\SerialColumn',
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center','style'=>'max-width:20px;'],
                    ],
                    [
                        'class' =>  '\kartik\grid\RadioColumn',
                        'radioOptions' => function ($model) use ($checkSample) {
                            return [
                                'value' => $model['sample_id'],
                                'checked' => $model['sample_id'] == $checkSample,
                            ];
                        },
                        'name' => 'sample_id',
                        'showClear' => true,
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center','style'=>'max-width:20px;'],
                    ],
                    [
                        'attribute'=>'samplename',
                        'enableSorting' => false,
                        'contentOptions' => ['style'=>'max-width:200px;'],
                    ],
                    [
                        'attribute'=>'description',
                        'format' => 'raw',
                        'enableSorting' => false,
                        'value' => function($data){
                            return ($data->request->lab_id == 2) ? "Sampling Date: <span style='color:#000077;'><b>".date("Y-m-d h:i A",strtotime($data->sampling_date))."</b></span>,&nbsp;".$data->description : $data->description;
                        },
                       'contentOptions' => [
                            'style'=>'max-width:200px; overflow: auto; white-space: normal; word-wrap: break-word;'
                        ],
                    ],
                ];

                echo GridView::widget([
                    'id' => 'sample-analysis-grid',
                    'dataProvider'=> $sampleDataProvider,
                    //'pjax'=>false,
                    'pjax'=>true,
                    //'headerRowOptions' => ['class' => 'kartik-sheet-style'],
                    //'filterRowOptions' => ['class' => 'kartik-sheet-style'],
                    'pjaxSettings' => [
                        'options' => [
                            'enablePushState' => false,
                        ]
                    ],
                    'containerOptions'=>[
                        'style'=>'overflow:auto; height:250px',
                    ],
                    'floatHeaderOptions' => ['scrollingTop' => true],
                    'responsive'=>true,
                    'striped'=>true,
                    'hover'=>true,
                    'bordered' => true,
                    'panel' => [
                       'heading'=>'<h3 class="panel-title">Samples</h3>',
                       'type'=>'primary',
                       'before' => '',
                       'after'=>false,
                       //'footer'=>false,
                    ],
                    'columns' => $gridColumns,
                    'toolbar' => false,
                ]);
            } else {
            $gridColumns = [
                [
                    'class' => '\kartik\grid\SerialColumn',
                    'headerOptions' => ['class' => 'text-center'],
                    'contentOptions' => ['class' => 'text-center','style'=>'max-width:10px;'],
                ],
                [
                    //'class' => '\kartik\grid\CheckboxColumn',
                    'class' => '\yii\grid\CheckboxColumn',
                    'headerOptions' => ['class' => 'text-center'],
                    'contentOptions' => ['class' => 'text-center','style'=>'max-width:10px;'],
                    'name' => 'sample_ids',
                    //'checked' => $model['sample_id'] == 125,
                    /*'checkboxOptions' => function($model, $key, $index, $column) use ($checkSample) {
                        //$bool = in_array($model->id_machine, $machines); 
                        return ['checked' => $model['sample_id'] == $check];
                    },*/
                    //'multiple' => false,
                ],
                [
                    'attribute'=>'samplename',
                    'enableSorting' => false,
                    'contentOptions' => ['style'=>'max-width:200px;'],
                ],
                [
                    'attribute'=>'description',
                    'format' => 'raw',
                    'enableSorting' => false,
                    'value' => function($data){
                        return ($data->request->lab_id == 2) ? "Sampling Date: <span style='color:#000077;'><b>".date("Y-m-d h:i A",strtotime($data->sampling_date))."</b></span>,&nbsp;".$data->description : $data->description;
                    },
                   'contentOptions' => [
                        'style'=>'max-width:200px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
            ];

            echo GridView::widget([
                'id' => 'sample-analysis-grid',
                'dataProvider'=> $sampleDataProvider,
                'pjax'=>true,                
                'pjaxSettings' => [
                    'options' => [
                        'enablePushState' => false,
                    ]
                ],
                'containerOptions'=>[
                    'style'=>'overflow:auto; height:250px',
                ],
                'floatHeaderOptions' => ['scrollingTop' => true],
                'responsive'=>true,
                'striped'=>true,
                'hover'=>true,
                'bordered' => true,
                'panel' => [
                   'heading'=>'<h3 class="panel-title">Samples</h3>',
                   'type'=>'primary',
                   'before' => '',
                   'after'=>false,
                   'footer'=>false,
                ],
                'columns' => $gridColumns,
                'toolbar' => false,
            ]);
        }
        ?>
        </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8">
        <?php
            $options = [
                'language' => 'en-US',
                'width' => '100%',
                'theme' => Select2::THEME_KRAJEE,
                'placeholder' => 'Select Testname',
                'allowClear' => true,
            ];

            echo $form->field($model,'test_id')->widget(Select2::classname(),[
                'data' => $data,
                'theme' => Select2::THEME_KRAJEE,
                //'theme' => Select2::THEME_BOOTSTRAP,
                'options' => $options,
                'pluginOptions' => [
                    'allowClear' => true,
                ],
                'pluginEvents' => [
                    "change" => "function() {
                        var testId = this.value;
                        var analysisId = '".$model->analysis_id."';
                        var labId = '".$labId."';
                        var key_id = $('#sample-analysis-grid').yiiGridView('getSelectedRows');
                        $.ajax({
                            url: '".Url::toRoute("analysisreferral/gettestnamemethod")."',
                            //dataType: 'json',
                            method: 'GET',
                            data: {test_id:testId,analysis_id:analysisId,lab_id:labId,sample_id:key_id},
                            //data: $(this).serialize(),
                            success: function (data, textStatus, jqXHR) {
                                $('.image-loader').removeClass( \"img-loader\" );
                                $('#methodreference').html(data);
                            },
                            beforeSend: function (xhr) {
                                //alert('Please wait...');
                                $('.image-loader').addClass( \"img-loader\" );
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>Error Encountered!</p>\");
                            }
                        });
                    }",
                ],
            ])->label('Test Name');
        ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div id="methodreference">
                <?php
                    echo $this->render('_methodreference', [ 'methodProvider' => $methodrefDataProvider,'model'=>$model]);
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="form-group" style="padding-bottom: 3px;">
        <div style="float:right;">
            <?= Html::button($model->isNewRecord ? 'Add' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary','id'=>'add_analysis']) ?>
            <!-- <?= Html::submitButton($model->isNewRecord ? 'Save' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary','id'=>'add_analysis','onclick'=>'verifyCheck()']) ?> -->
            <?= Html::button('Close', ['class' => 'btn', 'onclick'=>'closeDialog()']) ?>
            <br>
        </div>
    </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
<?php
// Warning alert for no selected sample or method
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
    function closeDialog(){
        $(".modal").modal('hide'); 
    };
</script>
<?php
if(Yii::$app->controller->action->id === 'update'){
    $this->registerJs("
        $('#add_analysis').on('click',function(){
            var radioSample = $(\"input[name='sample_id']:checked\").val();
            var radioMethod = $(\"input[name='methodref_id']:checked\").val();
            
            if (!$(\"input[name='sample_id']\").is(':checked') || radioSample == '') {
                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No sample selected!</p>\");
                return false;
            }
            else if ($('input[type=radio][name=methodref_id]', '#method-reference-grid').length < 1) {
                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No Method selected!</p>\");
                return false;
            }
            else if (!$(\"input[name='methodref_id']\").is(':checked') || radioMethod == '') {
                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No Method selected!</p>\");
                return false;
            }
            else {
                $('.image-loader').addClass('img-loader');
                $('.analysisreferral-form form').submit();
                $('.image-loader').removeClass('img-loader');
            }
        });
    ");

    $this->registerJs("
        $('#sample-analysis-grid').on('change',function(){
            var radioSample = $(\"input[name='sample_id']:checked\").val();
            var select = $('#analysisextend-test_id');
            select.find('option').remove().end();
            if(radioSample > 0) {
                $.ajax({
                    url: '".Url::toRoute("analysisreferral/getreferraltestname?sample_id='+radioSample+'&labId='+".$labId)."',
                    success: function (data) {
                        var select2options = ".Json::encode($options).";
                        select2options.data = data.data;
                        select.select2(select2options);
                        select.val(data.selected).trigger('change');
                        $('.image-loader').removeClass(\"img-loader\");
                    },
                    beforeSend: function (xhr) {
                        //alert('Please wait...');
                        $('.image-loader').addClass(\"img-loader\");
                    }
                });
            } else {
                //alert('No sample selected!');
                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No sample selected!</p>\");
                select.val('').trigger('change');
            }
        });
    ");

} else {
    $this->registerJs("
        $('#add_analysis').on('click',function(){
            var key_sample = $('#sample-analysis-grid').yiiGridView('getSelectedRows');
            var radioMethod = $(\"input[name='methodref_id']:checked\").val();
            
            if(key_sample.length < 1) {
                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No sample selected!</p>\");
                return false;
            }
            else if ($('input[type=radio][name=methodref_id]', '#method-reference-grid').length < 1) {
                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No Method selected!</p>\");
                return false;
            }
            else if(!$(\"input[name='methodref_id']\").is(':checked') || radioMethod == '') {
                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No Method selected!</p>\");
                return false;
            }
            else {
                $('.image-loader').addClass('img-loader');
                $('.analysisreferral-form form').submit();
                $('.image-loader').removeClass('img-loader');
            }
        });
    ");

    $this->registerJs("
        $('#sample-analysis-grid').on('change',function(){
            var key_id = $('#sample-analysis-grid').yiiGridView('getSelectedRows');
            var select = $('#analysisextend-test_id');
            select.find('option').remove().end();
            if(key_id.length > 0) {
                $.ajax({
                    url: '".Url::toRoute("analysisreferral/getreferraltestname?sample_id='+key_id+'&labId=".$labId)."',
                    success: function (data) {
                        var select2options = ".Json::encode($options).";
                        select2options.data = data.data;
                        select.select2(select2options);
                        select.val(data.selected).trigger('change');
                        $('.image-loader').removeClass(\"img-loader\");
                    },
                    beforeSend: function (xhr) {
                        //alert('Please wait...');
                        $('.image-loader').addClass(\"img-loader\");
                    }
                });
            } else {
                //alert('No sample selected!');
                alertWarning.alert(\"<p class='text-danger' style='font-weight:bold;'>No sample selected!</p>\");
                select.val('').trigger('change');
            }
        });
    ");
}
?>
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