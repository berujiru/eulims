<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

echo $op_id;
?>
<div class="epay-form">
	<?php $form = ActiveForm::begin(); ?>
	<div class="row">
		<div class="col-sm-6">
			 <?= $form->field($epay, 'merchant_code')->textInput(['maxlength' => true]); ?>
		</div>   
    </div>
	<div class="row">
		 <div class="col-sm-12">
				<?= $form->field($epay, 'mrn')->textInput(['maxlength' => true]); ?>
         </div>
	</div>
	<div class="row">
		 <div class="col-sm-12">
				<?= $form->field($epay, 'particulars')->textInput(['maxlength' => true]); ?>
         </div>
	</div>
	<div class="row">
		 <div class="col-sm-6">
				<?= $form->field($epay, 'amount')->textInput(['maxlength' => true]); ?>
         </div>
		 <div class="col-sm-6">
				<?= $form->field($epay, 'epp')->textInput(['maxlength' => true]); ?>
         </div>
	</div>
	<div class="form-group pull-right">
            <?= Html::submitButton('Create Epayment', ['class' => $epay->isNewRecord ? 'btn btn-success' : 'btn btn-primary','id'=>'createEpay']) ?>
            <?php if(Yii::$app->request->isAjax){ ?>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <?php } ?>
    </div>
	 <?php ActiveForm::end(); ?>
</div>