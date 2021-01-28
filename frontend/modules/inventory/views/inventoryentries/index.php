<?php

use yii\helpers\Html;
use kartik\export\ExportMenu;
use kartik\grid\GridView;
use yii\grid\ActionColumn;
use common\components\Functions;
use common\models\inventory\Products;
use yii\helpers\ArrayHelper;
use kartik\widgets\DatePicker;
use kartik\daterange\DateRangePicker;
/* @var $this yii\web\View */
/* @var $searchModel common\models\inventory\InventoryEntriesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$func= new Functions();
$Header="Department of Science and Technology<br>";
$Header.="Inventory Entries";
$this->title = 'Inventory Entries';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="inventory-entries-index">

    <div class="table-responsive">
     <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
         'panel' => [
                'type' => GridView::TYPE_PRIMARY,
                 'before'=> Html::button('<span class="glyphicon glyphicon-plus"></span> Entry Product', ['value'=>'/inventory/inventoryentries/create', 'class' => 'btn btn-success','title' => Yii::t('app', "Entry Product"),'id'=>'btnEntry','onclick'=>'addEntry(this.value,this.title)']),
                'heading' => '<span class="glyphicon glyphicon-book"></span>  ' . Html::encode($this->title),
            ],
        'pjax'=>true,
        'pjaxSettings' => [
            'options' => [
                'enablePushState' => false,
            ]
        ],
        'exportConfig'=>$func->exportConfig("Inventory Entries", "inventory_entries", $Header),
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            // 'rstl_id',
             [
                'class' => 'kartik\grid\ExpandRowColumn',
                'width' => '50px',
                'value' => function ($model, $key, $index, $column) {
                    return GridView::ROW_COLLAPSED;
                },
                'detail' => function ($model, $key, $index, $column) {
                    return Yii::$app->controller->renderPartial('_withdrawdetails', ['model' => $model]);
                },
                'headerOptions' => ['class' => 'kartik-sheet-style'],
                'expandOneOnly' => true
            ],

            [
                'attribute' => 'product_id',
                'label' => 'Product',
                'value' => function($model) {
                    return $model->product->product_name;
                },
                'filterType' => GridView::FILTER_SELECT2,
                'filter' => ArrayHelper::map(Products::find()->asArray()->all(), 'product_id', 'product_name'),
                'filterWidgetOptions' => [
                    'pluginOptions' => ['allowClear' => true],
                ],
                'filterInputOptions' => ['placeholder' => 'Products', 'id' => 'grid-product-search']
            ],
            //'manufacturing_date',
            [
               'attribute'=>'manufacturing_date',
               'filterType'=> GridView::FILTER_DATE_RANGE,
               'value' => function($model) {
                    if($model->manufacturing_date)
                        return date_format(date_create($model->manufacturing_date),"m/d/Y");
                    return null;
                },
                'filterWidgetOptions' => ([
                     'model'=>$model,
                     'useWithAddon'=>true,
                     'attribute'=>'manufacturing_date',
                     'startAttribute'=>'createDateStart',
                     'endAttribute'=>'createDateEnd',
                     'presetDropdown'=>TRUE,
                     'convertFormat'=>TRUE,
                     'pluginOptions'=>[
                         'allowClear' => true,
                        'locale'=>[
                            'format'=>'Y-m-d',
                            'separator'=>' to ',
                        ],
                         'opens'=>'left',
                      ],
                     'pluginEvents'=>[
                        "cancel.daterangepicker" => "function(ev, picker) {
                        picker.element[0].children[1].textContent = '';
                        $(picker.element[0].nextElementSibling).val('').trigger('change');
                        }",
                        
                        'apply.daterangepicker' => 'function(ev, picker) { 
                        var val = picker.startDate.format(picker.locale.format) + picker.locale.separator +
                        picker.endDate.format(picker.locale.format);

                        picker.element[0].children[1].textContent = val;
                        $(picker.element[0].nextElementSibling).val(val);
                        }',
                      ] 
                     
                ]),        
               
            ], 
            [
               'attribute'=>'expiration_date',
               'filterType'=> GridView::FILTER_DATE_RANGE,
               'value' => function($model) {
                    if($model->expiration_date)
                        return date_format(date_create($model->expiration_date),"m/d/Y");
                    return null;
                },
                'filterWidgetOptions' => ([
                     'model'=>$model,
                     'useWithAddon'=>true,
                     'attribute'=>'expiration_date',
                     'startAttribute'=>'createDateStart2',
                     'endAttribute'=>'createDateEnd2',
                     'presetDropdown'=>TRUE,
                     'convertFormat'=>TRUE,
                     'pluginOptions'=>[
                         'allowClear' => true,
                        'locale'=>[
                            'format'=>'Y-m-d',
                            'separator'=>' to ',
                        ],
                         'opens'=>'left',
                      ],
                     'pluginEvents'=>[
                        "cancel.daterangepicker" => "function(ev, picker) {
                        picker.element[0].children[1].textContent = '';
                        $(picker.element[0].nextElementSibling).val('').trigger('change');
                        }",
                        
                        'apply.daterangepicker' => 'function(ev, picker) { 
                        var val = picker.startDate.format(picker.locale.format) + picker.locale.separator +
                        picker.endDate.format(picker.locale.format);

                        picker.element[0].children[1].textContent = val;
                        $(picker.element[0].nextElementSibling).val(val);
                        }',
                      ] 
                     
                ]),        
               
            ],              
           
            //'created_by',
            //'suppliers_id',
            //'po_number',
            'quantity_onhand',
            'amount',
            //'total_amount',
            //'Image1',
            //'Image2',
            //'created_at',
            //'updated_at',
            ['class' => 'kartik\grid\ActionColumn'],
        ],
    ]); ?>
    </div>
</div>
<script type="text/javascript">
    function addEntry(url,title){
        LoadModal(title,url,'true','800px');
    }
</script>
