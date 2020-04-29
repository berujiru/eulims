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
<script type="text/javascript">
    $(document).ready(function(){
        $("a.thismessage").on('click',function (ee) {
            var id=($(this).attr("id"));
            //alert(id);
            document.getElementById("senderid").value=id;
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


<a class="thismessage" id="<?=$model->sender_userid?>">
<li class="clearfix">
<img src="/images/icons/customer.png" alt="avatar" style="width: 40px"/>
    <div class="about">
<?php
                    echo "<div class='name'>";
                    echo Html::encode($model->getProfile($model->sender_userid)->fullname);
                    echo "</div>";
                    echo "<div class='status'>";
                            if ($model->status_id == 1) {
                                echo "<i class='fa fa-circle offline'></i>";
                            }
                    echo StringHelper::truncateWords(($model->message), 5);
                    echo "</div>";
?>
    </div>
</li>
</a>

