<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\lab\Businessnature;
use common\models\lab\Industrytype;
use common\models\lab\Customertype;
use common\models\lab\Classification;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use common\models\address\CityMunicipality;
use common\models\address\Province;
use common\models\address\Barangay;
use common\models\address\Region;
use yii\helpers\Url;
use kartik\widgets\DepDrop;
use yii\jui\Accordion;
/* @var $model common\models\lab\Customer */
/* @var $form yii\widgets\ActiveForm */ 
?>
<div class="customer-form"  style="padding-bottom: 10px">

    <?php $form = ActiveForm::begin(); ?>    
        <div class="row">
            <div class="col-md-6">
            <?= $form->field($model, 'customer_name')->textInput(['maxlength' => true]) ?>
            </div>

            <div class="col-md-6">
            <?= $form->field($model, 'head')->textInput(['maxlength' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
            <?= $form->field($model, 'tel')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-md-4">
            <?= $form->field($model, 'fax')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-md-4">
            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
            <?php 
            echo $form->field($model, 'business_nature_id')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Businessnature::find()->all(), 'business_nature_id', 'nature'),
                'language' => 'en',
                'options' => ['placeholder' => 'Select  ...'],
                'pluginOptions' => [
                  'allowClear' => true
                ],
            ]);
            ?>
            </div>
            <div class="col-md-3">
             <?php 
            echo $form->field($model, 'industrytype_id')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Industrytype::find()->all(), 'industrytype_id', 'industry'),
                'language' => 'en',
                'options' => ['placeholder' => 'Select  ...'],
                'pluginOptions' => [
                  'allowClear' => true
                ],
            ]);
            ?>
            </div>
            <div class="col-md-3">
            <?php 
            echo $form->field($model, 'customer_type_id')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Customertype::find()->all(), 'customertype_id', 'type'),
                'language' => 'en',
                'options' => ['placeholder' => 'Select  ...'],
                'pluginOptions' => [
                  'allowClear' => true
                ],
            ]);
            ?>
            </div>
            <div class="col-md-3">
            <?php 
            echo $form->field($model, 'classification_id')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Classification::find()->all(), 'classification_id', 'classification'),
                'language' => 'en',
                'options' => ['placeholder' => 'Select  ...'],
                'pluginOptions' => [
                  'allowClear' => true
                ],
            ]);
            ?>
            </div>
        </div>
    <div class="form-group">
        <?= $form->field($model, 'address')->textarea(['maxlength' => true]) ?>
    </div>

    <div class="form-group pull-right">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success mybtn' : 'btn btn-primary mybtn'/*,'disabled'=>$model->isNewRecord ? true : false*/]) ?>
        <?php if(Yii::$app->request->isAjax){ ?>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <?php } ?>
        
    </div>
    
    <?php ActiveForm::end(); ?>
</div>