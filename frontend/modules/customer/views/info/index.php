<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\Url;
use common\components\Functions;
use yii\bootstrap\Modal;

$func= new Functions();
/* @var $this yii\web\View */
/* @var $searchModel common\models\lab\CustomerSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
// $this->registerJsFile('https://maps.googleapis.com/maps/api/js?key=AIzaSyBkbMSbpiE90ee_Jvcrgbb12VRXZ9tlzIc&libraries=places');
$this->title = "Info";
$this->params['breadcrumbs'][] = ['label' => 'Customer', 'url' => ['/customer']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerJsFile("/js/customer/customer.js");
$display=false;
if(Yii::$app->user->can('allow-create-op')){
    $display=true;
}
?>
<!-- <div id="map" style="width: auto;height: 400px;"></div>  -->
<div class="customer-index">
    <fieldset>
        <legend>Status Legends</legend>
        <div style='padding: 0 10px'>
            <span class='badge legend-font btn-primary' ><span class="glyphicon glyphicon-arrow-up"> </span>  For Syncing</span>
            
            <span class='badge legend-font btn-warning' ><span class="glyphicon glyphicon-pencil"> </span>  For Confirmation</span>

            <span class='badge legend-font btn-success' ><span class="glyphicon glyphicon-cloud"> </span>  Synced</span>
            
        </div>
        <div>
            <i style="font-size: 8pt"><b style="color:#3c8dbc">For Syncing</b> - The record only exist on the local area and is not backed-up yet.
            </i>
            <br>
            <i style="font-size: 8pt"><b style="color:#f39c12">For Confirmation</b> - The record is detected to exist in the cloud storage already and confirmation of the user is needed if the record is the same.</i>
            <br>
            <i style="font-size: 8pt"><b style="color:#00a65a">Synced</b> - The record exists on the cloud storage and the user can use the eulims-app.</i>
        </div>
    </fieldset>
    <div class="table-responsive">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'pjax' => true,
            'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-customer',/*'enablePushState'=>false*/]],
            'panel' => [
                'type' => GridView::TYPE_PRIMARY,
                'heading' => '<span class="glyphicon glyphicon-book"></span>  ' . Html::encode($this->title),
            ],
            'panel' => [
                'type' => GridView::TYPE_PRIMARY,
                'heading' => '<span class="glyphicon glyphicon-book"></span>  ' . Html::encode($this->title),
                'before'=>"<button type='button' onclick='openModal(\"/customer/info/create\",\"Create New Customer\")' class=\"btn btn-success\"><i class=\"fa fa-plus-o\"></i> Create Customer</button>",
            ],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],

                // 'customer_id',
                // 'rstl_id',
                'customer_code',
                'customer_name',
                // 'head',
                //'tel',
                //'fax',
                'email:email',
                'Completeaddress',
                //'latitude',
                //'longitude',
                //'customer_type_id',
                //'business_nature_id',
                //'industrytype_id',
                //'created_at',c
                // [
                //     'header'=>'Status',
                //     'format'=>'raw',
                //     'value'=>function($model){
                //         if($model->sync_status==0){
                //             //for syncing
                //             //return Html::a('<span class="glyphicon glyphicon-arrow-up" title="Sync Info"></span>', ['syncrecord','id'=>$model->customer_id], ['class' => 'btn btn-primary']);

                //             //use Jquery here
                //             $t = '/customer/info/syncrecord?id='.$model->customer_id;
                //             return Html::button('<span class="glyphicon glyphicon-arrow-up"></span>', ['value'=>Url::to($t), 'class' => 'btn btn-primary','title' => Yii::t('app', "Sync Info of ".$model->customer_name),'name' => Yii::t('app', "Sync Info of  <font color='#272727'>[<b>".$model->customer_name."</b>]</font>"),'onclick'=>'openModal(this.value,this.title)']);

                //         }elseif($model->sync_status==2){
                //             //for confirmation
                //             $t = '/customer/info/confirmrecord/?id='.$model->customer_id;
                //             return Html::button('<span class="glyphicon glyphicon-pencil"></span>', ['value'=>Url::to($t), 'class' => 'btn btn-warning','title' => Yii::t('app', "Confirm Info of ".$model->customer_name),'name' => Yii::t('app', "Confirm Info of <font color='#272727'>[<b>".$model->customer_name."</b>]</font>"),'onclick'=>'openModal(this.value,this.title)']);
                //         }else{
                //             //synced 
                //             return Html::button('<span class="glyphicon glyphicon-cloud"></span>', ['class' => 'btn btn-success']);
                //         }
                //     }
                // ],
                ['class' => 'yii\grid\ActionColumn',
                'contentOptions' => ['style' => 'width: 8.7%'],
                'visible'=> Yii::$app->user->isGuest ? false : true,
                'template' => '{view}{update}',
                'buttons'=>[
                    'update'=>function ($url, $model) {
                        $t = '/customer/info/update/?id='.$model->customer_id;
                        return Html::button('<span class="glyphicon glyphicon-pencil"></span>', ['value'=>Url::to($t), 'class' => 'btn btn-success','title' => Yii::t('app', "Update Info of ".Html::encode($model->customer_name)),'name' => Yii::t('app', "Update Info of <font color='#272727'>[<b>".$model->customer_name."</b>]</font>"),'onclick'=>'openModal(this.value,this.title)']);
                        // return Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $model->customer_id], ['class' => 'btn btn-success','target'=>'_']);
                    },
                    'view'=>function ($url, $model) {
                        $t = '/customer/info/view?id='.$model->customer_id;
                        return Html::button('<span class="fa fa-eye"></span>', ['value'=>Url::to($t), 'class' => 'btn btn-primary','title' => Yii::t('app', "View Info of ".Html::encode($model->customer_name)),'name' => Yii::t('app', "View Info of  <font color='#272727'>[<b>".$model->customer_name."</b>]</font>"),'onclick'=>'openModal(this.value,this.title)']);
                    },
                ],
            ],
            ],
        ]); ?>
    </div>

    <script src="https://maps.googleapis.com/maps/api/js?key=<?= \Yii::$app->params['googlekey']?>&libraries=places"></script>
