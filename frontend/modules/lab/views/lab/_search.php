<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\lab\LabSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="lab-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'lab_id') ?>

    <?= $form->field($model, 'labname') ?>

    <?= $form->field($model, 'labcode') ?>

    <?= $form->field($model, 'labcount') ?>

    <?= $form->field($model, 'nextrequestcode') ?>

    <?php // echo $form->field($model, 'active') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
