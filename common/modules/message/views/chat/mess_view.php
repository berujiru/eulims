<?php
use yii\helpers\Html;
use \yii\helpers\StringHelper;
use \yii\helpers\Url;
use yii\widgets\DetailView;
/**
 * Created by PhpStorm.
 * User: OneLab
 * Date: 16/04/2020
 * Time: 21:52
 */
/** @var $model common\modules\message\models\Chat */
/* @var $searchModel common\modules\message\models\ChatSearch */
?>
<li>
<a class="thismessage" id="<?=$model->sender_userid?>">
<?php

                        echo "<i class='fa fa-' style='display:none;width: 0px; height: 15px'></i>";
                        echo "<span>";
                        if ($model->status_id == 1) {
                            echo "<img src='/images/icons/red.png' style='width: 5px'>";
                        }
                        echo "<span><b>";
                        echo Html::encode($model->getProfile($model->sender_userid)->fullname);
                        echo "</span></b><br>";
                        echo StringHelper::truncateWords(($model->message), 5);
                        echo "</span><br><br>";


?>
    </a>
</li>
<script type="text/javascript">
$(document).ready(function(){
   $("a.thismessage").on('click',function (ee) {
	var id=($(this).attr("id"));
	//alert(id);
		
	 $.ajax({
		url: '/message/chat/getsendermessage',
		//dataType: 'json',
		method: 'GET',
		data: {id:id},
		success: function (data, textStatus, jqXHR) {
		   $('#idconvo').html(data);
		}
	});
	
    });
	
	
   
});
</script>
