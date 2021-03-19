 <?php
use common\models\system\User;
use common\models\system\Package;
use common\models\system\PackageDetails;
use yii\helpers\Url;
use yii\helpers\Html;
$unseen = '';
$Packages= Package::find()->all();

$Request_URI=$_SERVER['REQUEST_URI'];

if($Request_URI=='/'){//alias ex: http://admin.eulims.local
    $Backend_URI=Url::base();//Yii::$app->urlManagerBackend->createUrl('/');
    $Backend_URI=$Backend_URI."/uploads/user/photo/";
}else{//http://localhost/eulims/backend/web
    $Backend_URI=Url::base().'/uploads/user/photo/';
}
Yii::$app->params['uploadUrl']=\Yii::$app->getModule("profile")->assetsUrl."\photo\\";
if(Yii::$app->user->isGuest){
    $CurrentUserName="Visitor";
    $CurrentUserAvatar=Yii::$app->params['uploadUrl'] . 'no-image.png';
    $CurrentUserDesignation='Guest';
    $UsernameDesignation=$CurrentUserName;
	$unresponded = '';
	$unseen = '';
}else{
    $CurrentUser= User::findOne(['user_id'=> Yii::$app->user->identity->user_id]);
    $CurrentUserName=$CurrentUser->profile ? $CurrentUser->profile->fullname : $CurrentUser->username;
    if($CurrentUser->profile){
        $CurrentUserAvatar=!$CurrentUser->profile->getImageUrl()=="" ? Yii::$app->params['uploadUrl'].$CurrentUser->profile->getImageUrl() : Yii::$app->params['uploadUrl'] . 'no-image.png';
    }else{
        $CurrentUserAvatar=Yii::$app->params['uploadUrl'] . 'no-image.png';
    }
    $CurrentUserDesignation=$CurrentUser->profile ? $CurrentUser->profile->designation : '';
    if($CurrentUserDesignation==''){
       $UsernameDesignation=$CurrentUserName;
    }else{
       $UsernameDesignation=$CurrentUserName.'<br>'.$CurrentUserDesignation;
    }
 //    //runs the action that gets all of the notification, btc was here, returns json encoded respponse
	// $unresponded_notification = json_decode(Yii::$app->runAction('/referrals/notification/count_unresponded_notification'));
    
 //    //checks if there are any notification response and displays them otherwise displays none, btc was here
	// $unresponded = $unresponded_notification->num_notification > 0 ? $unresponded_notification->num_notification : ''; //no display if 0

	// //same as the first notification but only gets all of the unseen bidding notification, btc was here, returns json encoded response
	// $unseen_bid_notification = json_decode(Yii::$app->runAction('/referrals/bidnotification/count_unseen_bidnotification'));

 //    //display all or nothing, hmm btc was here
	// $unseen = $unseen_bid_notification['bid_notification'] > 0 ? $unseen_bid_notification['bid_notification'] : '';
  
    //notification will run if the user is already logged in
	// $this->registerJs("
	// 	setInterval(function(e){
	// 		get_unresponded_notifications();
	// 	}, 30000);
	// ");
	
	// $this->registerJs("
	// 	setInterval(function(e){
	// 		get_unseen_bidnotifications();
	// 	}, 30000);
	// ");
}
?>
<aside class="main-sidebar">

    <section class="sidebar">

        <!-- Sidebar user panel -->
       
        <div class="user-panel" style="height:70px">
            <div class="pull-left image">
            <?php 
                        if (Yii::$app->user->isGuest){
                            $imagename = "no-image.png";
                        }else{
                            $CurrentUser = User::findOne(['user_id'=> Yii::$app->user->identity->user_id]);
                        
                                $imagename = $CurrentUser->profile->image_url;
                           
                             if ($imagename){
                                $imagename = $CurrentUser->profile->image_url;
                            }else{
                                $imagename = "no-image.png";
                            }
                        }
                     ?>  
                         <?= Html::img("/uploads/user/photo/".$imagename, [ 
                            'class' => 'img-circle',     
                            'data-target'=>'#w0'
                        ]) 
                        ?>
            </div>
            <div class="pull-left info">
                <p><?= $UsernameDesignation ?></p>
              
               <a href="#"><i class="fa fa-circle text-success" ></i> Online</a>
                
            </div>
        </div>
    
        <!-- search form -->
        <!-- <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search..."/>
              <span class="input-group-btn">
                <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
            </div>
        </form> -->
      <br>
        <?php
        $Menu= Package::find()->orderBy(['PackageName'=>SORT_ASC])->all();
        $init=true;
        foreach ($Menu as $MenuItems => $Item) {
            $modulePermission="access-".strtolower($Item->PackageName);
            $MenuItems= PackageDetails::find()->orderBy(['Package_Detail'=>SORT_ASC])->where(['PackageID'=>$Item->PackageID])->all();
            $ItemSubMenu[]=[
                'label' => '<img src="/images/icons/dashboard.png" style="width:20px">  <span>' . 'Dashboard' . '</span>', 
                'icon'=>' " style="display:none;width:0px"',
                'url'=>["/".strtolower($Item->PackageName)],
                //'url'=>["/".strtolower($Item->PackageName)],
                'visible'=>true
            ];
            $unresponded = '';
	        $unseen = '';
            //$unresponded=""; //comment this
            // $ItemSubMenu[]=[];
            foreach ($MenuItems as $MenuItem => $mItem){
                $icon=substr($mItem->icon,6,strlen($mItem->icon)-6);
                $pkgdetails1=strtolower($mItem->Package_Detail);
                $pkgdetails2=str_replace(" ","-",$pkgdetails1);
                $SubmodulePermission="access-".$pkgdetails2; //access-Order of Payment


                //delete this 3 line if we gonna use the new referral
                // $numNotification = '';
                // $template = '<a href="{url}">{label}</a>';
                // $showURL = [$mItem->url];
                
                //uncomment this line if we gonna use the new referral
                

				if($mItem->extra_element == 1){//1 is for the referral notification
					$numNotification = '&nbsp;&nbsp;<span class="label label-danger" id="count_noti_sub_referral">'.$unresponded.'</span>';
					$showURL = '#';
					$template = '<a href="{url}" onclick="showNotifications()" id="btn_unresponded_referral">{label}</a>';
				} elseif ($mItem->extra_element == 2) {//2 is for the bidding notification
					$numNotification = '&nbsp;&nbsp;<span class="label label-danger" id="count_noti_sub_bid">'.$unseen.'</span>';
					$showURL = '#';
					$template = '<a href="{url}" onclick="showBidNotifications()" id="btn_unseen_bid">{label}</a>';
				} else {
					$numNotification = '';
					$template = '<a href="{url}">{label}</a>';
					$showURL = [$mItem->url];
				}

                
                $ItemS=[
                   'label' =>'<img src="/images/icons/' .$mItem->icon. '.png" style="width:20px">  <span>' . $mItem->Package_Detail . $numNotification . '</span>', 
                   'icon'=>' " style="display:none;width:0px"',
                   'url'=>$showURL,
                   'visible'=>Yii::$app->user->can($SubmodulePermission),
				   'template' => $template,
                ];
                array_push($ItemSubMenu, $ItemS);
            }
			
			if($unresponded > 0 && $unseen > 0){
            	$all_notification = $unresponded + $unseen;
            } elseif($unresponded > 0 && $unseen == ''){
             	$all_notification = $unresponded;
            } elseif($unresponded == '' && $unseen > 0){
           	$all_notification = $unseen;
            } else {
              	$all_notification = '';
            }
			
            $MainIcon=substr($Item->icon,6,strlen($Item->icon)-6);
			$showNotification = (stristr($Item->PackageName, 'referral')) ? '&nbsp;&nbsp;<span class="label label-danger" id="count_noti_menu">'.$all_notification.'</span>' : '';
            $ItemMenu[]=[
                'label' => '<img src="/images/icons/' .$Item->icon. '.png" style="width:20px">  <span>' . ucwords($Item->PackageName) . $showNotification . '</span>', 
                'icon'=>' " style="display:none;width:0px"',
                'url' => "",
                //'url' => ["/".$Item->PackageName."/index"],
                'items'=>$ItemSubMenu,
                'visible'=>Yii::$app->user->can($modulePermission)
            ]; 
             unset($ItemSubMenu);
        }
        // Fixed Sub Menu Item
        $SubItem=[
            'label' => '<img src="/images/icons/admin.png" style="width:20px">  <span>System</span>', 
            'icon'=>' " style="display:none;width:0px"',
            'url' => ["#"],
            'items'=>[
                [
                    'label' => '<img src="/images/icons/dbmanager.png" style="width:20px">  <span>DB Manager</span>', 
                    'icon'=>' " style="display:none;width:0px"',
                    'url' => ["/dbmanager"],
                    'visible'=>Yii::$app->user->can('access-db-manager')
                ],
                [
                    'label' => '<img src="/images/icons/dbconfig.png" style="width:20px">  <span>Configurations</span>', 
                    'icon'=>' " style="display:none;width:0px"',
                    'url' => ["/dbmanager/config"],
                    'visible'=>Yii::$app->user->can('access-db-config')
                ],
                [
                    'label' => '<img src="/images/icons/admin.png" style="width:20px">  <span>API Configuration</span>', 
                    'icon'=>' " style="display:none;width:0px"',
                    'url' => ["/system/apiconfig"],
                    'visible'=>Yii::$app->user->can('access-api-config')
                ],
                [
                    'label' => '<img src="/images/icons/admin.png" style="width:20px">  <span>Backup and Restore</span>', 
                    'icon'=>' " style="display:none;width:0px"',
                    'url' => ["/system/utilities/backup-restore"],
                    'visible'=>Yii::$app->user->can('access-api-config')
                ]
            ],
            'visible'=>Yii::$app->user->can('access-system')
        ];
        array_push($ItemMenu, $SubItem);
        ?>
         <?php 
         echo dmstr\widgets\Menu::widget(
            [
                'options' => ['class' => 'sidebar-menu tree', 'data-widget'=> 'tree'],
                'items' => $ItemMenu,
                'encodeLabels' => false,
            ]
        );
        ?>
    </section>

</aside>
