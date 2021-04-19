<?php

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\grid\GridView;
use yii\helpers\Url;
use yii\bootstrap\Modal;
use kartik\dialog\Dialog;
//use yii\web\JsExpression;
//use yii\widgets\ListView;
use kartik\tabs\TabsX;

/* @var $this yii\web\View */
/* @var $request common\models\referral\Pstcrequest */

$this->title = empty($request['request_ref_num']) ? $request['pstc_request_id'] : $request['request_ref_num'];
$this->params['breadcrumbs'][] = ['label' => 'Pstcrequests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$reference_num = !empty($request['request_ref_num']) ? $request['request_ref_num'] : '<i class="text-danger font-italic">Pending request</i>';

if(!empty($respond['request_ref_num'])){//With Reference
    $disableButton="false";
}else{ // NO reference number yet
    $disableButton="true";
}

$accepted = $request['accepted'];
$requestId = $request['pstc_request_id'];
?>
<div class="pstcrequest-view">
    <div class="image-loader" style="display: none;"></div>
    <div class="container table-responsive">
        <?php
            echo DetailView::widget([
            'model'=>$model,
            'responsive'=>true,
            'hover'=>true,
            'mode'=>DetailView::MODE_VIEW,
            'panel'=>[
                'heading'=>'<i class="glyphicon glyphicon-book"></i> Request Reference No. : ' . $reference_num,
                'type'=>DetailView::TYPE_PRIMARY,
            ],
            'buttons1' => '',
            'attributes'=>[
                [
                    'group'=>true,
                    'label'=>'Request Details ',
                    'rowOptions'=>['class'=>'info']
                ],
                [
                    'columns' => [
                        [
                            'label'=>'Customer Requested Date',
                            'format'=>'raw',
                            'value' => date('F j, Y h:i A', strtotime($request['created_at'])),
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                        [
                            'label'=>'PSTC',
                            'format'=>'raw',
                            'value'=> !empty($pstc) ? $pstc['name'] : "",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                    ],
                ],
                [
                    'columns' => [
                        [
                            'label'=>'Customer / Agency',
                            'format'=>'raw',
                            'value'=> $request['customer_id'] > 0 && !empty($customer) ? $customer['customer_name'] : "",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                        [
                            'label'=>'Address',
                            'format'=>'raw',
                            'value'=> $request['customer_id'] > 0 && !empty($customer['customer_name']) ? $customer['address'] : "",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                    ],
                    
                ],
                [
                    'columns' => [
                        [
                            'label'=>'Estimated Due Date',
                            'format'=>'raw',
                            'value'=> !empty($request['duedate'])  ? date('F j, Y', strtotime($request['duedate'])) : "<i class='text-danger font-italic'>Pending request</i>",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                        [
                            'label'=>'Tel no.',
                            'format'=>'raw',
                            'value'=> $request['customer_id'] > 0 && !empty($customer['customer_name']) ? $customer['tel'] : "",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                    ],
                ],       
                [
                    'group'=>true,
                    'label'=>'Transaction Details',
                    'rowOptions'=>['class'=>'info']
                ],
                [
                    'columns' => [
                        [ 
                            'label'=>'Recieved By',
                            //'attribute' => 'received_by',
                            'value'=> $request['received_by'],
                            'format'=>'raw',
                            'displayOnly'=>true,
                            'valueColOptions'=>['style'=>'width:30%']
                        ],
                        [
                            'label'=>'Submitted By',
                            //'attribute' => 'submitted_by',
                            'value'=> $request['submitted_by'],
                            'format'=>'raw',
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                    ],
                ],
            ],

        ]);
        ?>
    </div>
    <div class="container">
        <div class="table-responsive">
        <?php
            $sampleGridColumns = [
                [
                    'header' => 'Temporary Sample ID',
                    'attribute'=>'pstc_sample_id',
                    'enableSorting' => false,
                    'format' => 'raw',
                    'contentOptions' => [
                        'style'=>'overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                [
                    'header' => 'Sample Name',
                    'attribute'=>'sample_name',
                    'enableSorting' => false,
                    'format' => 'raw',
                    'contentOptions' => [
                        'style'=>'max-width:70px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                [
                    'header' => 'Sample Description',
                    'attribute'=>'sample_description',
                    'format' => 'raw',
                    'enableSorting' => false,
                    //'value' => function($data) use ($request){
                    //    return ($request->lab_id == 2) ? "Sampling Date: <span style='color:#000077;'><b>".date("Y-m-d h:i A",strtotime($data->sampling_date))."</b></span>,&nbsp;".$data->description : $data->description;
                    //},
                   'contentOptions' => [
                        'style'=>'max-width:180px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                [
                    'attribute'=>'customer_description',
                    'header'=>'Description provided by Customer',
                    'format' => 'raw',
                    'enableSorting' => false,
                    'value' => function($data){
                        return empty($data['customer_description']) ? "<span style='color:#444444;font-size:11px;'><i>No information provided</i></span>" : $data['customer_description'];
                    },
                   'contentOptions' => [
                        'style'=>'max-width:180px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                /* [
                    'class' => 'kartik\grid\ActionColumn',
                    'template' => '{update} {remove}',
                    'dropdown' => false,
                    'dropdownOptions' => ['class' => 'pull-right'],
                    'urlCreator' => function ($action, $data, $key, $index) {
                        if ($action === 'remove') {
                            $url ='/pstc/pstcsample/delete?id='.$data['pstc_sample_id'];
                            return $url;
                        }
                    },
                    'headerOptions' => ['class' => 'kartik-sheet-style'],
                    'buttons' => [
                        'update' => function ($url, $data) use ($request) {
                            if($data['active'] == 1 && $request['accepted'] == 0){
                                return Html::a('<span class="glyphicon glyphicon-pencil"></span>', '#', ['class'=>'btn btn-primary','title'=>'Update Sample','onclick' => 'updateSample('.$data['pstc_sample_id'].','.$data['pstc_request_id'].')']);
                            } else {
                                return null;
                            }
                        },
                        'remove' => function ($url, $data) use ($request) {
                            if($data['sample_code'] == "" && $data['active'] == 1 && $request['accepted'] == 0){
                                return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url,['data-confirm'=>"Are you sure you want to delete <b>".$data['sample_name']."</b>?",'data-method'=>'post','class'=>'btn btn-danger','title'=>'Remove Sample','data-pjax'=>'0']);
                            } else {
                                return null;
                            }
                        },
                    ],
                ], */
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'template' => '{delete}',
                    'dropdown' => false,
                    'dropdownOptions' => ['class' => 'pull-right'],
                    /*'urlCreator' => function ($action, $model, $key, $index) {
                        if ($action === 'remove') {
                            $url = '';//'/pstc/pstcsample/delete?id='.$model->pstc_sample_id;
                            return $url;
                        }
                    },*/
                    'headerOptions' => ['class' => 'kartik-sheet-style'],
                    'buttons' => [
                        'update' => function ($url, $data) use ($accepted) {
                            if($data['active'] == 1 && $accepted == 0){
                                return Html::a('<span class="glyphicon glyphicon-pencil"></span>', '#', ['class'=>'btn btn-primary','title'=>'Update PSTC Sample','onclick' => 'updateSample('.$data['pstc_sample_id'].','.$data['pstc_request_id'].','.$data['pstc_id'].',this.title)']);
                            } else {
                                return null;
                            }
                        },
                        'delete' => function ($url, $data) use ($accepted) {
                            if($data['active'] == 1 && $accepted == 0){
                                $urls = '/pstc/pstcrequest/sample_delete?sample_id='.$data['pstc_sample_id'].'&request_id='.$data['pstc_request_id'].'&pstc_id='.$data['pstc_id'];
                                return Html::a('<span class="glyphicon glyphicon-trash"></span>', $urls,['data-confirm'=>"Tests under this sample will also be deleted, Are you sure you want to delete this <b>".$data['sample_name']."</b> sample?", 'data-method'=>'post', 'class'=>'btn btn-danger','title'=>'Delete PSTC Sample','data-pjax'=>'0']);
                            } else {
                                return null;
                            }
                        },
                    ],
                ],
            ];

            echo GridView::widget([
                'id' => 'sample-grid',
                //'dataProvider'=> $sampleDataProvider,
                'dataProvider'=> $sampleDataProvider,
                'pjax'=>true,
                'pjaxSettings' => [
                    'options' => [
                        'enablePushState' => false,
                    ]
                ],
                'responsive'=>true,
                'striped'=>true,
                'hover'=>true,
                'panel' => [
                    'heading'=>'<h3 class="panel-title">Samples</h3>',
                    'type'=>'primary',
                    //'before'=>null,
                    'after'=>false,
                    'before'=> ($request['accepted'] == 0) ? Html::button('<i class="glyphicon glyphicon-plus"></i> Add Sample', ['value' => Url::to(['/pstc/pstcrequest/createsample','request_id'=>$request['pstc_request_id']]),'title'=>'Add Sample', 'onclick'=>'addSample(this.value,this.title)', 'class' => 'btn btn-success','id' => 'modalBtn']) : '',
                    // 'footer'=> ($countSample > 0 && empty($respond['request_ref_num']) && $request['accepted'] == 0) ? $btn_saveRequest : '',
                ],
                'columns' => $sampleGridColumns,
                'toolbar' => [
                    'content'=> Html::a('<i class="glyphicon glyphicon-repeat"></i> Refresh Grid', [Url::to(['/pstc/pstcrequest/view','request_id'=>$request['pstc_request_id'],'pstc_id'=>$request['pstc_id']])], [
                                'class' => 'btn btn-default', 
                                'title' => 'Refresh Grid'
                            ]),
                ],
            ]);
        ?>

<?php
        $btn_saveRequest = ($request['is_referral'] == 0) ? Html::button('<span class="glyphicon glyphicon-save"></span> Save as Local Request', ['value' => Url::to(['/pstc/pstcrequest/request_local','request_id'=>$request['pstc_request_id'],'pstc_id'=>$request['pstc_id']]),'title'=>'Save as Local Request', 'onclick'=>'saveRequest(this.value,this.title)', 'class' => 'btn btn-primary','id' => 'modalBtn'])." ".Html::button('<span class="glyphicon glyphicon-bell"></span> Notify CRO', ['value' => Url::to(['/pstc/pstcrequest/notifycro','request_id'=>$request['pstc_request_id'],'pstc_id'=>$request['pstc_id']]),'title'=>'Notifying CRO', 'onclick'=>'notify(this.value,this.title)', 'class' => 'btn btn-warning','id' => 'modalBtn']): Html::button('<span class="glyphicon glyphicon-save"></span> Save as Referral Request', ['value' => Url::to(['/pstc/pstcrequest/request_referral','request_id'=>$request['pstc_request_id'],'pstc_id'=>$request['pstc_id']]),'title'=>'Save as Referral Request', 'onclick'=>'saveRequest(this.value,this.title)', 'class' => 'btn btn-primary','id' => 'modalBtn']);
        $analysisGridColumns = [
             [
                'attribute'=>'pstc_sample_id',
                'header'=>'Temporary Sample ID',
                'contentOptions' => ['style' => 'width: 15%;word-wrap: break-word;white-space:pre-line;'],
                'enableSorting' => false,
            ],
            [
                'attribute'=>'testname',
                'header'=>'Test/ Calibration Requested',
                'contentOptions' => ['style' => 'width: 15%;word-wrap: break-word;white-space:pre-line;'],
                'enableSorting' => false,
            ],
            [
                'attribute'=>'method',
                'header'=>'Test Method',
                'enableSorting' => false,  
                'contentOptions' => ['style' => 'width: 50%;word-wrap: break-word;white-space:pre-line;'],              
            ],
            [
                'attribute'=>'quantity',
                'header'=>'Quantity',
                'hAlign'=>'center',
                'enableSorting' => false,
                'pageSummary' => '<span style="float:right";>SUBTOTAL<BR>DISCOUNT<BR><B>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;TOTAL</B></span>',       
            ],
            [
                'attribute'=>'fee',
                'header'=>'Unit Price',
                'enableSorting' => false,
                'hAlign'=>'right',
                'value'=>function($model){
                    return $model['fee'];
                },
                'contentOptions' => [
                    'style'=>'max-width:80px; overflow: auto; white-space: normal; word-wrap: break-word;'
                ],
                'hAlign' => 'right', 
                'vAlign' => 'left',
                'format' => 'raw',
                'width' => '7%',
                  'pageSummary'=> function (){
                       
                        return  '<div id="subtotal">₱1</div><div id="discount">₱1</div><div id="total"><b>₱1</b></div>';
                      
                  },
            ],
            [
                    'class' => 'kartik\grid\ActionColumn',
                    'template' => '{delete}',
                    'dropdown' => false,
                    'dropdownOptions' => ['class' => 'pull-right'],
                    /*'urlCreator' => function ($action, $model, $key, $index) {
                        if ($action === 'remove') {
                            $url = '';//'/pstc/pstcsample/delete?id='.$model->pstc_sample_id;
                            return $url;
                        }
                    },*/
                    'headerOptions' => ['class' => 'kartik-sheet-style'],
                    'buttons' => [
                        'delete' => function ($url, $data) use ($accepted,$request) {
                            if($accepted == 0){
                                // return Html::a('<span class="glyphicon glyphicon-trash"></span>', '#', ['class'=>'btn btn-danger','title'=>'Delete PSTC Analysis','onclick' => 'deleteTest('.$data['pstc_analysis_id'].','.$request['pstc_request_id'].','.$data['pstc_id'].',this.title)']);

                                $urls = '/pstc/pstcrequest/analysis_delete?analysis_id='.$data['pstc_analysis_id'].'&request_id='.$request['pstc_request_id'].'&pstc_id='.$data['pstc_id'];
                                return Html::a('<span class="glyphicon glyphicon-trash"></span>', $urls,['data-confirm'=>"Are you sure you want to delete this <b>".$data['testname']."</b> test?", 'data-method'=>'post', 'class'=>'btn btn-danger','title'=>'Delete PSTC Test','data-pjax'=>'0']);

                            } else {
                                return null;
                            }
                        },
                    ],
                ],
          
        ];

            echo GridView::widget([
                'id' => 'sample-grid',
                //'dataProvider'=> $sampleDataProvider,
                'dataProvider'=> $analysisDataProvider,
                'pjax'=>true,
                'pjaxSettings' => [
                    'options' => [
                        'enablePushState' => false,
                    ]
                ],
                'responsive'=>true,
                'striped'=>true,
                'hover'=>true,
                'panel' => [
                    'heading'=>'<h3 class="panel-title">Analysis</h3>',
                    'type'=>'primary',
                    //'before'=>null,
                    'after'=>false,
                    'before'=> ($request['accepted'] == 0) ? Html::button('<i class="glyphicon glyphicon-plus"></i> Add Analysis', ['value' => Url::to(['/pstc/pstcrequest/createanalysis','request_id'=>$request['pstc_request_id'],'pstc_id'=>$request['pstc_id']]),'title'=>'Add Analysis', 'onclick'=>'addAnalysis(this.value,this.title)', 'class' => 'btn btn-success','id' => 'modalBtn']) ." ".Html::button('<i class="glyphicon glyphicon-plus"></i> Add Package', ['value' => Url::to(['/pstc/pstcrequest/createpackage','id'=>$request['pstc_request_id']]),'title'=>'Add Package', 'onclick'=> 'addAnalysis(this.value,this.title)', 'class' => 'btn btn-success','id' => 'btn_add_package']) : '',
                    'footer'=> ($countAnalysis > 0 && empty($respond['request_ref_num']) && $request['accepted'] == 0) ? $btn_saveRequest : '',
                ],
                'columns' => $analysisGridColumns,
                'toolbar' => [
                    'content'=> Html::a('<i class="glyphicon glyphicon-repeat"></i> Refresh Grid', [Url::to(['/pstc/pstcrequest/view','request_id'=>$request['pstc_request_id'],'pstc_id'=>$request['pstc_id']])], [
                                'class' => 'btn btn-default', 
                                'title' => 'Refresh Grid'
                            ]),
                ],
            ]);
        ?>
        </div>
    </div>
    <!-- <div class="container">
        <?php
            
            
            $btn_saveRequest = ($request['is_referral'] == 0) ? Html::button('<span class="glyphicon glyphicon-save"></span> Save as Local Request', ['value' => Url::to(['/pstc/pstcrequest/request_local','request_id'=>$request['pstc_request_id'],'pstc_id'=>$request['pstc_id']]),'title'=>'Save as Local Request', 'onclick'=>'saveRequest(this.value,this.title)', 'class' => 'btn btn-primary','id' => 'modalBtn']) : Html::button('<span class="glyphicon glyphicon-save"></span> Save as Referral Request', ['value' => Url::to(['/pstc/pstcrequest/request_referral','request_id'=>$request['pstc_request_id'],'pstc_id'=>$request['pstc_id']]),'title'=>'Save as Referral Request', 'onclick'=>'saveRequest(this.value,this.title)', 'class' => 'btn btn-primary','id' => 'modalBtn']);

          
        ?>
    </div> -->
    
    <?php 
    
    if(!empty($respond['request_ref_num']) && $request['accepted'] == 1): ?>
    <div class="container">
        <div class="panel panel-primary">
        <div class="panel-body">
        <!--<div class="table-responsive">-->
        <?php
            $items = [
                [
                    'label'=>'<i class="glyphicon glyphicon-save-file"></i> Uploaded Request Form',
                    'content'=> $this->renderAjax('_attachment',['ref_num'=>$respond['request_ref_num'],'request'=>$request,'attachmentDataprovider'=>$attachmentDataprovider]),
                    'active'=>true,
                    //'linkOptions'=>['data-url'=>Url::to(['/pstcrequest/show_attachment?id='.$model->pstc_request_id])]
                ],
            ];

            echo TabsX::widget([
                'items'=>$items,
                'position'=>TabsX::POS_ABOVE,
                //'bordered'=>true,
                'encodeLabels'=>false
            ]);
        ?>
        <!--</div>-->
        </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
    Modal::begin([
        'clientOptions' => ['backdrop' => 'static', 'keyboard' => false],
        'bodyOptions'=>[
            'class' => 'modal-body',
            'style'=>'padding-bottom: 20px',
        ],
        'options' => [
            'id' => 'modalPstcRequest',
            'tabindex' => false, // important for Select2 to work properly
            //'tabindex' => 0, // important for Select2 to work properly
        ],
        'header' => '<h4 class="fa fa-clone" style="padding-top: 0px;margin-top: 0px;padding-bottom:0px;margin-bottom: 0px"> <span class="modal-title" style="font-size: 16px;font-family: \'Source Sans Pro\',sans-serif;"></span></h4>',
        'size' => Modal::SIZE_LARGE,
    ]);
    echo "<div>";
    echo "<div class='modal-scroll'><div id='modalContent' style='margin-left: 5px;'><div style='text-align:center;'><img src='/images/img-png-loader64.png' alt=''></div></div>";
    echo "<div>&nbsp;</div>";
    echo "</div></div>";
    Modal::end();
?>

<script type="text/javascript">
    function saveRequest(url,title){
        $(".modal-title").html(title);
        $('#modalPstcRequest').modal('show')
            .find('#modalContent')
            .load(url);
    }

    function addSample(url,title){
        $('.modal-title').html(title);
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
    }

    function updateSample(id,requestId,pstcId,title){
        var url = '/pstc/pstcrequest/sample_update?sample_id='+id+'&request_id='+requestId+'&pstc_id='+pstcId;
        $('.modal-title').html(title);
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
    }
    //btc
    function deleteSample(id,requestId,pstcId,title){
        var url = '/pstc/pstcrequest/sample_delete?sample_id='+id+'&request_id='+requestId+'&pstc_id='+pstcId;
        $('.modal-title').html(title);
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
    }
    function deleteTest(id,requestId,pstcId,title){
        var url = '/pstc/pstcrequest/analysis_delete?analysis_id='+id+'&request_id='+requestId+'&pstc_id='+pstcId;
        $('.modal-title').html(title);
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
    }

    // function notify(request_id,pstc_id){
    //     var url = '/pstc/pstcrequest/notifycro?pstc_request_id='+request_id+'&pstc_id='+pstcId;
    //     $('.modal-title').html(title);
    //     $('#modal').modal('show')
    //         .find('#modalContent')
    //         .load(url);
    // }

    function notify(url,title){
        $(".modal-title").html(title);
        $('#modalPstcRequest').modal('show')
            .find('#modalContent')
            .load(url);
    }
    /*function updateSample(id,requestId){
       var url = '/pstc/pstcsample/update?id='+id+'&request_id='+requestId;
        $('.modal-title').html('Update Sample');
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
    } */

    function addAnalysis(url,title){
        $(".modal-title").html(title);
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
    }

    // function updateAnalysis(id,requestId,title){
    //     $.ajax({
    //         url: '/pstc/pstcanalysis/getdefaultpage?analysis_id='+id,
    //         success: function (data) {
    //             $('.image-loader').removeClass('img-loader');
    //             var url = '/pstc/pstcanalysis/update?id='+id+'&request_id='+requestId+'&page='+data;
    //             $('.modal-title').html(title);
    //             $('#modalAnalysis').modal('show')
    //                 .find('#modalContent')
    //                 .load(url);
    //         },
    //         beforeSend: function (xhr) {
    //             $('.image-loader').addClass('img-loader');
    //         }
    //     });
    // }

    // function updateReferralAnalysis(id,requestId,title){
    //     $.ajax({
    //         url: '/pstc/pstcanalysis/getreferraldefaultpage?analysis_id='+id,
    //         success: function (data) {
    //             $('.image-loader').removeClass('img-loader');
    //             var url = '/pstc/pstcanalysis/update_not_offer?id='+id+'&request_id='+requestId+'&page='+data;
    //             $('.modal-title').html(title);
    //             $('#modalAnalysis').modal('show')
    //                 .find('#modalContent')
    //                 .load(url);
    //         },
    //         beforeSend: function (xhr) {
    //             $('.image-loader').addClass('img-loader');
    //         }
    //     });
    // }

    function updatePackage(url,title){
        $('.modal-title').html(title);
        $('#modalAnalysis').modal('show')
            .find('#modalContent')
            .load(url);
    }

    function uploadRequest(title) {
        var _replace = "<div style='text-align:center;'><img src='/images/img-loader64.gif' alt=''></div>";
        var url = "<?= Url::to(['/pstc/pstcattachment/upload']) ?>";
        $('#modalContent').html(_replace);
        $('.modal-title').html(title);
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
    }

    function downloadRequest(url) {
        $.ajax({
            url: url,
            success: function (data) {
                $('.image-loader').removeClass('img-loader');
            },
            beforeSend: function (xhr) {
                $('.image-loader').addClass('img-loader');
            }
        });
    }
</script>
<style type="text/css">
/* Absolute Center Spinner */
.img-loader {
    position: fixed;
    z-index: 999;
    /*height: 2em;
    width: 2em;*/
    height: 64px;
    width: 64px;
    overflow: show;
    margin: auto;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background-image: url('/images/img-png-loader64.png');
    background-repeat: no-repeat;
}
/* Transparent Overlay */
.img-loader:before {
    content: '';
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.3);
}
</style>