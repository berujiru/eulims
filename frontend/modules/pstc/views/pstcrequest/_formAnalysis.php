<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\widgets\Select2;
use kartik\widgets\DepDrop;
use kartik\widgets\DatePicker;
use kartik\widgets\DateTimePicker;
use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\widgets\TypeaheadBasic;
use kartik\widgets\Typeahead;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model common\models\lab\Sample */
/* @var $form yii\widgets\ActiveForm */

?>



<div class="pstc-sample-form">

    <div class="alert alert-danger" style="background: #ffc0cb !important;margin-top: 1px !important;">
        <a href="#" class="close" data-dismiss="alert">×</a>
        <p class="note" style="color:#d73925"><b>Always check if you have chosen the right Laboratory in this Request:
            </b><br /> Test Methods won't load if it is meant for other Laboratory.</p>
    </div>

    <div class="alert alert-info" style="background: #d4f7e8 !important;margin-top: 1px !important;">
        <a href="#" class="close" data-dismiss="alert">×</a>
        <p class="note" style="color:#265e8d"><b>Note:</b> Please contact the Administrator or the Lab-Manager if the
            Test that you're looking for cannot be found.</p>
    </div>

    <div class="image-loader" style="display: hidden;"></div>

    <?php $form = ActiveForm::begin(['method' => 'post', 'action' => ['/pstc/pstcrequest/createanalysis'],]); ?>

    <div class="row">
        <div class="col-sm-12">
            <?php echo Html::activeLabel($model,'sample'); ?>
            <?= Select2::widget([
                'name' => 'base_samples',
                'data' => ArrayHelper::map($base_sample,'pstc_sample_id','sample_name'),
                'options' => [
                    'multiple' => true,
                    'placeholder' => 'Select sample ...',
                    'id'=>'base-sample',
                    'tags'=>true
                ]
            ]);?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <?php echo Html::label('Sample Type'); ?>
            <?= Select2::widget([
                'name'=>'sampletype',
                'data' => $sampletypes,
                'options' => [
                    'placeholder' => 'Select sample type...',
                    'id'=>'the-sample-type',
                ]
            ]);?>
        </div>
        <div class="col-sm-6" id="testname">
        <?php echo Html::label('Test Name'); ?>
            <?php echo DepDrop::widget([
                'type'=>DepDrop::TYPE_SELECT2,
                'name' => 'testname',
                'data'=>$sampletype,
                'options'=>['id'=>'sample-sample_type_id'],
                'select2Options'=>['pluginOptions'=>['allowClear'=>true]],
                'pluginOptions'=>[
                    'depends'=>['the-sample-type'],
                    'placeholder'=>'Select Test Name',
                    'url'=>Url::to(['/pstc/pstcrequest/listsampletype']),
                    'loadingText' => 'Loading Test Names...',
                    ]
                ])
            ?>
        </div>
    </div>

    
    <div id="methodreference">
    </div>       

    <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
    <input type="hidden" name="pstc_id" value="<?php echo $pstc_id; ?>">

    <div class="form-group" style="padding-bottom: 3px;">
        <div style="float:right;">
            <?= Html::submitButton($model->isNewRecord ? 'Save' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary','id'=>'btn-update','disabled'=>true]) ?>
            <?= Html::button('Close', ['class' => 'btn', 'data-dismiss' => 'modal']) ?>
            <br>
        </div>
    </div>


    <div class="row-fluid" id ="xyz">
    </div>
    <?php ActiveForm::end(); ?>

</div>


</div>

    <script type="text/javascript">
    $('#sample-sample_type_id').on('change',function() {

        $.ajax({
            url: '/pstc/pstcrequest/gettestnamemethod',
            method: "GET",
            dataType: 'html',
            data: { 
                sample: $('#base-sample').val(),
                sampletype_id: $('#the-sample-type').val(),
                testname_id: $(this).val()
            },
            beforeSend: function(xhr) {
               $('.image-loader').addClass("img-loader");
               }
            })
            .done(function( response ) {
                $("#methodreference").html(response); 
                $('.image-loader').removeClass("img-loader");  
            });
    });




    $('#base-sample').on('change',function() {
        var samples = $(this).val();
        if(samples == '')
          $('#analysis_create').prop('disabled',true);
        else
          $('#analysis_create').prop('disabled',false);
    });


</script>