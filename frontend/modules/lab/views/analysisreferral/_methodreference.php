<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use kartik\widgets\Select2;
use kartik\widgets\DepDrop;
use yii\helpers\Url;
use yii\helpers\Json;
use common\components\ReferralComponent;

/* @var $this yii\web\View */
/* @var $model common\models\lab\Analysis */
/* @var $form yii\widgets\ActiveForm */
?>

<?php
    $checkMethod = ($model->methodref_id) ? $model->methodref_id : null;
    $referralcomp = new ReferralComponent();
?>

<div class="analysismethodreference-form">
    <div class="row">
        <div class="col-lg-12">
            <div class="table-responsive">
            <?php
                $gridColumns = [
                    [
                        'class' => '\kartik\grid\SerialColumn',
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center','style'=>'max-width:20px;'],
                    ],
                    [
                        'class' =>  '\kartik\grid\RadioColumn',
                        'radioOptions' => function ($model) use ($checkMethod) {
                            return [
                                'value' => $model['methodreference_id'],
                                'checked' => $model['methodreference_id'] == $checkMethod,
                            ];
                        },
                        'name' => 'methodref_id',
                        'showClear' => true,
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center','style'=>'max-width:20px;'],
                    ],
                    [
                        'attribute'=>'method',
                        'header' => 'Agency',
                        'value' => function($data) use($referralcomp){
                            $methodref = $referralcomp->getAgencybyMethodrefOne($data['methodreference_id']);
                            if(!$methodref)
                                return "Sync ID-".$data['methodreference']['sync_id'].": Cant get the agency details";
                            
                            return $methodref->code;
                        },
                    ],
                    [
                        'attribute'=>'method',
                        'header' => 'Method',
                        'enableSorting' => false,
                        'value' => function($data){
                            return $data['methodreference']['method'];
                        },
                        'contentOptions' => [
                            'style'=>'max-width:200px; overflow: auto; white-space: normal; word-wrap: break-word;'
                        ],
                        'headerOptions' => ['class' => 'text-center'],
                    ],
                    [
                        //'attribute'=>'testname_method_id',
                        'header' => 'Reference',
                        'enableSorting' => false,
                        'value' => function($data){
                            return $data['methodreference']['reference'];
                        },
                        'contentOptions' => [
                            'style'=>'max-width:200px; overflow: auto; white-space: normal; word-wrap: break-word;'
                        ],
                        'headerOptions' => ['class' => 'text-center'],
                    ],
                    [
                        //'attribute'=>'testname_method_id',
                        'header' => 'Fee',
                        'enableSorting' => false,
                        'value' => function($data){
                            return number_format($data['methodreference']['fee'],2);
                        },
                        'contentOptions' => [
                            'style'=>'text-align:right;max-width:45px;'
                        ],
                        'headerOptions' => ['class' => 'text-center'],
                    ],
                ];

                echo GridView::widget([
                    'id' => 'method-reference-grid',
                    'dataProvider'=> $methodProvider,
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
                       'heading'=>'<h3 class="panel-title">Method Reference</h3>',
                       'type'=>'primary',
                       'before' => '',
                       'after'=>false,
                    ],
                    'columns' => $gridColumns,
                    'toolbar' => false,
                ]);
            ?>
            </div>
        </div>
    </div>
</div>