<?php
    Modal::begin([
    'options' => [
        'id' => 'gmodal',
        'tabindex' => false, // important for Select2 to work properly
        'class' => 'modal draggable fade',
    ],
    'header' => '<h4 class="modal-title">New Profile</h4>'
    ]);
    echo "<div>";
    //echo "<div id='modalContent' style='margin-left: 5px; padding-bottom:10px;'><img src='/images/ajax-loader.gif' alt=''/></div>";
   ?>
    <STRONG>Select Location Here</STRONG><br>
    <input id="searchTextField" type="text" size="50"/>
    <div id="output"></div><br /><br />     
    <div id="map" style="width: auto;height: 400px;"></div> 
   <?php
    echo "<div>&nbsp;</div>";
    echo "</div>";
    Modal::end();
    ?>

<?php 
$this->registerJsFile("/js/customer/autocomplete.js");
$this->registerJsFile("/js/customer/google-map-marker.js");
?>

<script type="text/javascript">
    $("#gmodal").on("hidden.bs.modal", function () {
    // put your default event here
 document.getElementById("modal").focus();
});

    function func() {
            // $.pjax({container: '#kv-pjax-container-customer'})
            var url = $('#kv-pjax-container-customer li.active a').attr('href');

            //$.pjax.reload({container:"#kv-pjax-container-customer",url: '/customer/info/',replace:false,timeout: false});

            $.pjax.reload({container:"#kv-pjax-container-customer",url: url,replace:false,timeout: false});
        }

    function openModal(url,title){
        LoadModal(title,url,'true','900px');
    }

    // A $( document ).ready() block.
    $( document ).ready(function() {
        setInterval(func, 60000); // Time in milliseconds

        var classname = document.getElementsByClassName("close");

        for (var i = 0; i < classname.length; i++) {
            classname[i].addEventListener('click', func, false);
        }
    });

</script>
        

<?php
    // This section will allow to popup a notification
    $session = Yii::$app->session;
    if ($session->isActive) {
        $session->open();
        if (isset($session['deletepopup'])) {
            $func->CrudAlert("Deleted Successfully","WARNING");
            unset($session['deletepopup']);
            $session->close();
        }
        if (isset($session['updatepopup'])) {
            $func->CrudAlert("Updated Successfully");
            unset($session['updatepopup']);
            $session->close();
        }
        if (isset($session['savepopup'])) {
            $func->CrudAlert("Saved Successfully","SUCCESS",true);
            unset($session['savepopup']);
            $session->close();
        }
    }
    ?>
</div>


    



