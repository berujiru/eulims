<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
// var_dump($epay); exit;

if($epay->epp){
?>
<div class="alert alert-success" style="background: #d4f7e8 !important;margin-top: 1px !important;">
	<h3 class="note" style="color:#265e8d">Receipt: <b><?=$epay->epp?></b><br/><br/>Status: PAID</h3>
</div>
<?php	
}
elseif($epay->op_id){
	?>
	<div class="alert alert-danger" style="background: #ffc0cb !important;margin-top: 1px !important;">
		<h3 class="note" style="color:#d73925"><b>NOT PAID YET</b></h3>
	</div>
	<?php
}
else{
?>
<div class="alert alert-info" style="background: #d4f7e8 !important;margin-top: 1px !important;">
	<h3 class="note" style="color:#265e8d"><b>Note: Please review the information below before sending for epayment transaction.</b></h3>
</div>
<div class="epay-form">
	<?php $form = ActiveForm::begin(); ?>
	<div class="row">
		<div class="col-sm-6">
			 <?= $form->field($epay, 'merchant_code')->textInput(['maxlength' => true, 'readOnly'=>true]); ?>
		</div>   
    </div>
	<div class="row">
		 <div class="col-sm-12">
				<?= $form->field($epay, 'mrn')->textInput(['maxlength' => true, 'readOnly'=>true]); ?>
         </div>
	</div>
	<div class="row">
		 <div class="col-sm-12">
				<?= $form->field($epay, 'particulars')->textInput(['maxlength' => true, 'readOnly'=>true]); ?>
         </div>
	</div>
	<div class="row">
		 <div class="col-sm-12">
				<?= $form->field($epay, 'amount')->textInput(['maxlength' => true, 'readOnly'=>true]); ?>
         </div>
	</div>
	<div class="form-group pull-right">
            <?= Html::submitButton('Send for Epayment', ['class' => $epay->isNewRecord ? 'btn btn-success' : 'btn btn-primary','id'=>'createEpay']) ?>
            <?php if(Yii::$app->request->isAjax){ ?>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <?php } ?>
    </div>
	 <?php ActiveForm::end(); ?>
</div>
<?php
}
?>