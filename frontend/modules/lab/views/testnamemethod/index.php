<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use common\models\lab\Methodreference;
use common\models\lab\Testname;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\lab\Lab;
use common\models\lab\Sampletype;

/* @var $this yii\web\View */
/* @var $searchModel common\models\lab\TestnamemethodSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$methodlist= ArrayHelper::map(Methodreference::find()->all(),'method_reference_id','method');
$testnamelist= ArrayHelper::map(Testname::find()->all(),'testname_id','testName');

$this->title = 'Test Name Methods';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="testnamemethod-index">
<div class="info-box">
<span class="info-box-icon bg-aqua"><i class="fa fa-question-circle"></i></span>
<a href="#" class="close" data-dismiss="alert" >×</a>
<div class="info-box-content bg-aqua">
    <span class="info-box-number">Manuals</span>
    <span class="info-box-text"><?= Html::a('How to create a new test service</b>', 'https://tinyurl.com/wawdry67',['class'=>'btn-info','target'=>"_blank"]); ?></span>
    <span class="info-box-text"><?= Html::a('How to enroll test services to referral', 'https://tinyurl.com/rht3rp5a',['class'=>'btn-info','target'=>"_blank"]); ?></span>
    <span class="info-box-text">.</span>
</div>
<!-- /.info-box-content -->

</div>

<?php $this->registerJsFile("/js/services/services.js"); ?>

<?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pjax' => true,
        'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-products']],
        'panel' => [
                'type' => GridView::TYPE_PRIMARY,
                'heading' => '<span class="glyphicon glyphicon-book"></span>  ' . Html::encode($this->title),
                'before'=>"<button type='button' onclick='openModal(\"/lab/testnamemethod/create\",\"Create New TestName Method\")' class=\"btn btn-success\"><i class=\"fa fa-plus-o\"></i> Create New TestName Method</button>",
            ],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'lab_id',
                'contentOptions' => ['style' => 'width: 8.7%'],
                'label' => 'Lab',
                'format' => 'raw',
                'width'=>'20%',
                'value' => function($model) {
                   if ($model->lab){
                      return $model->lab->labname;
                   }else{
                        return "";
                   }
                   
                },
                'filterType' => GridView::FILTER_SELECT2,
                'filter' => ArrayHelper::map(Lab::find()->all(),'lab_id','labname'),
                'filterWidgetOptions' => [
                    'pluginOptions' => ['allowClear' => true],
               ],
               'filterInputOptions' => ['placeholder' => 'Lab',]
            ],
            [
                'attribute' => 'sampletype_id',
                'contentOptions' => ['style' => 'width: 8.7%'],
                'label' => 'Sample Type',
                'format' => 'raw',
                'width'=>'20%',
                'value' => function($model) {
                   if ($model->sampletype){
                      return $model->sampletype->type;
                   }else{
                        return "";
                   }
                   
                },
                'filterType' => GridView::FILTER_SELECT2,
                'filter' => ArrayHelper::map(Sampletype::find()->where(['status_id'=>1])->all(),'sampletype_id','type'),
                'filterWidgetOptions' => [
                    'pluginOptions' => ['allowClear' => true],
               ],
               'filterInputOptions' => ['placeholder' => 'Sampletype',]
            ],
            [
                'attribute' => 'testname_id',
                'label' => 'Test Name',
                'value' => function($model) {

                    if ($model->testname){
                        return $model->testname->testName;
                    }else{
                        return "";
                    }
                    
                },
                'filterType' => GridView::FILTER_SELECT2,
                'filter' => $testnamelist,
                'filterWidgetOptions' => [
                    'pluginOptions' => ['allowClear' => true],
                ],
                'filterInputOptions' => ['placeholder' => 'Test Name', 'testcategory_id' => 'grid-products-search-category_type_id']
            ],
            [
                'attribute' => 'method_id',
                'label' => 'Method',
                'value' => function($model) {
                     if($model->method){
                      return $model->method->method;
                    }else{
                        return "";
                 }    
                },
                'filterType' => GridView::FILTER_SELECT2,
                'filter' => $methodlist,
                'filterWidgetOptions' => [
                    'pluginOptions' => ['allowClear' => true],
               ],
               'filterInputOptions' => ['placeholder' => 'Method', 'testcategory_id' => 'grid-products-search-category_type_id']
            ],
            [
                'header' => 'Fee',
                'value' => function($model) {
                     if($model->method){
                      return $model->method->fee;
                    }else{
                        return "";
                 }    
                },
                'filterType' => GridView::FILTER_SELECT2,
                'filter' => $methodlist,
                'filterWidgetOptions' => [
                    'pluginOptions' => ['allowClear' => true],
               ],
               'filterInputOptions' => ['placeholder' => 'Method', 'testcategory_id' => 'grid-products-search-category_type_id']
            ],
            // 'create_time',
            // 'update_time',

            ['class' => 'kartik\grid\ActionColumn',
            'contentOptions' => ['style' => 'width: 8.7%'],
           'template' => '{sync}{view}{update}{delete}',
            'buttons'=>[
                'sync'=>function ($url, $model) {
                    $check = $model->checking($model->method->method_reference_id);

                    if($check == '"Synced"'){
                        return Html::button('<span class="glyphicon glyphicon-cloud"></span>', ['value'=>Url::to(['/lab/testnamemethod/checkmethod','id'=>$model->method->method_reference_id]), 'onclick'=>'LoadModal(this.title, this.value);', 'class' => 'btn btn-success','title' => Yii::t('app', "Sync Test Name Method")]);
                    }else{
                        return Html::button('<span class="glyphicon glyphicon-cloud"></span>', ['value'=>Url::to(['/lab/testnamemethod/checkmethod','id'=>$model->method->method_reference_id]), 'onclick'=>'LoadModal(this.title, this.value);', 'class' => 'btn btn-warning','title' => Yii::t('app', "Sync Test Name Method")]);
                    }
                    //return Html::button('<span class="glyphicon glyphicon-cloud"></span>', ['value'=>Url::to(['/lab/testnamemethod/checkmethod','id'=>$model->method->method_reference_id]), 'onclick'=>'LoadModal(this.title, this.value);', 'class' => 'btn btn-warning','title' => Yii::t('app', "Sync Test Name Method")]);
                },
                'view'=>function ($url, $model) {
                    return Html::button('<span class="glyphicon glyphicon-eye-open"></span>', ['value'=>Url::to(['/lab/testnamemethod/view','id'=>$model->testname_method_id]), 'onclick'=>'LoadModal(this.title, this.value);', 'class' => 'btn btn-primary','title' => Yii::t('app', "View Test Name Method")]);
                },
                'update'=>function ($url, $model) {
                    return Html::button('<span class="glyphicon glyphicon-pencil"></span>', ['value'=>Url::to(['/lab/testnamemethod/update','id'=>$model->testname_method_id]),'onclick'=>'LoadModal(this.title, this.value);', 'class' => 'btn btn-success','title' => Yii::t('app', "Update Test Name Method")]);
                },
                'delete'=>function ($url, $model) {
                    $urls = '/lab/testnamemethod/delete?id='.$model->testname_method_id;
                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', $urls,['data-confirm'=>"Are you sure you want to delete this record?<b></b>", 'data-method'=>'post', 'class'=>'btn btn-danger','title'=>'Delete Test Name Method','data-pjax'=>'0']);
                },
                // 'workflow'=>function ($url, $model) {
                //   $t = '/lab/testnamemethod/createworkflow?test_id='.$model->testname_method_id;
                //     return Html::button('<span class="glyphicon glyphicon-plus"></span><span class="glyphicon glyphicon-file"></span>', ['value'=>$t, 'class' => 'btn btn-warning btn-modal','onclick'=>'LoadModal(this.title, this.value, true, 900);','name' => Yii::t('app', "Manage Workflow"),'title' => Yii::t('app', "Create Workflow")]);
                // },
                ],
            ],
          
        ],
    ]); ?>
</div>
<script>
    function openModal(url,title){
        LoadModal(title,url,'true','900px');
    }
</script>