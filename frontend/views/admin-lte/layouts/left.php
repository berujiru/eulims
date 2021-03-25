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
}
?>
<aside class="main-sidebar">

    <section class="sidebar">       
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
                'visible'=>true
            ];
            $unresponded = '';
	        $unseen = '';
            foreach ($MenuItems as $MenuItem => $mItem){
                $icon=substr($mItem->icon,6,strlen($mItem->icon)-6);
                $pkgdetails1=strtolower($mItem->Package_Detail);
                $pkgdetails2=str_replace(" ","-",$pkgdetails1);
                $SubmodulePermission="access-".$pkgdetails2;

                $numNotification = '';
                $template = '<a href="{url}">{label}</a>';
                $showURL = [$mItem->url];
                
                $ItemS=[
                   'label' =>'<img src="/images/icons/' .$mItem->icon. '.png" style="width:20px">  <span>' . $mItem->Package_Detail . $numNotification . '</span>', 
                   'icon'=>' " style="display:none;width:0px"',
                   'url'=>$showURL,
                   'visible'=>Yii::$app->user->can($SubmodulePermission),
				   'template' => $template,
                ];
                array_push($ItemSubMenu, $ItemS);
            }
			
            $all_notification = '';

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
