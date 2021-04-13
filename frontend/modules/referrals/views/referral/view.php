<?php

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\grid\GridView;
use yii\helpers\Url;
use common\components\Functions;
use common\components\ReferralComponent;
use common\models\lab\Cancelledrequest;
use common\models\lab\Discount;
use common\models\lab\Request;
use common\models\lab\Sample;
use common\models\lab\Sampletype;
use common\models\finance\Paymentitem;

use common\models\lab\Package;
use yii\bootstrap\Modal;
use kartik\dialog\Dialog;

//$Connection = Yii::$app->financedb;
//$func = new Functions();
//$referralcomp = new ReferralComponent();

$this->title = empty($request['referral_code']) ? $request['referral_id'] : $request['referral_code'];
$this->params['breadcrumbs'][] = ['label' => 'Referrals', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$cancelButton = "";
if($notification['notification_type_id'] == 1 && $notification['responded'] == 0){
    $actionButtonConfirm = "<div class='row' style='margin-left: 2px;padding-top: 5px'>".Html::button('<span class="glyphicon glyphicon-ok"></span> Accept Referral Notification', ['value'=>Url::to(['/referrals/referral/confirm','local_request_id'=>$request['local_request_id'],'referral_id'=>$request['referral_id'],'notice_id'=>$notification['notification_id'],'sender_id'=>$notification['sender_id']]),'onclick'=>'confirmNotification(this.value,this.title)','class' => 'btn btn-primary','title' => 'Confirm Referral of '.$receiving_agency]);
} else {
    $actionButtonConfirm = "";
}

if($notification['notification_type_id'] == 3 && $notification['responded'] == 0){
    $actionButtonSaveLocal= "<div class='row' style='margin-left: 2px;padding-top: 5px'>".Html::button('<span class="glyphicon glyphicon-save"></span> Save as Local Request', ['value'=>Url::to(['/referrals/referral/savelocal','referral_id'=>$request['referral_id'],'notice_id'=>$notification['notification_id']]),'onclick'=>'localSave(this.value,this.title)','class' => 'btn btn-primary','title' => 'Save as Local Request']);
} else {
    $actionButtonSaveLocal = "";
}
?>
<div class="section-request">
<div class="request-view ">
    <div class="image-loader" style="display: none;"></div>
    <div class="container table-responsive">
        <?php
            echo DetailView::widget([
            'model'=>$model,
            'responsive'=>true,
            'hover'=>true,
            'mode'=>DetailView::MODE_VIEW,
            'panel'=>[
                'heading'=>'<i class="glyphicon glyphicon-book"></i> Referral Code ' . $request['referral_code'],
                'type'=>DetailView::TYPE_PRIMARY,
            ],
            'buttons1' => '',
            'attributes'=>[
                [
                    'group'=>true,
                    'label'=>'Referral Details '.$cancelButton,
                    'rowOptions'=>['class'=>'info']
                ],
                [
                    'columns' => [
                        [
                            'label'=>'Referral Code',
                            'displayOnly'=>true,
                            'valueColOptions'=>['style'=>'width:30%'],
                            'value'=> $request['referral_code'],
                        ],
                        [
                            'label'=>'Customer / Agency',
                            'format'=>'raw',
                            'value'=> $request['customer_id'] > 0 && count($customer) > 0 ? $customer['customer_name'] : "Customer Details is not sync yet",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                    ],
                ],
                [
                    'columns' => [
                        [
                            'label'=>'Referral Date / Time',
                            'format'=>'raw',
                            'value'=> ($request['referral_date_time'] != "0000-00-00 00:00:00") ? Yii::$app->formatter->asDate($request['referral_date_time'], 'php:F j, Y h:i a') : "<i class='text-danger font-weight-bold h5'>Pending referral request</i>",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                        [
                            'label'=>'Address',
                            'format'=>'raw',
                            'value'=> $request['customer_id'] > 0 && count($customer) > 0 ? $customer['address'] : "",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                    ],
                    
                ],
                [
                    'columns' => [
                       [
                            'label'=>'Sample Received Date',
                            'format'=>'raw',
                            'value'=> !empty($request['sample_received_date']) ? Yii::$app->formatter->asDate($request['sample_received_date'], 'php:F j, Y') : "<i class='text-danger font-weight-bold h5'>No sample received date</i>",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                        [
                            'label'=>'Tel no.',
                            'format'=>'raw',
                            'value'=> $request['customer_id'] > 0 && count($customer) > 0 ? $customer['tel'] : "",
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
                            'value'=> ($request['report_due'] != "0000-00-00 00:00:00") ? Yii::$app->formatter->asDate($request['report_due'], 'php:F j, Y') : "<i class='text-danger font-weight-bold h5'>Pending referral request</i>",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                        [
                            'label'=>'Fax no.',
                            'format'=>'raw',
                            'value'=> $request['customer_id'] > 0 && count($customer) > 0 ? $customer['fax'] : "",
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                    ],
                ],
                [
                    'columns' => [
                        [
                            //'attribute'=>'report_due',
                            'label'=>'Referred by',
                            'format'=>'raw',
                            'value'=> !empty($receiving_agency) ? $receiving_agency : null,
                            'displayOnly'=>true
                        ],
                        [
                            'label'=>'Referred to',
                            'format'=>'raw',
                            //'value'=>$model->customer ? $model->customer->fax : "",
                            'value'=> !empty($testing_agency) ? $testing_agency : null,
                            'valueColOptions'=>['style'=>'width:30%'], 
                            'displayOnly'=>true
                        ],
                    ],
                ],
                [
                    'group'=>true,
                    'label'=>'Payment Details',
                    'rowOptions'=>['class'=>'info']
                ],
                [
                    'columns' => [
                        [
                            'label'=>'Deposit Slip',
                            'format' => 'raw',
                            'value'=>function() use ($depositslip,$model){
                                $link = '';
                                $link .= !empty($model->request_ref_num) && $model->referralrequest->referral_type_id == 1 ? Html::button('<span class="glyphicon glyphicon-upload"></span>Upload', ['value'=>Url::to(['/referrals/attachment/upload_deposit','referral_id'=>$model->referral_id,'request_id'=>$model->request_id]), 'onclick'=>'upload(this.value,this.title)', 'class' => 'btn btn-primary btn-xs','title' => 'Upload Deposit Slip']) : '';
                             
							 
								if($depositslip > 0){
                                    foreach ($depositslip as $deposit) {
										$link .= "<br>".Html::a('<span class="glyphicon glyphicon-save-file"></span> '.$deposit['filename'],'/referrals/attachment/download?request_id='.$model->request_id.'&file='.$deposit['filename'].'&referral_id='.$model->referral_id, ['style'=>'font-size:12px;color:#000077;font-weight:bold;','title'=>'Download Deposit Slip','target'=>'_self']);
										//$link .="<br><a href='http://eulims.onelab.ph/uploads/referral/".$deposit['filename']."' download>".$deposit['filename']."</a>";
										//$link .=  "<br>".Html::a('<span class="glyphicon glyphicon-save-file"></span> '.$deposit['filename'],'https://eulims.onelab.ph/uploads/referral/'.$deposit['filename'], ['style'=>'font-size:12px;color:#000077;font-weight:bold;','title'=>'Download Deposit Slip']);
									}
									
                                }
                                return $link;
								
                            },
                            'displayOnly'=>true,
                            'valueColOptions'=>['style'=>'width:30%;vertical-align: top;'],
                            'labelColOptions' => ['style' => 'width: 20%; text-align: right; vertical-align: top;'],
                            //'vAlign' => 'right',
                        ],
                        [
                            'label'=>'Official Receipt',
                            'format'=>'raw',
                            'value'=>function() use ($officialreceipt,$model){
                                $link = '';
                                $link .= !empty($model->request_ref_num) && $model->referralrequest->referral_type_id == 1 ? Html::button('<span class="glyphicon glyphicon-upload"></span>Upload', ['value'=>Url::to(['/referrals/attachment/upload_or','referral_id'=>$model->referral_id,'request_id'=>$model->request_id]), 'onclick'=>'upload(this.value,this.title)', 'class' => 'btn btn-primary btn-xs','title' => 'Upload Official Receipt']) : '';
                             
								if($officialreceipt > 0){
                                    foreach ($officialreceipt as $or) {
                                       $link .= "<br>".Html::a('<span class="glyphicon glyphicon-save-file"></span> '.$or['filename'],'/referrals/attachment/download?request_id='.$model->request_id.'&file='.$or['filename'].'&referral_id='.$model->referral_id, ['style'=>'font-size:12px;color:#000077;font-weight:bold;','title'=>'Download Deposit Slip','target'=>'_self']);
										
                                    }
                                }
                                return $link;
                            },
                            'valueColOptions'=>['style'=>'width:30%;vertical-align: top;'], 
                            'displayOnly'=>true,
                            'labelColOptions' => ['style' => 'width: 20%; text-align: right; vertical-align: top;'],
                            //'vAlign' => 'right',
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
                            'format'=>'raw',
                            'value'=>$request['cro_receiving'],
                            'displayOnly'=>true,
                            'valueColOptions'=>['style'=>'width:30%']
                        ],
                        [
                            'label'=>'Conforme',
                            'value'=> $request['conforme'],
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
            $gridColumns = [
                [
                    'attribute'=>'sample_code',
                    'enableSorting' => false,
                    'contentOptions' => [
                        'style'=>'max-width:70px; overflow: auto; white-space: normal; word-wrap: break-word;'
                    ],
                ],
                [
                    'attribute'=>'sample_name',
                    'enableSorting' => false,
                ],
                [
                    'attribute'=>'description',
                    'format' => 'raw',
                    'enableSorting' => false,
                    'value' => function($data) use ($request){
                        return ($request['lab_id'] == 2) ? "Sampling Date: <span style='color:#000077;'><b>".date("Y-m-d h:i A",strtotime($data['sampling_date']))."</b></span>,&nbsp;".$data['description'] : $data['description'];
                    },
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
            ];

            echo GridView::widget([
                'id' => 'sample-grid',
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
                    'before'=>null,
                    'after'=>false,
                ],
                /*'krajeeDialogSettings' => [ 
                    'options' => ['title' => 'Your sssse'],
                    'overrideYiiConfirm' => false,
                ],*/
                'columns' => $gridColumns,
                'toolbar' => [
                    'content'=> Html::a('<i class="glyphicon glyphicon-repeat"></i> Refresh Grid', [Url::to(['referral/view','id'=>$request['referral_id'],'notice_id'=>$notification['notification_id']])], [
                                'class' => 'btn btn-default', 
                                'title' => 'Refresh Grid'
                            ]),
                ],
            ]);
        ?>
        </div>
    </div>
    <div class="container">
    <?php

        $analysisgridColumns = [
            [
                'attribute'=>'sample_name',
                'header'=>'Sample',
                'format' => 'raw',
                'enableSorting' => false,
                'contentOptions' => ['style' => 'width:10%; white-space: normal;'],
               
            ],
            [
                'attribute'=>'sample_code',
                'header'=>'Sample Code',
                'format' => 'raw',
                'enableSorting' => false,
                'contentOptions' => ['style' => 'width:10%; white-space: normal;'],
            ],
            [
                'attribute'=>'test_name',
                'format' => 'raw',
                'header'=>'Test/ Calibration Requested',
                'contentOptions' => ['style' => 'width: 15%;word-wrap: break-word;white-space:pre-line;'],
                'enableSorting' => false,
            ],
            [
                'attribute'=>'method',
                'format' => 'raw',
                'header'=>'Test Method',
                'enableSorting' => false,  
                'contentOptions' => ['style' => 'width: 50%;word-wrap: break-word;white-space:pre-line;'],
                'pageSummary' => '<span style="float:right";>SUBTOTAL<BR>DISCOUNT<BR><B>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;TOTAL</B></span>',             
            ],
            [
                'attribute'=>'fee',
                'header'=>'Unit Price',
                'enableSorting' => false,
                'hAlign'=>'right',
                'format' => 'raw',
                'value'=>function($data){
                    return number_format($data['analysis_fee'],2);
                },
                'contentOptions' => [
                    'style'=>'max-width:80px; overflow: auto; white-space: normal; word-wrap: break-word;'
                ],
                'hAlign' => 'right', 
                'vAlign' => 'left',
                'width' => '7%',
                'format' => 'raw',
                'pageSummary'=> function () use ($subtotal,$discounted,$total,$countSample) {
                    if($countSample > 0){
                        return  '<div id="subtotal">₱'.number_format($subtotal, 2).'</div><div id="discount">₱'.number_format($discounted, 2).'</div><div id="total"><b>₱'.number_format($total, 2).'</b></div>';
                    } else {
                        return '';
                    }
                },
            ],
        ];
            echo GridView::widget([
                'id' => 'analysis-grid',
                'responsive'=>true,
                'dataProvider'=> $analysisdataprovider,
                'pjax'=>true,
                'pjaxSettings' => [
                    'options' => [
                        'enablePushState' => false,
                    ]
                ],
                'responsive'=>true,
                'striped'=>true,
                'hover'=>true,
                'showPageSummary' => true,
                'hover'=>true,
                
                'panel' => [
                    'heading'=>'<h3 class="panel-title">Analysis</h3>',
                    'type'=>'primary',
                    'before'=> null,
                   'after'=> false,
                   'footer'=>$actionButtonConfirm.$actionButtonSaveLocal,
                ],
                'columns' => $analysisgridColumns,
                'toolbar' => [
                    'content'=> Html::a('<i class="glyphicon glyphicon-repeat"></i> Refresh Grid', [Url::to(['referral/view','id'=>$request['referral_id'],'notice_id'=>$notification['notification_id']])], [
                                'class' => 'btn btn-default', 
                                'title' => 'Refresh Grid'
                            ]),
                ],
            ]);
        ?>
    </div>
</div>
</div>
<script type="text/javascript">
    //$('#sample-grid tbody td').css('cursor', 'pointer');

    function confirmNotification0(url,title){
        $(".modal-title").html(title);
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
    }

    function confirmNotification(url,title){
        var str = title.slice(19);
        var header_title = '';

        if(title.length > 73){
            header_title = title.slice(0, 70) + '...';
        } else {
            header_title = title;
        }

        if(str.length > 0){
            var agency_name = str;
        } else {
            var agency_name = "<span style='font-size:10px;color:#757575;'>...No agency to be displayed...</span>";
        }

        BootstrapDialog.show({
            title: "<span class='glyphicon glyphicon-send'></span>&nbsp;&nbsp;" + header_title,
            message: "<p class='note' style='margin:15px 0 0 15px;font-weight:bold;color:#990000;font-size:14px;'><span class='glyphicon glyphicon-exclamation-sign' style='font-size:17px;'></span> Are you sure you want to accept this referral request from <span class='agency-name' style='color:#000000;'>"+agency_name+"</span>?</p>",
            buttons: [
                {
                    label: 'Accept',
                    cssClass: 'btn-primary',
                    action: function(thisDialog){
                        thisDialog.close();
                        $('.modal-title').html(header_title);
                        $('#modal').modal('show')
                            .find('#modalContent')
                            .load(url);
                    }
                }, 
                {
                    label: 'Close',
                    action: function(thisDialog){
                        thisDialog.close();
                    }
                }
            ]
        });
    }

    function localSave(url,title){
        var header_title = '';
        if(title.length > 73){
            header_title = title.slice(0, 70) + '...';
        } else {
            header_title = title;
        }
        $('.modal-title').html(header_title);
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
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
    background-image: url('/images/img-loader64.gif');
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