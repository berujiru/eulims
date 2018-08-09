<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\finance\Collectiontype;
use common\models\finance\Paymentmode;
use common\models\lab\Customer;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use kartik\widgets\DatePicker;
use common\components\Functions;
use kartik\widgets\DepDrop;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $model common\models\finance\Op */
/* @var $form yii\widgets\ActiveForm */
$paymentlist='';
$disable='';
 if($status == 0){
 $disable=true;
 }
 else{
 $disable=false;    
 }

?>
<?php
    if(!$model->isNewRecord){
    ?>
    <script type="text/javascript">
       $(document).ready(function(){
           $(".select-on-check-all").click();
        });
    </script>
    <?php
    
    }
?>
<div class="orderofpayment-form" style="margin:0important;padding:0px!important;padding-bottom: 10px!important;">

    <?php $form = ActiveForm::begin(); ?>
    <div class="alert alert-info" style="background: #d9edf7 !important;margin-top: 1px !important;">
     <a href="#" class="close" data-dismiss="alert" >×</a>
    <p class="note" style="color:#265e8d">Fields with <i class="fa fa-asterisk text-danger"></i> are required.</p>
     </div>
   
    <div style="padding:0px!important;">
        <div class="row">
            <div class="col-sm-6">
           <?php 

                echo $form->field($model, 'collectiontype_id')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Collectiontype::find()->all(), 'collectiontype_id', 'natureofcollection'),
                'theme' => Select2::THEME_BOOTSTRAP,
                'options' => ['placeholder' => 'Select Collection Type ...'],
                'pluginOptions' => [
                  'allowClear' => true
                ],
                ]);
            ?>
            </div>   
            <div class="col-sm-6">
             <?php
             echo $form->field($model, 'order_date')->widget(DatePicker::classname(), [
             'options' => ['placeholder' => 'Select Date ...',
             'autocomplete'=>'off'],
             'type' => DatePicker::TYPE_COMPONENT_APPEND,
                 'pluginOptions' => [
                     'format' => 'yyyy-mm-dd',
                     'todayHighlight' => true,
                     'autoclose'=>true,
                     'startDate' => date('Y-m-d'),
                     'endDate' => date('Y-m-d')
                     
                 ]
             ]);
             ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
              
             <?php
            $disabled=false;
            if($status == 1){
            $disabled=true;
            }
            
            $func=new Functions();
            echo $func->GetCustomerList($form,$model,$disabled,"Customer");
            ?>    
           
            </div>
             <div class="col-sm-6">
                <?php
                if($status == 0){
                    echo $form->field($model, 'payment_mode_id')->widget(DepDrop::classname(), [
                        'type'=>DepDrop::TYPE_SELECT2,
                        'options' => ['placeholder' => 'Select Payment Mode ...'],
                        'select2Options'=>['pluginOptions'=>['allowClear'=>true]],
                        'pluginOptions'=>[
                            'depends'=>['op-customer_id'],
                            'url'=>Url::to(['/finance/op/listpaymentmode?customerid='.$model->customer_id]),

                        ]
                    ]);
                }
                else{
                      echo $form->field($model, 'payment_mode_id')->widget(Select2::classname(), [
                        'data' => ArrayHelper::map(Paymentmode::find()->all(), 'payment_mode_id', 'payment_mode'),
                        'theme' => Select2::THEME_BOOTSTRAP,
                        'options' => ['placeholder' => 'Select Payment mode ...'],
                        'pluginOptions' => [
                          'allowClear' => true
                        ]
                        ])->label('Payment Mode');
                }
                ?>
            </div>
        </div>
        <div class="row">
         <div class="col-md-12">
            <?= $form->field($model, 'subsidiary_customer_ids')->widget(Select2::classname(), [
               'data' => ArrayHelper::map(Customer::find()->where(['not',['customer_id'=>$model->customer_id]])->all(),'customer_id','customer_name'),
               //'initValueText'=>$model->modeofrelease_ids,
               'language' => 'en',
                'disabled'=>true,
                'options' => [
                   'placeholder' => 'Select Subsidiary Customer(s)...',
                   'multiple' => true,
                   //'disabled'=>$disabled
               ],
               'pluginEvents' => [
                   "change" => "function() { 
                      // $('#modeofrelease_ids').val($(this).val());
                   }
                   ",
               ]
           ])->label('Subsidiary Customer(s) * (Optional)'); ?> 
         </div>
        </div> 
        <div class="row">
            <div class="col-lg-12">  
                 <div id="prog" style="position:relative;display:none;">
                    <img style="display:block; margin:0 auto;" src="<?php echo  $GLOBALS['frontend_base_uri']; ?>/images/ajax-loader.gif">
                     </div>
                

                <div id="requests" style="padding:0px!important;">    	
                   <?php
                   if (!$model->isNewRecord){
                      // $model->RequestIds=1;
                      // echo$form->field($model, 'requestid_update')->textInput()->label(false);
                       echo $this->renderAjax('_paymentitems', ['dataProvider'=>$dataProvider,'model'=>$request_model]);
                      
                   }
                    
                   ?>
                </div> 

            </div>
        </div> 
		 <?php echo $form->field($model, 'RequestIds')->hiddenInput()->label(false) ?>
        <div class="row">
            <div class="col-lg-12"> 
                <?= $form->field($model, 'purpose')->textarea(['maxlength' => true,'disabled' => $disable]); ?>
            </div>
        </div>

        <input type="text" id="wallet" name="wallet" hidden>
        
        <div class="form-group pull-right">
            <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
                'id'=>'createOP','disabled'=>$disable]) ?>
            <?php if(Yii::$app->request->isAjax){ ?>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <?php } ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>
