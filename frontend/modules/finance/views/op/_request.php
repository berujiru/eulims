<?php
use kartik\grid\GridView;
use yii\helpers\Html;
use frontend\modules\finance\components\models\Ext_Request;
$js=<<<SCRIPT
   $(".kv-row-checkbox").click(function(){
        settotal();
   });    
   $(".select-on-check-all").change(function(){
        settotal();
   });
  
SCRIPT;
$this->registerJs($js);

?>

 <?php 
    $gridColumn = [
        [
            'class' => '\kartik\grid\SerialColumn',
            
         ],
       
         [
             'class' => '\kartik\grid\CheckboxColumn',
         ],
        [
          'attribute'=>'request_ref_num',
          'enableSorting' => false,
        ],
       
        [
            'attribute'=>'request_datetime',
             'value' => function($model) {
                    return date($model->request_datetime);
                },
            'pageSummary' => '<span style="float:right;">Total</span>',
            'enableSorting' => false,
        ],
        [
            'attribute'=>'total',
            'enableSorting' => false,
            'contentOptions' => [
                'style'=>'max-width:80px; overflow: auto; white-space: normal; word-wrap: break-word;'
            ],
            'value' => function($model) {
                 $request= Ext_Request::find()->where(['request_id' => $model->request_id])->one();
                 $total=$request['total'];
                 return $model->getBalance($model->request_id,$total);
            },
            'hAlign' => 'right', 
            'vAlign' => 'middle',
            'width' => '7%',
            'format' => ['decimal', 2],
            'pageSummary' => '<span id="total">0.00</span>',
             
        ],
         /*[
            'attribute'=>'selected_request',
            'pageSummary' => '<span style="float:right;">Total</span>',
        ],*/
      
        
    ];
?>    

	   
	<?= GridView::widget([
        'dataProvider' => $dataProvider,
        'id'=>'grid',
        'pjax'=>true,
        'containerOptions'=> ["style"  => 'overflow:auto;height:300px'],
        'pjaxSettings' => [
            'options' => [
                'enablePushState' => false,
            ]
        ],
        
        'responsive'=>false,
        'striped'=>true,
        'hover'=>true,
      
        'floatHeaderOptions' => ['scrollingTop' => true],
        'panel' => [
            'heading'=>'<h3 class="panel-title">Request</h3>',
            'type'=>'primary',

         ],
       
         'columns' =>$gridColumn,
         'showPageSummary' => true,
         'toolbar'=> [],
         /*'afterFooter'=>[
             'columns'=>[
                 'content'=>'Total Selected'
             ],
             
         ],*/
    ]); ?>
<script type="text/javascript">
    function settotal(){
        
        //
        var dkeys=$("#grid").yiiGridView("getSelectedRows");
        $("#ext_billing-opids").val(dkeys);
        var SearchFieldsTable = $(".kv-grid-table>tbody");
        var trows = SearchFieldsTable[0].rows;
        var Total=0.00;
        var amt=0.00;
        $.each(trows, function (index, row) {
            var data_key=$(row).attr("data-key");
            for (i = 0; i < dkeys.length; i++) { 
                if(data_key==dkeys[i]){
                    amt=StringToFloat(trows[index].cells[4].innerHTML);
                    Total=Total+parseFloat(amt);
                }
            }
        }); 
       
        var keylist= dkeys.join();
        $("#op-requestids").val(keylist);
     
        var tot=parseFloat(Total);
        var total=CurrencyFormat(tot,2);
        $('#total').html(total);
         var payment_mode=$('#op-payment_mode_id').val()
        if(payment_mode==4){

            wallet=parseInt($('#wallet').val());
            totalVal = parseFloat($('#total').html().replace(/[^0-9-.]/g, ''));
            if( totalVal > wallet) {
              alert("Insufficient customer wallet");
              $('#op-purpose').prop('disabled', true);
              $('#createOP').prop('disabled', true);

            }
            else{
                if(total !== 0){
                    $('#op-purpose').prop('disabled', false);
                    $('#createOP').prop('disabled', false);
                }

            }
        }
         
    }
    
</script>