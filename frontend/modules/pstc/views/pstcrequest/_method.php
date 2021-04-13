<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use common\models\lab\Sampletype;
use common\models\lab\Services;
use common\models\lab\Lab;
use common\models\lab\Testname;
use common\models\referral\Methodreference;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\widgets\Select2;
use kartik\widgets\DepDrop;
use kartik\widgets\DatePicker;
use kartik\datetime\DateTimePicker;
use common\components\PstcComponent;
use common\components\ReferralComponent;


$js=<<<SCRIPT

$("#testname-grid").change(function(){
    var key = $("input[name='method_id']:checked").val();
    $("#analysis-method").val(key);
    $("#analysis-references").val(key);
    $("#analysis-fee").val(key);
   // alert(key);

});    

  

SCRIPT;
$this->registerJs($js, $this::POS_READY);

$referralcomp = new ReferralComponent();
?>


<?= GridView::widget([
        'dataProvider' =>  $testnamedataprovider,
        'id'=>'testname-grid',
        'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-products']],
        'pjax'=>true,
        'pjaxSettings' => [
            'options' => [
                'enablePushState' => false,
            ]
        ],
        'containerOptions'=>[
            'style'=>'overflow:auto; height:120px',
        ],
        'floatHeaderOptions' => ['scrollingTop' => true],
        'responsive'=>true,
        'striped'=>true,
        'hover'=>true,
        'bordered' => true,
        'panel' => [
           'heading'=>'<h3 class="panel-title">Methods</h3>',
           'type'=>'primary',
           'before' => '',
           'after'=>false,
        ],
        'toolbar' => false,
        'columns' => [
            [
                'class' =>  '\kartik\grid\RadioColumn',
                'name' => 'method_id',
                'showClear' => true,
                'headerOptions' => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center','style'=>'max-width:20px;'],
                'radioOptions' => function ($model) {
                    return [
                        'value' => $model['testname_method_id'],
                        //'checked' => $model['testname_method_id'],
                    ];
                },
            ],
             [
                        'attribute'=>'method',
                        'header' => 'Agency',
                        'value' => function($data) use($referralcomp){
                            $methodref = $referralcomp->getAgencybyMethodrefOne($data['methodreference_id']);
                            if(!$methodref)
                                return "Error: Cant get the agency details";
                            
                            return $methodref->name;
                        },
                    ],
            [     
                'label' => 'Method',
                'format' => 'raw',
                'contentOptions' => ['style' => 'width: 50%;word-wrap: break-word;white-space:pre-line;'],  
                'value' => function($model) {

                    $pstcComponent = new PstcComponent();
                    $method_query = json_decode($pstcComponent->getMethodreference($model['methodreference_id']),true);
                    if ($method_query){
                        return $method_query['method'];
                    }else{
                        return "";
                    }
                 }                        
            ],
            [     
                'label' => 'Reference',
                'format' => 'raw',
                'contentOptions' => ['style' => 'width: 40%;word-wrap: break-word;white-space:pre-line;'],  
                'value' => function($model) {

                    $pstcComponent = new PstcComponent();
                    $method_query = json_decode($pstcComponent->getMethodreference($model['methodreference_id']),true);
                    
                    if ($method_query){
                        return $method_query['reference'];
                    }else{
                        return "";
                    }
                            
                 }                        
            ],
            [    
                'label' => 'Fee',
                'format' => 'raw',
                'width'=> '150px',
                'contentOptions' => ['style' => 'width: 10%;word-wrap: break-word;white-space:pre-line;'],  
                'value' => function($model) {
                    $pstcComponent = new PstcComponent();
                    $method_query = json_decode($pstcComponent->getMethodreference($model['methodreference_id']),true);
                    if ($method_query){
                        return number_format($method_query['fee'],2);
                    }else{
                        return "";
                    }
                 }                
            ]
       ],
    ]); ?>