<style>
    .modal-body{
        padding-top: 0px!important;
    }
</style>

<script type="text/javascript">
    $('#op-customer_id').on('change',function(e) {
       
       $(this).select2('close');
       e.preventDefault();
       $('#op-subsidiary_customer_ids').val('').trigger('change');
        $('#prog').show();
        $('#requests').hide();
        var cid=$(this).val();
        
        if (cid == ""){
            cid=-1;
            $('#op-subsidiary_customer_ids').prop('disabled', true);
            $('#op-subsidiary_customer_ids').select2('close');
        }
        else{
             $('#op-subsidiary_customer_ids').prop('disabled', false);
        }
         jQuery.ajax( {
            type: 'POST',
            url: '/finance/op/check-customer-wallet?customerid='+cid,
            dataType: 'html',
            success: function ( response ) {
               $('#wallet').val(response);
            },
            error: function ( xhr, ajaxOptions, thrownError ) {
                alert( thrownError );
            }
        });
        jQuery.ajax( {
            type: 'POST',
            //data: {
            //    customer_id:customer_id,
           // },
            url: '/finance/op/getlistrequest?id='+cid,
            dataType: 'html',
            success: function ( response ) {

               setTimeout(function(){
               $('#prog').hide();
               $('#requests').show();
               $('#requests').html(response);
                   }, 0);


            },
            error: function ( xhr, ajaxOptions, thrownError ) {
                alert( thrownError );
            }
        });
        
       //alert(paymentmode);
        $(this).select2('open');
      //  $(this).one('select-focus',select2Focus);
      $(this).attr('tabIndex',1);
       checkpayment_mode();
    });
    
    $('#op-payment_mode_id').on('change',function(e) {
        e.preventDefault();
        checkpayment_mode();
    });
    function checkpayment_mode(){
        var payment_mode=$('#op-payment_mode_id').val();
        if(payment_mode == 4){
            $('#op-purpose').prop('disabled', true);
            $('#createOP').prop('disabled', true);
        }
        else{
            $('#op-purpose').prop('disabled', false);
            $('#createOP').prop('disabled', false);
        }    
    }
    
     $('#op-subsidiary_customer_ids').on('change',function(e) {
         $(this).select2('close');
        e.preventDefault();
        var customer_id= $('#op-customer_id').val();
        var sdc=$(this).val();
        var ids='';
        if (customer_id == ""){
            ids=-1;
        }else{
            if (sdc == ""){
                ids=customer_id;
            }
            else{
                ids=sdc+','+customer_id;
            }
        }
       
        //alert(ids);
        jQuery.ajax( {
            type: 'POST',
            url: '/finance/op/getlistrequest?id='+ids,
            dataType: 'html',
            success: function ( response ) {
               setTimeout(function(){
               $('#requests').html(response);
                   }, 0);


            },
            error: function ( xhr, ajaxOptions, thrownError ) {
                alert( thrownError );
            }
        });     
        
        $(this).select2('open');
      //  $(this).one('select-focus',select2Focus);
      $(this).attr('tabIndex',1);
    });
</script>
