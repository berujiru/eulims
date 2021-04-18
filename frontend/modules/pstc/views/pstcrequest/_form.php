<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model common\models\referral\Pstcrequest */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="pstcrequest-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-12">
            <div class="input-group">
              <?= $form->field($model, 'customer_id')->widget(Select2::classname(), [
                'data' => $customers,
                //'type'=>DepDrop::TYPE_SELECT2,
                'language' => 'en',
                //'options' => ['placeholder' => 'Select Customer'],
                'theme' => Select2::THEME_KRAJEE,
                'pluginOptions'=>[
                   'placeholder'=>'Select Customer',
                   'LoadingText'=>'Loading...',
                   'allowClear' => true
                ],
            ])->label('Customer'); ?>
             <span class="input-group-btn" style="padding-top: 25.5px">
                <button onclick="LoadModal('Create New Cusotmer', '/pstc/pstcrequest/createcustomer');"class="btn btn-default" type="button"><i class="fa fa-plus"></i></button>
            </span> 
            </div>
        </div>
        <div class="col-md-12">
            <?= $form->field($model, 'submitted_by')->textInput(['placeholder' => 'Enter name ...']) ?>
        </div>
        <div class="col-md-12">
            <?= $form->field($model, 'received_by')->textInput(['readonly'=>true,'placeholder' => 'Enter name ...']) ?>
        </div>
    </div>

    <div class="form-group" style="padding-bottom: 3px;">
        <div style="float:right;">
            <?= Html::submitButton($model->isNewRecord ? 'Save' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            <?= Html::button('Close', ['class' => 'btn btn-default', 'data-dismiss' => 'modal']) ?>
            <br>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
