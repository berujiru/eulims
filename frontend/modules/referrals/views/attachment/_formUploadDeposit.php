<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\referral\Attachment */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="attachment-deposit-form">

    <?php /* $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'filename')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'filetype')->textInput() ?>

    <?= $form->field($model, 'referral_id')->textInput() ?>

    <?= $form->field($model, 'upload_date')->textInput() ?>

    <?= $form->field($model, 'uploadedby_name')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); */ ?>
	
	<?php $form = ActiveForm::begin(['options'=>['enctype'=>'multipart/form-data']]); ?>

    <!-- <? /*= $form->field($model, 'filename[]')->fileInput(['multiple'=>true])*/ ?> -->
    <?= $form->field($model, 'filename')->fileInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Upload', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
