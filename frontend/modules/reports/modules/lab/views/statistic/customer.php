<?php

use yii\helpers\Html;
//use yii\grid\GridView;
//use yii\bootstrap\Modal;
use yii\helpers\Url;
use kartik\grid\GridView;
use common\models\lab\Lab;
use kartik\grid\Module;
use kartik\daterange\DateRangePicker;
use yii\widgets\ActiveForm;
use kartik\widgets\Select2;
use kartik\export\ExportMenu;
use kartik\grid\DataColumn;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Customer Served';
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="samplestat-index">
<div class="panel panel-default col-xs-12">
        <div class="panel-heading"><i class="fa fa-adn"></i> </div>
        <div class="panel-body">
    	<?php
    		$form = ActiveForm::begin([
			    'id' => 'customer-serve-form',
			    'options' => [
					'class' => 'form-horizontal',
					//'data-pjax' => true,
				],
				'method' => 'get',
			])
    	?>
    		<div class="row">
		         <div id="business-nature" style="width:20%;position: relative; float:left;margin-right: 20px;">
		            <?php
		            	echo Html::radio('filtertype', false, ['label' => 'Business Nature','id'=>'business','value'=>1]);
		            	//echo '<label class="control-label">Business Nature </label>';
						echo Select2::widget([
						    'name' => 'businessnature_id',
						    'id' => 'businessnature_id',
						    //'value' => (!isset($_GET['businessnature_id'])) ? 1 : (int) $_GET['businessnature_id'],
						    'data' => $businessnature,
						    'theme' => Select2::THEME_KRAJEE,
						    'options' => ['placeholder' => 'Select Business Nature','disabled'=>true],
						    'pluginOptions' => [
						        'allowClear' => true,
						    ],
						]);
		            ?>
		            <span class="error-business text-danger" style="position:fixed;"></span>
		         </div>
		         <div id="industry-type" style="width:20%;position: relative; float:left;margin-right: 20px;">
		            <?php
		            	echo Html::radio('filtertype', false, ['label' => 'Industry Type','id'=>'industry','value'=>2]);
		            	//echo '<label class="control-label">Industry Type </label>';
						echo Select2::widget([
						    'name' => 'industrytype_id',
						    'id' => 'industrytype_id',
						    //'value' => (!isset($_GET['industrytype_id'])) ? 1 : (int) $_GET['industrytype_id'],
						    'data' => $industrytype,
						    'theme' => Select2::THEME_KRAJEE,
						    'options' => ['placeholder' => 'Select Industry Type','disabled'=>true],
						    'pluginOptions' => [
						        'allowClear' => true,
						    ],
						]);
		            ?>
		            <span class="error-industry text-danger" style="position:fixed;"></span>
		         </div>
		         <div id="lab-name" style="width:20%;position: relative; float:left;margin-right: 20px;">
		            <?php
		            	//echo Html::radio('filtertype', true, ['label' => 'Laboratory','id'=>'lab','value'=>1]);
		            	echo '<label class="control-label">Laboratory </label>';
						echo Select2::widget([
						    'name' => 'lab_id',
						    'id' => 'lab_id',
						    'value' => (!isset($_GET['lab_id'])) ? 1 : (int) $_GET['lab_id'],
						    'data' => $laboratories,
						    'theme' => Select2::THEME_KRAJEE,
						    'options' => ['placeholder' => 'Select Laboratory '],
						    'pluginOptions' => [
						        'allowClear' => true,
						    ],
						]);
		            ?>
		            <span class="error-lab text-danger" style="position:fixed;"></span>
		         </div>
		         <div id="date_range" style="position: relative; float: left;margin-left: 5px;">
    				<?php
				        echo '<label class="control-label">Request Date </label>';
		    			echo '<div class="input-group drp-container">';
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
					            'style' => 'padding:5px;width:200px;',
					        ],
						    'pluginOptions'=>[
						        'locale'=>[
						        	'format' => 'Y-m-d',
						        	'separator'=>' to ',
						        ],
						    ]
						]);
						echo '</div>';
		    		?>
		    		<span class="error-date text-danger" style="position:fixed;"></span>
		    	</div>
		    	 <div style="width:15%;position: relative; float:left;margin: 27px 0 0 10px;">
		    		<!-- <button type="button" id="btn-filter" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span> Filter</button> -->
		    		<?= Html::button('<span class="glyphicon glyphicon-search"></span> Filter', ['title'=>'Filter Result', 'id'=>'btn-filter', 'class' => 'btn btn-primary']) ?>
		    		<?= Html::resetButton('<i class="glyphicon glyphicon-repeat"></i> Reset', ['title'=>'Reset Form', 'class' => 'btn btn-default', 'onclick'=>'reloadGrid()']) ?>
		    	</div>
		    </div>
		    <?php ActiveForm::end(); ?>
		    <br>
		    <div class="row">
		    <?php //\yii\widgets\Pjax::begin(); ?>
        	<?php
        		$startDate = Yii::$app->request->get('from_date', date('Y-01-01'));
        		$endDate = Yii::$app->request->get('to_date', date('Y-m-d'));
        		$labId = (int) Yii::$app->request->get('lab_id', 1);
        		$filtertype = (int) Yii::$app->request->get('filtertype', 0);
        		//$filterId = (int) Yii::$app->request->get('lab_id', 1);
        		if($filtertype == 1){
        			$filterId = (int) Yii::$app->request->get('businessnature_id');
        		} elseif($filtertype == 2){
        			$filterId = (int) Yii::$app->request->get('industrytype_id');
        		} else {
        			$filterId = 0;
        		}
        		//$filterId = 0;
        		//echo isset($_GET['filtertype']) ? $filtertype = $_GET['filtertype'] : $filtertype = 0;
        		$gridColumns = [
			        //['class'=>'kartik\grid\SerialColumn'],
			        [
			            'label' => 'Customer Code',
			            'format' => 'raw',
			            'value' => function($model, $key, $index, $widget){
			            	return $model->customer_code;
			            },
			            'headerOptions' => ['class' => 'text-center'],
			            'contentOptions' => ['class' => 'text-center'],
			        ],
			        [
			            'label' => 'Customer Name',
			            'format' => 'raw',
			            'value' => function($model, $key, $index, $widget){
			            	return $model->customer_name;
			            },
			            'headerOptions' => ['class' => 'text-center'],
			            'contentOptions' => ['class' => 'text-center'],
			        ],
			        [
			        	'label' => 'Address',
			        	'format' => 'raw',
			        	'value' => function($model, $key, $index, $widget) {
			            	return $model->address;
							/* if($model->barangay_id > 0){
								return $model->completeaddress;
							} else {
								return $model->address;
							} */
			            },
			            'headerOptions' => ['class' => 'text-center'],
			            'contentOptions' => ['class' => 'text-center'],
			        ],
			        [
			        	'label' => 'Contact Details',
			        	'format' => 'raw',
			        	'value' => function($model, $key, $index, $widget) {
			            	return $model->tel;
			            },
			            'headerOptions' => ['class' => 'text-center'],
			            'contentOptions' => ['class' => 'text-center'],
			        ],
			        [
			            'label' => 'No. of Requests',
			            'format' => 'raw',
			            'value' => function($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$filtertype,$filterId){
			            	$countRequest = Yii::$app->formatter->format($model->statCustomerServed($labId,$model->customer_id,$startDate,$endDate,1,$filtertype,$filterId,1),['decimal',0]);
			            	return ($countRequest > 0) ? $countRequest : 0;
			            	//return 1;
			            },
			            'headerOptions' => ['class' => 'text-center'],
			            'contentOptions' => ['class' => 'text-center'],
			        ],
			        [
			            'label' => 'No. of Samples',
			            'format' => 'raw',
			            'value' => function($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$filtertype,$filterId){
			            	$countSample = Yii::$app->formatter->format($model->statCustomerServed($labId,$model->customer_id,$startDate,$endDate,2,$filtertype,$filterId,1),['decimal',0]);
			            	return ($countSample > 0) ? $countSample : 0;
			            },
			            'headerOptions' => ['class' => 'text-center'],
			            'contentOptions' => ['class' => 'text-center'],
			        ],
			        [
			            'label' => 'No. of Testing Services Availed',
			            'format' => 'raw',
			            'value' => function($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$filtertype,$filterId){
			            	$countTest = Yii::$app->formatter->format($model->statCustomerServed($labId,$model->customer_id,$startDate,$endDate,3,$filtertype,$filterId,1),['decimal',0]);
			            	return ($countTest > 0) ? $countTest : 0;
			            },
			            'headerOptions' => ['class' => 'text-center'],
			            'contentOptions' => ['class' => 'text-center'],
			        ],
			        [
			            'label' => 'Total Income Generated (PHP)',
			            'format' => 'raw',
			            'value' => function($model, $key, $index, $widget) use ($labId, $startDate,$endDate,$filtertype,$filterId){
			            	$totalIncome = Yii::$app->formatter->format($model->statCustomerServed($labId,$model->customer_id,$startDate,$endDate,4,$filtertype,$filterId,1),['decimal',2]);
			            	return ($totalIncome > 0) ? $totalIncome : 0;
			            },
			            'headerOptions' => ['class' => 'text-center'],
			            'contentOptions' => ['class' => 'text-center'],
			        ],
			    ];
        		echo GridView::widget([
        			'id' => 'customer-serve',
				    'dataProvider' => $dataProvider,
				    'summary' => false,
					//'columns' => $gridColumns,
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
	                    'heading'=>'<h3 class="panel-title"><i class="glyphicon glyphicon-book"></i> Customer Served</h3>',
	                    'type'=>'primary',
	                    'after'=>false,
	                    'before'=>'<span class="text-danger"><h5>Note: Click to view request(s) per customer.</h5></span>',
	                    //'before'=>$exportMenu,
	                    //'headerOptions' => ['class' => 'text-center'],
	                ],
	               'exportConfig' => [
				    	//GridView::CSV => [],
				    	//GridView::HTML => [],
				   		GridView::PDF => [],
				    	//GridView::EXCEL => [],
				        GridView::EXCEL => [],
				    ],
					'columns' => $gridColumns,
				    'toolbar' => [
	                    //'content'=> Html::button('<i class="glyphicon glyphicon-repeat"></i> Reset', ['value' => Url::to(['statistic/samples']),'class' => 'btn btn-default', 'title' => 'Reset Grid']),
	                 //    [
	                 //    'content' => Html::button('<i class="glyphicon glyphicon-repeat"></i> Reset Grid', ['title'=>'Reset Grid', 'onclick'=>'reloadGrid()', 'class' => 'btn btn-default'])
	                	// ],
	                	'{export}',
	                ],
	                'autoXlFormat'=>true,
	                'export'=>[
	                	'label' => 'Export',
				        'fontAwesome'=>true,
				        'showConfirmAlert'=>false,
				        'target'=>GridView::TARGET_SELF,
				    ],
				    // 'rowOptions' => function ($model, $key, $index, $grid) {
        // 				return ['data-id' => $model->id];
   					// },
   					'rowOptions' => function ($model, $key, $index, $grid) use ($labId, $startDate,$endDate,$filtertype,$filterId) {
	                    return [
	                        //'id' => $model->sample_id,
	                        'id' => $model->customer_id,
	                        //'id' => $data['request_id'],
	                        //'onclick' => 'alert(this.id);',
	                        'onclick' => 'viewRequest('.$model->customer_id.','.$labId.',"'.$startDate.'","'.$endDate.'",'.$filtertype.','.$filterId.');',
	                        'style' => 'cursor:pointer;',
	                        'title' => 'Click to View Requests',
	                        //'onclick' => 'updateSample(this.id,this.request_id);',
	                        // [
	                        //     'data-id' => $model->sample_id,
	                        //     'data-request_id' => $model->request_id
	                        // ],
	                    ];
	                },
				]);
        	?>
        	<?php //\yii\widgets\Pjax::end(); ?>
        	</div>
        </div>
