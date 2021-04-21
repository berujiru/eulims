<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use common\models\lab\Lab;
use common\models\services\Testcategory;
use common\models\services\Sampletype;
use kartik\widgets\DepDrop;
use kartik\widgets\DatePicker;
use kartik\datetime\DateTimePicker;
use kartik\grid\GridView;
use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\widgets\TypeaheadBasic;
use kartik\widgets\Typeahead;
use common\models\services\Test;


use common\models\lab\Labsampletype;
use common\models\lab\Request;
use common\models\lab\Sampletypetestname;
use common\models\lab\Testnamemethod;
use common\models\lab\Methodreference;
use common\models\lab\Testname;


/* @var $this yii\web\View */
/* @var $model common\models\lab\Packagelist */
/* @var $form yii\widgets\ActiveForm */

//Testcategorylist= ArrayHelper::map(Testcategory::find()->all(),'testcategory_id','category_name');
//$Sampletypelist= ArrayHelper::map(Sampletype::find()->all(),'sample_type_id','sample_type');

$sampletypelist= ArrayHelper::map(Sampletype::find()->all(),'sampletype_id','type');

$js=<<<SCRIPT
$(".kv-row-checkbox").click(function(){
   
   var keys = $('#sample-grid').yiiGridView('getSelectedRows');
   var keylist= keys.join();
   $("#sample_ids").val(keylist);
   if(($("#sample_ids").val())&&($('#package_ids').val()!=0)){
        $('#package_btn').prop('disabled', false);                    
    }else{
        $('#package_btn').prop('disabled', true);
    } 
});    
$(".select-on-check-all").change(function(){

 var keys = $('#sample-grid').yiiGridView('getSelectedRows');
 var keylist= keys.join();
  $("#sample_ids").val(keylist);
  if(($("#sample_ids").val())&&($('#package_ids').val()!=0)){
        $('#package_btn').prop('disabled', false);                    
    }else{
        $('#package_btn').prop('disabled', true);
    } 
 
});




SCRIPT;
$this->registerJs($js);

