<?php
use yii\helpers\Html;
use yii\widgets\ListView;
use yii\widgets\Pjax;
?>

<div class="notification-view notification-display">
	<div class="alert alert-danger" style="background: #ffc0cb !important;">
        <a href="#" class="close" data-dismiss="alert" >×</a>
        <p class="note" style="color:#d73925"><b>PSTC Notification will not appear here.</b></p>
      </div>
	<div class="alert alert-info" style="border-bottom: 2px solid #555;margin-bottom:2px;">
	  <strong style="color:#000;">List of Referral Notifications</strong>
	</div>
	<?php
	echo ListView::widget([
		'dataProvider' => $notificationProvider,
		'itemOptions' => ['class' => 'item'],
		'itemView' => '_list_item',
		//'viewParams' => ['notifications' => $notifications,'count_notice'=>$count_notice],
		'viewParams' => ['count_notice'=>$count_notice],
		'summary' => false,
		'emptyText' => '<div style="padding:5px;font-size:12px;background-color:#bbb;">No notifications to be displayed.</div>',
		'pager' => [
			'class' => \kop\y2sp\ScrollPager::className(),
			'spinnerSrc' => '/images/img-png-loader-24.png',
			'triggerText' => 'Load more',
			'triggerTemplate' => '<button type="button" class="btn btn-primary btn-xs btn-block" style="font-size:13px;">{text}</button>',
			'noneLeftText' => 'No more notifications left to load.',
			'noneLeftTemplate' => '<button type="button" class="btn btn-default btn-xs btn-block" style="font-size:12px;">{text}</button>',
			'enabledExtensions' => [
				\kop\y2sp\ScrollPager::EXTENSION_TRIGGER,
				\kop\y2sp\ScrollPager::EXTENSION_SPINNER,
				\kop\y2sp\ScrollPager::EXTENSION_NONE_LEFT,
				\kop\y2sp\ScrollPager::EXTENSION_PAGING,
			],
		]
	]);
	?>
</div>

<style type="text/css">
.responded-notice {
  border-bottom: 1px solid #888;
  padding: 4px 15px 4px 15px;
  background: #ebf7e6;
  font-size: 12px;
  color: #444;
}

.unresponded-notice {
  background: #dfdfdf;
  border-bottom: 1px solid #888;
  padding: 4px 15px 4px 15px;
  font-size: 12px;
  color: #444;
}

.notification-display .item :hover {
  background: #fff5d4;
  cursor: pointer;
}

.notification-display .notification-date {
	color: #777;
	font-size: 11px;
}
.notification-display a:link, a:hover, a:active {
	text-decoration: none;
	display: block;
	background-color:none;
	color: #444;
}
</style>