</div>
</div>
<script type="text/javascript">
	//$('#customer-serve tbody td').css('cursor', 'pointer');

    function reloadGrid(){
    	var lab_id = 1;
		var fromdate = <?= "'".date('Y-01-01')."'" ?>;
		var todate = <?= "'".date('Y-m-d')."'" ?>;
		$("#lab_id").val(lab_id).trigger('change');
		$("#businessnature_id").val(0).trigger('change');
		$("#industrytype_id").val(0).trigger('change');
		$("#lab_id").val(lab_id).trigger('change');
		$('#request_date_range-start').val(fromdate).trigger('change');
		$('#request_date_range-end').val(todate).trigger('change');
		$('#request_date_range').val(fromdate+' to '+todate);
		var businessnature = $('#businessnature_id').val();
		var industrytype = $('#industrytype_id').val();
		var filtertype = $('input[name="filtertype"]:checked').val();
		$(':radio').each(function () {
			$(this).removeAttr('checked');
			$('input[type="radio"]').prop('checked', false);
			$("#businessnature_id").attr('disabled', true);
			$("#industrytype_id").attr('disabled', true);
		});
		$.pjax.reload({container:"#customer-serve-pjax",url: '/reports/lab/statistic/customers?filtertype='+filtertype+'&lab_id='+lab_id+'&from_date='+fromdate+'&to_date='+todate+'&businessnature_id='+businessnature+'&industrytype_id='+industrytype,replace:false,timeout: false});
    }

	$('#btn-filter').on('click',function(event){
	    event.preventDefault();
	    event.stopImmediatePropagation();

	    //validateInput();

	  //   	if($('#business').is(':checked') && $('#businessnature_id').val() == ''){
		 //    	$('#business-nature').addClass('has-error');
			// 	$('.error-business').html('Please select business nature.').fadeIn('fast').fadeOut(3000);
			// }
			// if($('#industry').is(':checked') && $('#industrytype_id').val() == ''){
			// 	$('#industry-type').addClass('has-error');
			// 	$('.error-industry').html('Please select industry type.').fadeIn('fast').fadeOut(3000);
			// }
			// if($('#lab_id').val() == ''){
			// 	$('#lab-name').addClass('has-error');
			// 	$('.error-lab').html('Please select laboratory.').fadeIn('fast').fadeOut(3000);
			// }
			// if($('#request_date_range').val() == ''){
			// 	$('#date_range').addClass('has-error');
			// 	$('.error-date').html('Please specify date range.').fadeIn('fast').fadeOut(3000);
			// }

		if(($('#business').is(':checked') && $('#businessnature_id').val() == '') || ($('#industry').is(':checked') && $('#industrytype_id').val() == '') || ($('#lab_id').val() == '') || ($('#request_date_range').val() == '')){
			//return false;
			validateInput();
		}
		else {
			$('#lab-name').removeClass('has-error');
			$('#date_range').removeClass('has-error');
			$('#business-nature').removeClass('has-error');
			$('#industry-type').removeClass('has-error');
			$('.error-lab').html('');
			$('.error-date').html('');
			$('.error-business').html('');
			$('.error-industry').html('');

			$.get('/reports/lab/statistic/customers', {
		        data : $('form').serialize(),
			    }, function(response){
		    	<?php if(Yii::$app->session->hasFlash('error')): ?>
	            	echo Yii::$app->session->getFlash('error');
	            <?php else: ?>
	            	var lab_id = $('#lab_id').val();
	            	var fromdate = $('#request_date_range-start').val();
	            	var todate = $('#request_date_range-end').val();
	            	var businessnature = $('#businessnature_id').val();
					var industrytype = $('#industrytype_id').val();
					var filtertype = $('input[name="filtertype"]:checked').val();
	            	$.pjax.reload({container:"#customer-serve-pjax",url: '/reports/lab/statistic/customers?filtertype='+filtertype+'&lab_id='+lab_id+'&from_date='+fromdate+'&to_date='+todate+'&businessnature_id='+businessnature+'&industrytype_id='+industrytype,replace:false,timeout: false});
	            <?php 
	        		endif;
	            	Yii::$app->session->setFlash('error', null);
		        ?>
		    });
		}
	});

	function validateInput(){
		if($('#business').is(':checked') && $('#businessnature_id').val() == ''){
	    	$('#business-nature').addClass('has-error');
			$('.error-business').html('Please select business nature.').fadeIn('fast').fadeOut(3000);
		}
		if($('#industry').is(':checked') && $('#industrytype_id').val() == ''){
			$('#industry-type').addClass('has-error');
			$('.error-industry').html('Please select industry type.').fadeIn('fast').fadeOut(3000);
		}
		if($('#lab_id').val() == ''){
			$('#lab-name').addClass('has-error');
			$('.error-lab').html('Please select laboratory.').fadeIn('fast').fadeOut(3000);
		}
		if($('#request_date_range').val() == ''){
			$('#date_range').addClass('has-error');
			$('.error-date').html('Please specify date range.').fadeIn('fast').fadeOut(3000);
		}
	}

	$("#business").on('click',function() {
		//validateInput();
		//e.preventDefault();
		//validateInput();
		$("#industrytype_id").val(0).trigger('change');
		$("#businessnature_id").val(1).trigger('change');
		$("#businessnature_id").attr('disabled', false);
		$("#industrytype_id").attr('disabled', true);
		$('#industry-type').removeClass('has-error');
		//validateInput();
		//$('#business').attr('checked', true);
	});

	$("#industry").on('click',function() {
		//validateInput();
		//e.preventDefault();
		//validateInput();
		$("#businessnature_id").val(0).trigger('change');
		$("#industrytype_id").val(1).trigger('change');
		//$('#industry').attr('checked', true);
		$("#industrytype_id").attr('disabled', false);
		$("#businessnature_id").attr('disabled', true);
		$('#business-nature').removeClass('has-error');
		//validateInput();
	});

	function viewRequest(id,labId,startDate,endDate,filtertype,filterId){
		var url = '/reports/lab/statistic/viewrequests?customerId='+id+'&labId='+labId+'&from_date='+startDate+'&to_date='+endDate+'&filtertype='+filtertype+'&filterId='+filterId;
        $('.modal-title').html('View Customer Request');
        $('#modal').modal('show')
            .find('#modalContent')
            .load(url);
	}
</script>