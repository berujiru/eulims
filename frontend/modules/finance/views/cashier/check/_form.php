<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use kartik\widgets\DatePicker;
use yii\helpers\Url;
use common\components\Functions;
/* @var $this yii\web\View */
/* @var $model common\models\finance\Check */
/* @var $form yii\widgets\ActiveForm */
$url ='/finance/cashier/add-check';
                     
?>

<div class="receipt-form" style="margin:0important;padding:0px!important;padding-bottom: 10px!important;">

    <?php $form = ActiveForm::begin(); ?>
    <div class="alert alert-info" style="background: #d9edf7 !important;margin-top: 1px !important;">
     <a href="#" class="close" data-dismiss="alert" >×</a>
    <p class="note" style="color:#265e8d">Fields with <i class="fa fa-asterisk text-danger"></i> are required.</p>
     
    </div>
   
    <div style="padding:0px!important;">
        <div class="row">
            <div class="col-sm-6">
             <?php echo $form->field($model, 'bank')->textInput() ?>
            </div>   
            <div class="col-sm-6">
             <?php echo $form->field($model, 'checknumber')->textInput() ?>
            </div> 
        </div>
        <div class="row">
          
            <div class="col-sm-6">
            <?php
             echo $form->field($model, 'checkdate')->widget(DatePicker::classname(), [
             'options' => ['placeholder' => 'Select Date ...',
             'autocomplete'=>'off'],
             'type' => DatePicker::TYPE_COMPONENT_APPEND,
                 'pluginOptions' => [
                     'format' => 'yyyy-mm-dd',
                     'todayHighlight' => true,
                     'autoclose'=>true,   
                 ]
             ]);
             ?>
            </div>
            <div class="col-sm-6">
                <?php echo $form->field($model, 'amount')->textInput() ?>
            </div>  
        </div>
      
        <div class="form-group pull-right">
            <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
                'id'=>'createCheck',
                
                ]) ?>
            <?php if(Yii::$app->request->isAjax){ ?>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <?php } ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
 <?php
    // This section will allow to popup a notification
    $session = Yii::$app->session;
    if ($session->isActive) {
        $session->open();
        if (isset($session['deletepopup'])) {
            $func->CrudAlert("Deleted Successfully","WARNING");
            unset($session['deletepopup']);
            $session->close();
        }
        if (isset($session['updatepopup'])) {
            $func->CrudAlert("Updated Successfully");
            unset($session['updatepopup']);
            $session->close();
        }
        if (isset($session['savepopup'])) {
            $func->CrudAlert("Successfully Saved","SUCCESS",true);
            unset($session['savepopup']);
            $session->close();
        }
        if (isset($session['errorpopup'])) {
            $func->CrudAlert("Transaction Error","ERROR",true);
            unset($session['errorpopup']);
            $session->close();
        }
        if (isset($session['checkpopup'])) {
            $func->CrudAlert("Insufficient Wallet Balance","INFO",true,false,false);
            unset($session['checkpopup']);
            $session->close();
        }
    }
?>
</div>
<style>
    .modal-body{
        padding-top: 0px!important;
    }
</style>
<!--<script type="text/javascript">
    $('#createcheck').on('click',function(e) {
        amount=$('#check-amount').val();
        collection=<?php //echo $total_collection ?>;
    
        total= amount;
        if(total > collection)(
            var hi= confirm("Do you really want me to say hi?");
            if (hi== true){
                alert("hi");
            }else{
                alert("Meany!!!");
            }
        }
//        if(total > collection)(
//            var x =confirm('Excess amount will be credited to Customer Wallet.Proceed?');
//            if (x== true){
//                alert("hi");
//            }else{
//                alert("Meany!!!");
//            }
//        }
       
        
    });
</script>-->