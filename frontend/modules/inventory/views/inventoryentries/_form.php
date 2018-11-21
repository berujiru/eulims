<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\components\Functions;

use kartik\date\DatePicker;
use kartik\money\MaskMoney;
use kartik\number\NumberControl;
/* @var $this yii\web\View */
/* @var $model common\models\inventory\InventoryEntries */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="inventory-entries-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="form-group">

    <div class="row">
        <div class="col-md-6">
            <?php
            $func=new Functions();
            echo $func->GetProductList($form,$model,false,"Product");
            ?> 
            
            <?= $form->field($model, 'po_number')->textInput(['maxlength' => true]) ?>
            
             <?php
                 echo "<b>Manufacturing Date</b>";
                echo DatePicker::widget([
                    'model' => $model,
                    'attribute' => 'manufacturing_date',
                    'readonly' => true,
                    'options' => ['placeholder' => 'Enter Date'],
                    'value' => function($model) {
                        return date("m/d/Y", $model->manufacturing_date);
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
            <br>

            <?php
                echo "<b>Expiration Date</b>";
                echo DatePicker::widget([
                    'model' => $model,
                    'attribute' => 'expiration_date',
                    'readonly' => true,
                    'options' => ['placeholder' => 'Enter Date'],
                    'value' => function($model) {
                        return date("m/d/Y", $model->expiration_date);
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
        <div class="col-md-6">
             <?php
                echo $func->GetSupplierList($form,$model,false,"Supplier");
            ?> 
            
            <?= $form->field($model, 'quantity')->textInput(['type'=>'number','onchange'=>'getTotal();']) ?>

            <?php
              echo $form->field($model, 'amount')->widget(NumberControl::classname(), [
                   'options'=>[
                       'style'=>'text-align: right'
                   ],
                   'maskedInputOptions' => [
                      'prefix' => '₱ ',
                      'allowNegative' => false,
                   ], 
                  'pluginEvents'=>[
                        'change'=>"function(){
                        getTotal();
                        }"
                   ],
                  ])->label('Amount');
             ?>

        </div>
       
    </div>
    <div class="row">
         <div class="col-md-6">
         <?php
              echo $form->field($model, 'total_amount')->widget(MaskMoney::classname(), [
                  'readonly'=>true,
                   'options'=>[
                       'style'=>'text-align: right'
                   ],
                   'pluginOptions' => [
                      'prefix' => '₱ ',
                      'allowNegative' => false,
                   ]
                 
                  ])->label('Total Amount');
             ?>
         </div>
    </div>
    
    
   <div class="form-group pull-right">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Cancel'), Yii::$app->request->referrer , ['class'=> 'btn btn-danger']) ?>
    </div>


    <?php ActiveForm::end(); ?>

</div>
    <script type="text/javascript">
        function getTotal(){
             qty= $('#inventoryentries-quantity').val();
             amount=$('#inventoryentries-amount').val();
             
             total= qty * amount;
            
            $("#inventoryentries-total_amount-disp").val(total);
            $("#inventoryentries-total_amount").val(total);
            $("#inventoryentries-total_amount-disp").maskMoney('mask', total);
        }
    </script>