$this->registerJs("$('#sample-sample_type_id').on('depdrop:afterChange',function(){
    var id = $('#sample-sample_type_id').val();
        $.ajax({
            url: '".Url::toRoute("packagelist/getpackage")."',
            dataType: 'json',
            method: 'GET',
            data: {packagelist_id: id},
            success: function (data, textStatus, jqXHR) {
               
                $('#packagelist-rate').val(data.rate);
                $('#packagelist-rate-disp').val(data.rate);
                $('#packagelist-tests').val(data.tests);
                $('#package_ids').val(data.ids);
                $('.image-loader').removeClass( \"img-loader\" );

                if((data.ids != 0)&&($('#sample_ids').val())){
                    $('#package_btn').prop('disabled', false);                    
                }else{
                    $('#package_btn').prop('disabled', true);
                }
            },
            beforeSend: function (xhr) {
                //alert('Please wait...');
                $('.image-loader').addClass( \"img-loader\" );
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('An error occured!');
                alert('Error in ajax request');
            }
        });
});");

$this->registerJs("$('#sample-sample_type_id').on('change',function(){
    var id = $('#sample-sample_type_id').val();
        $.ajax({
            url: '".Url::toRoute("packagelist/getpackage")."',
            dataType: 'json',
            method: 'GET',
            //data: {id: $(this).val()},
            data: {packagelist_id: id},
            success: function (data, textStatus, jqXHR) {
             
                $('#packagelist-rate').val(data.rate);
                $('#packagelist-rate-disp').val(data.rate);
                $('#packagelist-tests').val(data.tests);
                $('#package_ids').val(data.ids);
                $('.image-loader').removeClass( \"img-loader\" );
            },
            beforeSend: function (xhr) {
                //alert('Please wait...');
                $('.image-loader').addClass( \"img-loader\" );
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('An error occured!');
                alert('Error in ajax request');
            }
        });
});");
?>




<div class="packagelist-form" style="padding-bottom: 10px">

    <?php $form = ActiveForm::begin(); ?>

    <?= GridView::widget([
        'id' => 'sample-grid',
        'dataProvider'=> $sampleDataProvider,
        'pjax'=>true,
        'pjaxSettings' => [
            'options' => [
                'enablePushState' => false,
            ]
        ],
        'containerOptions'=>[
            'style'=>'overflow:auto; height:200px',
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
        ],
        'toolbar' => false,
        'columns' => [
               [
            'class' => '\kartik\grid\CheckboxColumn',
         ],
            'samplename',
            [
                'attribute'=>'description',
                'format' => 'raw',
                'enableSorting' => false,
                'value' => function($data){
                    return ($data->request->lab_id == 2) ? "Sampling Date: <span style='color:#000077;'><b>".$data->sampling_date."</b></span>,&nbsp;".$data->description : $data->description;
                },
                'contentOptions' => ['style' => 'width:70%; white-space: normal;'],
            ],
        ],
    ]); ?>

    <?php
            $requestquery = Request::find()->where(['request_id' => $request_id])->one();
        
            $category= ArrayHelper::map(Testcategory::find()
            ->leftJoin('tbl_lab_sampletype', 'tbl_lab_sampletype.testcategory_id=tbl_testcategory.testcategory_id')
            ->Where(['tbl_lab_sampletype.lab_id'=>$labId])
            ->orderBy(['testcategory_id' => SORT_DESC])->all(),'testcategory_id','category');
        ?>

        <!-- error here-->
        <?= Html::textInput('lab_id', $requestquery->lab_id, ['class' => 'form-control', 'id'=>'lab_id', 'type'=>'hidden'], ['readonly' => true]) ?>
        <!-- error here-->


         <div class="row">
         <div class="col-sm-6">
                <?= $form->field($model,'sample_type_id')->widget(Select2::classname(),[
                            'data' =>ArrayHelper::map(Sampletype::find()->where(['status_id'=>1])->all(),'sampletype_id','type'),
                            'theme' => Select2::THEME_KRAJEE,
                            'options' => ['id'=>'sample-testcategory_id'],
                            'pluginOptions' => ['allowClear' => true,'placeholder' => 'Select Sample Type'],
                    ])
                ?>
        </div>
       
    <div class="col-sm-6">

    
      
    <?= $form->field($model, 'name')->widget(DepDrop::classname(), [
                    'type'=>DepDrop::TYPE_SELECT2,
                    'data'=>$sampletype,
                    'options'=>['id'=>'sample-sample_type_id'],
                    'select2Options'=>['pluginOptions'=>['allowClear'=>true]],
                    'pluginOptions'=>[
                        'depends'=>['sample-testcategory_id'],
                        'placeholder'=>'Select Package',
                        'url'=>Url::to(['/services/packagelist/listsampletype']),
                        'loadingText' => 'Loading Test Names...',
                    ]
                ])
                ?>  

            </div>
        </div>

             <?= $form->field($model, 'tests')->textarea(['rows' => 4, 'readonly' => true]) ?>

             <?= Html::textInput('package_ids', '', ['class' => 'form-control', 'id'=>'package_ids'], ['readonly' => true]) ?>
          
             <?= $form->field($model, 'rstl_id')->hiddenInput(['value'=> 1])->label(false) ?>

             <?= Html::textInput('sample_ids', '', ['class' => 'form-control', 'id'=>'sample_ids',  'type'=>"hidden"], ['readonly' => true]) ?>
             <?= $form->field($model, 'rate')->textInput(['readonly' => true])->label("Rate") ?>         
           
            <div class="row" style="float: right;padding-right: 30px">
            <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary', 'id'=>'package_btn', 'disabled'=>true]) ?>
                <?php if($model->isNewRecord){ ?>
                <?php } ?>
            <?= Html::Button('Cancel', ['class' => 'btn btn-default', 'id' => 'modalCancel', 'data-dismiss' => 'modal']) ?>
       
    <?php ActiveForm::end(); ?>

</div>

