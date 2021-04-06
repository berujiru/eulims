<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use kartik\grid\Module;
use kartik\daterange\DateRangePicker;
use yii\widgets\ActiveForm;
use kartik\widgets\Select2;
use kartik\grid\DataColumn;
use common\models\referral\Lab;
use arturoliveira\ExcelView;

$this->title = 'Reports';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="accomplishment-report-default-index">
	<div class="panel panel-default">
		<!-- <div class="panel-heading" style="font-weight: bold;font-size: 15px;">Accomplishment Report</div> -->
		<div class="panel-heading" style="font-weight: bold;font-size: 15px;"><span class="glyphicon glyphicon-stats"></span> Accomplishment Report</div>
		<div class="panel-body">
			<?php
	    		$form = ActiveForm::begin([
				    'id' => 'accomplishment-form',
				    'options' => [
						'class' => 'form-horizontal',
						'data-pjax' => true,
					],
					'method' => 'get',
				])
	    	?>
			<!-- <div class="row"> -->
			<div style="width:auto; position: relative;">
				<h6><b>Choose type of accomplishment report to generate</b></h6>
				<div id="gen-report-type" style="float:left; margin-right: 10px;">
					<label class="radio-inline">
				    	<input type="radio" name="report_type" id="all_lab" value="1"> <b>All laboratories </b>
					</label>
					<label class="radio-inline">
						<input type="radio" name="report_type" id="per_lab" value="2"> <b>Per Laboratory</b>
					</label>
					<br>
					<span class="error-report-type text-danger" style="margin-left: 3px;"></span>
				</div>
				<div id="lab-name" style="width:25%;float:left;">
		            <?php
		            	//echo '<label class="control-label">Laboratory </label>';
						echo Select2::widget([
						    'name' => 'lab_id',
						    'id' => 'lab_id',
						    'value' => (!isset($_GET['lab_id'])) ? 1 : (int) $_GET['lab_id'],
						    //'data' => ['1'=>'Chem','2'=>'Micro'],
						    'data' => $laboratories,
						    'theme' => Select2::THEME_KRAJEE,
						    'options' => ['placeholder' => 'Select Laboratory '],
						    'pluginOptions' => [
						        'allowClear' => true,
						        //'disabled' => true,
						    ],
						]);
		            ?>
		            <span class="error-lab text-danger" style="float: left;"></span>
		        </div>
			</div>
	        <div id="date-range" style="float:left; width: 350px;margin-left: 5px;">
				<?php
			        //echo '<label class="control-label">Referral Date </label>';
			        echo '<label class="col-sm-2 control-form-label">Referral Date</label>';
			        //echo 'Referral Date';
					echo '<div class="col-sm-8" style="margin-left:5px;">';
	    			echo '<div class="input-group drp-container">';
	    			//echo 'Referral Date';
					echo DateRangePicker::widget([
					    'name'=>'request_date_range',
					    'id'=>'request_date_range',
					    'value' => (!isset($_GET['request_date_range'])) ? date("Y-m-d")." to ".date("Y-m-d") : $_GET['request_date_range'],
					    'useWithAddon'=>true,
					    'convertFormat'=>true,
					    'startAttribute' => 'from_date',
					    'endAttribute' => 'to_date',
					    'options'=>[
					    	'class' => 'form-control',
				            'style' => 'padding:5px;width:250px;',
				        ],
					    'pluginOptions'=>[
					        'locale'=>[
					        	'format' => 'Y-m-d',
					        	'separator'=>' to ',
					        ],
					    ]
					]);
					echo '</div>';
					echo '</div>';
	    		?>
	    		<span class="error-date text-danger" style="float: left;margin-left: 20px;"></span>
	    	</div>
	    	<div style="float:left;margin-left:5px;">
	    		<button type="button" id="btn-filter" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span> Submit</button>
	    	</div>
			<?php ActiveForm::end(); ?>
		</div>

		<? //= \arturoliveira\ExcelView::widget(); ?>

		    <?php //\yii\widgets\Pjax::begin(); ?>
        	<?php
        		$startDate = Yii::$app->request->get('from_date', date('Y-01-01'));
        		$endDate = Yii::$app->request->get('to_date', date('Y-m-d'));
        		$labId = (int) Yii::$app->request->get('lab_id', 1);
        		$report_type = (int) Yii::$app->request->get('report_type', 2);
        		$labCode = Lab::findOne($labId)->labcode;
                
				$gridColumns = [
	    		    [
			            'attribute'=>'referral_date_time', 
			            'header' => 'Year',
			            //'width'=>'310px',
			            'value'=>function ($model, $key, $index, $widget) {
		                    return Yii::$app->formatter->asDate($model->request_datetime, 'php:Y');
		                },
		                'contentOptions' => ['class' => 'bg-info text-primary','style'=>'font-weight:bold;font-size:15px;'],
			            'group'=>true,  // enable grouping,
			            'groupedRow'=>true,                    // move grouped column to a single grouped row
			            //'groupOddCssClass'=>'kv-grouped-row',  // configure odd group cell css class
			            //'groupEvenCssClass'=>'kv-grouped-row', // configure even group cell css class
			            'groupFooter'=>function ($model, $key, $index, $widget) { // Closure method
			                return [
			                    'mergeColumns'=>[[1]], // columns to merge in summary
			                    'content'=>[             // content to show in each summary cell
			                        1=>'SUB-TOTAL ('.Yii::$app->formatter->asDate($model->request_datetime, 'php:Y').')',
			                        2=>GridView::F_SUM,
			                        3=>GridView::F_SUM,
			                        4=>GridView::F_SUM,
			                        5=>GridView::F_SUM,
			                        6=>GridView::F_SUM,
			                        7=>GridView::F_SUM,
			                        8=>GridView::F_SUM,
			                        9=>GridView::F_SUM,
			                        10=>GridView::F_SUM,
			                        11=>GridView::F_SUM,
			                        12=>GridView::F_SUM,
			                    ],
			                    'contentFormats'=>[      // content reformatting for each summary cell
			                        2=>['format'=>'number', 'decimals'=>0],
			                        3=>['format'=>'number', 'decimals'=>0],
			                        4=>['format'=>'number', 'decimals'=>0],
			                        5=>['format'=>'number', 'decimals'=>0],
			                        6=>['format'=>'number', 'decimals'=>0],
			                        7=>['format'=>'number', 'decimals'=>0],
			                        8=>['format'=>'number', 'decimals'=>0],
			                        9=>['format'=>'number', 'decimals'=>2],
			                        10=>['format'=>'number', 'decimals'=>2],
			                        11=>['format'=>'number', 'decimals'=>2],
			                        12=>['format'=>'number', 'decimals'=>2],
			                    ],
			                    'contentOptions'=>[      // content html attributes for each summary cell
			                        1=>['style'=>'font-variant:small-caps'],
			                        2=>['style'=>'text-align:center'],
			                        3=>['style'=>'text-align:center'],
			                        4=>['style'=>'text-align:center'],
			                        5=>['style'=>'text-align:center'],
			                        6=>['style'=>'text-align:center'],
			                        7=>['style'=>'text-align:center'],
			                        8=>['style'=>'text-align:center'],
			                        9=>['style'=>'text-align:right'],
			                        10=>['style'=>'text-align:right'],
			                        11=>['style'=>'text-align:right'],
			                        12=>['style'=>'text-align:right'],
			                    ],
			                    // html attributes for group summary row
			                    'options'=>['class'=>'text-success bg-warning']
			                ];
			            }
			        ],
			        ['class' => 'kartik\grid\ActionColumn',
			    		'header'=>'Details',
						'contentOptions' => ['style' => 'width: 8.7%'],
						'template' => '{view}',
						'buttons'=>[
							'view'=>function ($url, $model) use($lab_id) {
								return Html::button('<span class="glyphicon glyphicon-print"></span>', ['value'=>Url::to(['/lab/tagging/referralmonthlyreport','month'=>Yii::$app->formatter->asDate($model->request_datetime, 'php:M'), 'year'=>Yii::$app->formatter->asDate($model->request_datetime, 'php:Y'), 'lab_id' => $lab_id]), 'class' => 'btn btn-primary','onclick'=>'LoadModal(this.title, this.value ,true, 1850);','title' => Yii::t('app', "Referral Monthly Report")]);
							},
						],
					],
		            [
		                'attribute'=>'referral_date_time',
		                'header' => 'Month',
		                'value'=>function ($model, $key, $index, $widget) {
		                    return strtoupper(Yii::$app->formatter->asDate($model->request_datetime, 'php:M'));
		                },
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
		                'pageSummary'=>'GRAND TOTAL',
		                'pageSummaryOptions'=>['class'=>'text-left text-primary bg-success'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of referral request sent to TCL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {

			            	return $model->getreferralsenttotcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of samples referred to TCL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   return $model->getsamplessenttotcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of tests referred to TCL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   return $model->gettestsenttotcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of referral request received from RL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   return $model->getreferralserveastcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of customers served from referral as TCL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                    return $model->getreferralserveastcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of samples received from RL',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   return $model->getsamplesserveastcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'No. of tests conducted from referred sample',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-center'],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   return $model->gettestserveastcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-center text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'Income Generated (Actual Fees Collected)',
		                'headerOptions' => ['class' => 'text-center','style'=>'width:15%; vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-right'],
			            'format'=>['decimal', 2],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                  return $model->getreferraltotal($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'Value of Assistance (Gratis)',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-right'],
			            'format'=>['decimal', 2],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   return $model->getgratisastcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
		            ],
		            [
		                //'attribute'=>'request_ref_num',
		                'header' => 'Value of Assistance (Discount)',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-right'],
			            'format'=>['decimal', 2],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
		                   return $model->getdiscountastcl($labId,$model->request_datetime);
		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
		            ],
		            [
		            	//'attribute'=>'request_ref_num',
		                'header' => 'Gross (Fees Collected)',
		                'headerOptions' => ['class' => 'text-center','style'=>'vertical-align: middle;'],
			            'contentOptions' => ['class' => 'text-right'],
			            'format'=>['decimal', 2],
			            'value'=>function ($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$report_type) {
			            	return $model->getgrossastcl($labId,$model->request_datetime);

		                },
		                'pageSummary'=>true,
        				'pageSummaryFunc'=>GridView::F_SUM,
        				'pageSummaryOptions'=>['class'=>'text-right text-primary'],
		            ],
			    ];

				    echo GridView::widget([
				    	'id' => 'accomplishment-report',
				        'dataProvider'=>$dataProvider,
				        //'filterModel'=>$searchModel,
				        'showPageSummary'=>true,
				        'summary' => false,
				        'pjax'=>true,
		                'pjaxSettings' => [
		                    'options' => [
		                        'enablePushState' => false,
		                    ]
		                ],
		                'responsive'=>true,
				        'striped'=>false,
				        'hover'=>true,
				        'panel' => [
		                    'heading'=>'<h3 class="panel-title"><i class="glyphicon glyphicon-stats"></i> Report Summary</h3>',
		                    'type'=>'primary',
		                    'after'=>false,
		                    //'before'=>$exportMenu,
		                    //'headerOptions' => ['class' => 'text-center'],
		                ],
				         'exportConfig' => [
					    	GridView::PDF => [
				                'filename' => $labCode.'-Referral Accomplishment_Report',
				                'alertMsg'        => 'The PDF export file will be generated for download.',
				                'config' => [
				                   // 'methods' => [
				                   //     'SetHeader' => [$pdfHeader],
				                   //     'SetFooter' => [$pdfFooter]
				                   // ],
				                    'options' => [
				                        'title' => 'Accomplishment Report',
				                        'subject' => 'Accomplishment_Report',
				                        'keywords' => 'pdf, preceptors, export, other, keywords, here'
				                    ],
				                ]
				            ],
							  GridView::EXCEL => [
				                'label'           => 'Excel',
				                'iconOptions'     => ['class' => 'text-success'],
				                'showHeader'      => TRUE,
				                'showPageSummary' => TRUE,
				                'showFooter'      => TRUE,
				                'showCaption'     => TRUE,
				                'filename'        =>  $labCode.'-Accomplishment_Report',
				                'alertMsg'        => 'The EXCEL export file will be generated for download.',
				                'options'         => ['title' => 'Department of Science OneLab'],
				                'mime'            => 'application/vnd.ms-excel',
				                'config'          => [
				                    'worksheet' => 'Accomplishment',
				                    'cssFile'   => ''
				                ]
				            ],
						],
				        'columns'=> $gridColumns,
				        'toolbar' => [
				        	[
		                		'content' => Html::button('<i class="glyphicon glyphicon-repeat"></i> Reset Grid', ['title'=>'Reset Grid', 'onclick'=>'reloadGrid()', 'class' => 'btn btn-default'])
		                	],
		                	'{export}',
		                ],
	                /*'autoXlFormat'=>true,
	                'export'=>[
	                	'label' => 'Export',
				        'fontAwesome'=>true,
				        'showConfirmAlert'=>false,
				        'target'=>GridView::TARGET_SELF,
				    ],
				    'tableOptions'=>['id'=>'myTable'],*/
				    ]);
        	?>
        	<?php //\yii\widgets\Pjax::end(); ?>
    </div>

</div>
<script type="text/javascript">

    function reloadGrid(){
    	var lab_id = 1;
		var fromdate = <?= "'".date('Y-01-01')."'" ?>;
		var todate = <?= "'".date('Y-m-d')."'" ?>;
		var report_type = null;
		$("#lab_id").val(lab_id).trigger('change');
		$('#request_date_range-start').val(fromdate).trigger('change');
		$('#request_date_range-end').val(todate).trigger('change');
		$('#request_date_range').val(fromdate+' to '+todate);
		$.pjax.reload({container:"#accomplishment-report-pjax",url: '/reports/referral/accomplishmentcro?lab_id='+lab_id+'&from_date='+fromdate+'&to_date='+todate+'&report_type='+report_type,replace:false,timeout: false});
    }


    //if($("input[name='report_type']").is(":checked")){
    $("input[name='report_type']").on('click',function(event){
    	if($("#all_lab").is(":checked") || !$("#per_lab").is(":checked")){
			//$('#lab-name').addClass('has-error');
			//$('#lab_id').prop('disabled', true);
    		//$('#lab_id').attr('disabled', true);
    		$('.select2').addClass('select2-container--disabled');
    		//select2-hidden-accessible
    		//style="display:none"
		} else {
			$('.select2').removeClass('select2-container--disabled');
		}
    });

    /*$('#all_lab').on('click',function(event){
    	$('.select2').addClass('select2-container--disabled');
    });

    $('#per_lab').on('click',function(event){

    });*/

	$('#btn-filter').on('click',function(event){
	    event.preventDefault();
	    event.stopImmediatePropagation();

	    if(!$("#per_lab").is(":checked") && !$("#all_lab").is(":checked"))
	    {
	    	$('#gen-report-type').addClass('has-error');
			$(".error-report-type").html("Select report type to generate").fadeIn('fast').fadeOut(2000);
			//$("a.export").attr("onclick","reminderMsg()");
			//$("a.export").attr("href","javascript:void(0)");
		}
	    else if($('#lab_id').val() == '')
	    {
			$('#lab-name').addClass('has-error');
			$('.error-lab').html('Please select laboratory.').fadeIn('fast').fadeOut(2000);
		} 
		else if ($('#request_date_range').val() == '')
		{
			$('#date-range').addClass('has-error');
			$('.error-date').html('Please specify date range.').fadeIn('fast').fadeOut(2000);
		} 
		else {
			$('#lab-name').removeClass('has-error');
			$('#date_range').removeClass('has-error');
			$('#gen-report-type').removeClass('has-error');
			$('.error-lab').html('').fadeOut('fast');
			$('.error-date').html('').fadeOut('fast');
			$('.error-report-type').html('').fadeOut('fast');

			var lab_id = $('#lab_id').val();
        	var fromdate = $('#request_date_range-start').val();
        	var todate = $('#request_date_range-end').val();
        	var report_type = $("input[name='report_type']:checked").val();
        	$.pjax.reload({container:"#accomplishment-report-pjax",url: '/reports/referral/accomplishmentcro?lab_id='+lab_id+'&from_date='+fromdate+'&to_date='+todate+'&report_type='+report_type,replace:false,timeout: false});	
		}
	});
</script>