<?php
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\system\User;
use common\models\system\Package;
use common\models\system\Message;
use common\models\system\Rstl;
use yii\helpers\ArrayHelper;
use common\components\Functions;
use common\components\ReferralComponent;

//EGG
use kartik\file\FileInput;
use yii\widgets\ActiveForm;
use common\models\message\Chat;
use common\models\message\ChatGroup;
use common\models\message\ChatSearch;
use common\models\system\LoginForm;
use linslin\yii2\curl;
use yii\helpers\Json;
use yii\data\ActiveDataProvider;
$this->registerCssFile("/css/modcss/chat.css", [
], 'css-chat'); //EGG

/* @var $this \yii\web\View */
/* @var $content string */
$Request_URI=$_SERVER['REQUEST_URI'];
$func= new Functions;

if($Request_URI=='/'){//alias ex: http://admin.eulims.local
    $Backend_URI=Url::base();//Yii::$app->urlManager->createUrl('/');
    $Backend_URI=$Backend_URI."/uploads/user/photo/";
}else{//http://localhost/eulims/backend/web
    $Backend_URI=Url::base().'/uploads/user/photo/';
}
Yii::$app->params['uploadUrl']=\Yii::$app->getModule("profile")->assetsUrl."/photo/";//$GLOBALS['upload_url'];
$imagePath=Yii::$app->params['uploadUrl'];
if(Yii::$app->user->isGuest){
    $CurrentUserName="Visitor";
    $CurrentUserAvatar=Yii::$app->params['uploadUrl'] . 'no-image.png';
    $CurrentUserDesignation='Guest';
    $UsernameDesignation=$CurrentUserName;
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
       $UsernameDesignation=$CurrentUserName.'-'.$CurrentUserDesignation;
    }
}

$Packages= Package::find()->all();
$conditions = ['to' => Yii::$app->user->id, 'status' => 0];
$messages=Message::find()->where($conditions)->all();
$TotalMsg=count($messages);
if($TotalMsg<=0){
    $TotalUnreadMessage="You have no message.";
}elseif($TotalMsg==1){
    $TotalUnreadMessage="You have $TotalMsg unread message.";
}else{
    $TotalUnreadMessage="You have $TotalMsg unread messages.";
}
if($TotalMsg==0){
    $TotalMsg='';
}
$GLOBALS['rstl_id']= 11;

//added the intro js and css
$this->registerCssFile("/css/introjs.css", [
], 'css-intro');


$this->registerJsFile("/js/intro.js", [
], 'js-intro');

$session = Yii::$app->session;
	
$source = $GLOBALS['newapi_url'].'message/'; //API LINK
$sourcetoken="";
$flag="";
$contacts="";
$group="";
if(isset($_SESSION['usertoken'])){
	
	$sourcetoken=$_SESSION['usertoken'];
	$userid= $_SESSION['userid'];
	//get profile
	$authorization = "Authorization: Bearer ".$sourcetoken; 
	$apiUrl=$source.'getuser';
	$curl = new curl\Curl();
	$curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
	$curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
	$curl->setOption(CURLOPT_CONNECTTIMEOUT, 180);
	$curl->setOption(CURLOPT_TIMEOUT, 180);
	$list = $curl->get($apiUrl);
	$decode=Json::decode($list);

	//GROUPLIST
	$groupUrl=$source.'getgroup?userid='.$userid;
	$curlgroup = new curl\Curl();
	$curlgroup->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $authorization]);
	$curlgroup->setOption(CURLOPT_SSL_VERIFYPEER, false);
	$curlgroup->setOption(CURLOPT_CONNECTTIMEOUT, 180);
	$curlgroup->setOption(CURLOPT_TIMEOUT, 180);
	$grouplist = $curlgroup->get($groupUrl);
	$group=Json::decode($grouplist);

	$chat = new Chat();
	$searchModel = new ChatSearch();
	$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
	
	$contacts=$decode; 
	
    $countunread=0;
	
	$flag=1;
	?>
	<button class="open-button" onclick="openForm()"><span id="counterunread" class="label label-success"></span><i class="fa fa-commenting-o"></i></button>		
    <?php
}else{
	$flag=0;
	echo Html::button('<h5>Login</h5>', ['value'=>'/chat/info/login', 'class' => 'open-button','title' => Yii::t('app', "Login"),'id'=>'btnOP','onclick'=>'LoadModal(this.title, this.value,"100px","300px");'])
	?>
	
	 
	<?php
}	



$unresponded = [];
if(isset($_SESSION['usertoken'])&&(!Yii::$app->user->isGuest)){
    //get the unresponded notification of the referral
    $referralcomp = new ReferralComponent;
    if(isset(Yii::$app->user->identity->profile->rstl_id)){
    $unresponded = $referralcomp->listUnrespondedNofication((int) Yii::$app->user->identity->profile->rstl_id);
    $unresponded = $unresponded['notification'];
    }
}
?>

<header class="main-header">

    <?= Html::a(Html::img(Yii::$app->request->baseUrl."/images/logo.png",['title'=>'Enhanced ULIMS','alt'=>'Enhanced ULIMS','height'=>'30px']), Yii::$app->homeUrl, ['class' => 'logo']) ?>
    <nav class="navbar navbar-static-top" role="navigation">
        <a href="#" class="sidebar-toggle" onclick="ToggleLeftMenu()" data-toggle="push-menu" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
        <div class="navbar-custom-menu">
            
            <ul class="nav navbar-nav">
                <li class="dropdown messages-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-envelope-o"></i>
                        <span class="label label-success"><?= $TotalMsg ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="header"><?= $TotalUnreadMessage ?></li>
                        <li>
                         
						 <!-- inner menu: contains the actual data -->
                            <ul class="menu">
                                <li><!-- start message -->
                                    <?php foreach($messages as $message){ ?>
                                    <a href="<?= Url::to(['/message/message/view','hash'=>$message->hash]) ?>">
                                            <div class="pull-left">
                                                <span><?= $message->sender->username ?></span>
                                            </div>
                                            <h4>
                                                <?= $message->title ?>
                                                <small><i class="fa fa-clock-o"></i> <?= $message->created_at ?></small>
                                            </h4>
                                            <p><?= $message->message ?></p>
                                        </a> 
                                    <?php } ?>
                                </li>
                            </ul>
                        </li>
                        <li class="footer">
                            <!-- <a href="<?= Url::to($GLOBALS['frontend_base_uri'].'message/message/inbox') ?>">View all Messages</a> -->
                        </li>
                    </ul>
                </li>
                <li class="dropdown messages-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-bell-o"></i>
                        <span class="label label-warning" id="top_notif_header"><?= count($unresponded)==0?"":count($unresponded) ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- <li class="header">
                        	<span class="label label-warning" id="mid_notif_header"></span>
                        	Referral Notification
                    	</li> -->
                        <li>
                        	<ul class="menu" id="referralnotif">
                                <?php
                                foreach ($unresponded as $item) {
                                    //get the agency they came from
                                    $rstlcode = Rstl::find()->select('code')->where(['rstl_id'=>$item['sender_id']])->one();
                                    echo '<li>';
                                        echo '<a href="#">';
                                            echo '<div>';
                                                echo '<span>'.$item['sender_name'].($rstlcode?' of '.$rstlcode->code:'').'</span>';
                                            echo '</div>';
                                            echo '<h4>';
                                                echo "Accepted the referral";
                                                echo '<small></small>';
                                            echo '</h4>';
                                            echo '<p><i class="fa fa-clock-o"></i> '.$item['notification_date'].'</p>';
                                        echo '</a>';
                                    echo '</li>';
                                    
                                }

                                ?>
                                <!-- start message --> 
                            </ul>
                        </li>
                        <li class="footer">
                            <a href="<?= Url::to($GLOBALS['frontend_base_uri'].'/referrals/notification/list_unresponded_notification') ?>">View all Notification</a>
                        </li>
                    </ul>
                </li>
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">

                    <?php 
                        if (Yii::$app->user->isGuest){
                            $imagename = "user.png";
                        }else{
                            $CurrentUser = User::findOne(['user_id'=> Yii::$app->user->identity->user_id]);
                            $imagename = $CurrentUser->profile->image_url;

                              
                            if ($imagename){
                                //$imagename = $CurrentUser->profile->image_url;
								$imagename="user.png";
                            }else{
                                $imagename = "user.png";
                            }
                        }
                     ?>  
                 <?= Html::img("/uploads/user/photo/".$imagename, [ 
                    'class' => 'user-image',     
                    'data-target'=>'#w0'
                ]) 
                ?>
                        
                    <span class="hidden-xs"><?= $CurrentUserName ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- User image -->
                        <li class="user-header">
                        <?php 
                        if (Yii::$app->user->isGuest){
                            $imagename = "no-image.png";
                        }else{
                            $CurrentUser = User::findOne(['user_id'=> Yii::$app->user->identity->user_id]);
                            $imagename = $CurrentUser->profile->image_url;
                        }
                     ?>  
                         <?= Html::img("/uploads/user/photo/".$imagename, [ 
                            'class' => 'img-circle',     
                            'data-target'=>'#w0'
                        ]) 
                        ?>
                            <p>
                                <?= $UsernameDesignation ?> 
                            </p>
                        </li>
                        <!-- Menu Body -->
                        <li class="user-body">
                            <hr style="margin: 0 0 0 0;padding: 0 0 0 0">
                        </li>
                        <!-- Menu Footer-->
                        <li class="user-footer">
                            <div class="pull-left">
                                <?php if(Yii::$app->user->can('access-profile') || Yii::$app->user->can('access-his-profile')){ ?>
                                <a href="<?= Url::toRoute('/profile') ?>" class="btn btn-default btn-flat">Profile</a>
                                <?php }else{ ?>
                                <a href="#" class="btn btn-default btn-flat disabled">Profile</a>
                                <?php } ?>
                            </div>
                            <div class="pull-right">
                                <?php if(Yii::$app->user->isGuest){ ?>
                                <?= Html::a(
                                    'Login',
                                    ['/site/login'],
                                    ['data-method' => 'post', 'class' => 'btn btn-default btn-flat']
                                ) ?> 
                                <?php }else{ ?>
                                <?= Html::a(
                                    'Sign out',
                                    ['/site/logout'],
                                    ['data-method' => 'post', 'class' => 'btn btn-default btn-flat']
                                ) ?> 
                                <?php } ?>
                            </div>
                        </li>
                    </ul>
                </li>

                <!-- User Account: style can be found in dropdown.less 
                <li>
                    <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
                </li>
                !-->
            </ul>
        </div>
    </nav>
</header>

	<div class="chat-popup" id="myForm" name="myForm">
	
		<form class="form-container" enctype="multipart/form-data">
			<div class="chat-popup-header">
				<!--<span>OneLab Chat</span>-->
				<!--i class="fa fa-plus" id="plusgc"></i -->
				<?php 
				echo Html::button('<h5></h5>', ['value'=>'/chat/info/group', 'class' => 'fa fa-group','title' => Yii::t('app', "Group"),'id'=>'btnOP','onclick'=>'LoadModal(this.title, this.value);','style'=>'border-radius: 50%;'])
	            ?>
		
				
				<label id="profilenamepop"> &nbsp;</label>
				<label id="chattype" hidden> &nbsp;</label> <!-- message(1) or file(2) -->
				<label id="ctype" hidden> &nbsp;</label> <!-- personnal(1) or group(2) -->
				<label id="dataid" hidden> &nbsp;</label> <!-- contact_id or group_id -->
				<!--label id="recipientid" hidden> &nbsp;</label -->
				<input type="text" id="recipientid" name="recipientid" hidden>
				<i class="fa fa-close" onclick="closeForm()"></i>

			</div>
			<div class="chat-popup-tab">
				<button type="button" class="btntab" id="btnuser"><i class="fa fa-user"></i></button>
			
				<button type="button" class="btntab" id="btngroup"><i class="fa fa-group"></i></button>
				 
			</div>
			
			<div class="scroll-style1" id="popchatbody">
			
			</div>
			<input type="file" name="filetoupload" id="UploadFile">
			<div class="chat-popup-footer">
				<textarea id="chatareapop" placeholder="Type message.." name="msg" required></textarea>
				<button type="button" class="btn" id="sendmes"><i class="fa fa-send-o"></i></button>
			</div>
		</form>
	</div>
<script type="text/javascript">

var x;
var profname ="";
var flag="";
function mes(id,type) {
	flag=<?=$flag?>;
	//var type=1;
	if(flag == "1"){
		$("#recipientid").val(id);
		const user_id=<?php 
		if(isset($_SESSION['userid'])){
			echo json_encode($_SESSION['userid']);
		}else{
			echo 0;
		}
		?>;
		const token =<?php 
		if(isset($_SESSION['usertoken'])){
			echo json_encode($_SESSION['usertoken']);
		}else{
			echo 0;
		}
		?>;
		   if (id != ""){
				$.ajax({
					url: "/chat/info/readmessage", //API LINK FROM THE CENTRAL
					type: 'POST',
					dataType: "JSON",
					data: {
						id:id
					},
					success: function(response) {
						//return response.fullname;
					},
					error: function(xhr, status, error) {
						//alert(error);
						location.reload();
					}
				}); 
		   }

			$.ajax({
			url: "/chat/info/getcontact", //API LINK FROM THE CENTRAL
			type: 'POST',
			dataType: "JSON",
			data: {
				userid: user_id,
				recipientid: id,
				type:type
			},
			success: function(response) {
				var y;
				
				x="";
				x = x+'<br>';
				for(y=0;y<response.chat.length;y++){
					var dt=new Date(response.chat[y].timestamp);
					dt=formatAMPM(dt);
					x = x+'<div>';
					var messagetype= response.chat[y].message_type;
					var chatdatatype= response.chat[y].chat_data_type;
					if (response.chat[y].sender_userid != user_id){
						
						x = x+"<div class='message-blue'>";
						if(chatdatatype == 2){
							profname= response.chat[y].contact_id;
							x = x+"<div class='message-prof'>"+profname+"</div>"; //Profile header	
						}
						
						if(messagetype == 1){
							x = x+"<p class='message-content'>"+response.chat[y].chat_data+"</p>";	
						}else{
							x= x+ "<a href='https://eulims.onelab.ph/uploads/message/"+response.chat[y].chat_data+"' download>"+response.chat[y].chat_data+"</a>";
						}
						
						x = x+"<div class='message-timestamp-left'>"+dt+"</div>";
						x = x+"</div>";
						x = x+'<br>';
					
					
					}else{
						x = x+"<div class='message-orange'>";
						if(messagetype == 1){
							x = x+"<p class='message-content'>"+response.chat[y].chat_data+"</p>";	
						}else{
							x= x+ "<a href='https://eulims.onelab.ph/uploads/message/"+response.chat[y].chat_data+"' download>"+response.chat[y].chat_data+"</a>";
						}
						x = x+"<div class='message-timestamp-right'>"+dt+"</div>";
						x = x+"</div>";
						x = x+'<br>';
					}

					x = x+'</div>';
							
				}
				
				
				$('#chattype').html(type);
				$('#dataid').html(response.id);
				if(type == 1){
					$("#ctype").text("1");//personal message
					$('#chathere').html(x); 
					$('#profilename').html(response.profile.fullname);
					
					$('#popchatbody').html(x); 
					
					$('#profilenamepop').html(response.profile.fullname);
				}
				if(type == 2){
					//console.log(response.profile);
					
					$("#ctype").text("2");//group message
					$('#profilenamepop').html(response.profile.group_name);
					$('#popchatbody').html(x); 
				}
				
			},
			error: function(xhr, status, error) {
				//alert(error);
				location.reload();
			}
			});  
			
			$('#popchatbody').scrollTop($('#popchatbody')[0].scrollHeight);
	
	}
 }


function sendmessage() {
   var textchat=$('#chatareapop').val();
   if(textchat === ""){
	   //alert("Hello");
   }
   else{
	   $('#chattype').text("1");//Message type
	   sendchat(textchat);
   }
    
   if (document.getElementById("UploadFile").files.length === 0 ){ //For file attachment
		//alert("Please select a file to upload");
   }
   else{
	    $('#chattype').text("2");//Filetype
		const token =<?php 
		if(isset($_SESSION['usertoken'])){
			echo json_encode($_SESSION['usertoken']);
		}else{
			echo 0;
		}
		?>;
		var formData = new FormData($('form')[0]);
		
		$.ajax({
			url: "https://eulims.onelab.ph/api/message/savefile", //API LINK FROM THE CENTRAL
			type: 'POST',
			dataType: "JSON",
			beforeSend: function (xhr) {
				xhr.setRequestHeader('Authorization', 'Bearer '+ token);
			}, 
			data: formData,
			success: function(response) {
				$('#UploadFile').val("");
				var txt=response.filename; //Filename
				if(response.message == "success"){
					//alert("Oks na lodi proceed next step!");	
					sendchat(txt);
				}
				else{
					alert("Error ka lodi!");
				}
				
				
			},
			error: function(xhr, status, error) {
				//alert(error);
				location.reload();
			},
			cache: false,
			contentType: false,
			processData: false
		}); 	
   }
 
  
} 



function formatAMPM(date) {
 const ye = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(date);
 const mo = new Intl.DateTimeFormat('en', { month: 'short' }).format(date);
 const da = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(date);

  var hours = date.getHours();
  var minutes = date.getMinutes();
  var ampm = hours >= 12 ? 'pm' : 'am';
  hours = hours % 12;
  hours = hours ? hours : 12; // the hour '0' should be '12'
  minutes = minutes < 10 ? '0'+minutes : minutes;
  var strTime = da+"-"+mo+"-"+ye + " " + hours + ':' + minutes + ' ' + ampm;
  return strTime;
}

window.setInterval(function(){
 
  // var id= $("#recipientid").value();
   var id=$("#recipientid").val();
   var type =$("#ctype").text();
   if(id == "")
   {
	  //alert("wala");   
   }
   else{
	  mes(id,type); 
   } 
   countunread();
   $(document).on('focus','.msg',function(){
	  alert('is typing'); 
   });
  
}, 5000);

function openForm() {
	document.getElementById("myForm").style.display = "block";
}

function closeForm() {
	document.getElementById("myForm").style.display = "none";
}

$("#btnuser").click(function(){
	$("#recipientid").val("");
	$('#profilenamepop').html("");
	contacts();
});
$("#btngroup").click(function(){
	$("#recipientid").val("");
	$('#profilenamepop').html("");
	groupcontacts();
});


function contacts(){ //Personnal Messages
    flag=<?=$flag?>;
	if(flag == "1"){
		var y;
		 y = "<h4>List of Contacts </h4>";
		 //y= "<input autocomplete='off' placeholder='Search' spellcheck='false' type='text' aria-label='Search' value=''>";
		 y =  y+ "<div>";
		 y = y + "<input type='text' id='txtsearch' placeholder='Search' style='width:90%;'>";
		 y = y + "<button id='btnsearch' name='btnsearch' onclick='search();'>";
         y = y + "<i class='fa fa-search'></i>";
         y = y + "</button>";
         y = y + "</div>";
		 y = y + "</br>";
		<?php 
		if($contacts){
			foreach ($contacts as $data)
			 { 
			 $profuserid=$data['user_id'];
			 if(isset($_SESSION['usertoken'])){
			 $userid2=($_SESSION['usertoken']);
		     }
			 
			 
			 ?>
			y=y + "<a class='thismessage' onclick='mes(<?=$data['user_id']?>,1)'>";
			y= y + "<div class='first'><img src='/uploads/user/photo/user.png' alt='/uploads/user/photo/user.png' width='42' height='42'>&nbsp;<b>"+ '<?= $data['fullname']?>' +"</div>";
			y= y + "</a>";
			<?php } ?>
			//GROUPS
			y= y + "<br> <div> <h4>Groups Contacts </h4></div>";
			<?php 
			if($group){
				foreach ($group as $data)
				 { ?>
					y=y + "<a class='thismessage1' onclick='mes(<?=$data['chat_group_id']?>,2)'>";
					y= y + "<div class='first'><img src='/uploads/user/photo/group.png' alt='/uploads/user/photo/user.png' width='42' height='42'>&nbsp;<b>"+ '<?= $data['chatGroup']['group_name']?>' +"</div>";
					y= y + "</a>";
				<?php } 
			}
		}	
		?>
		
		
		$('#popchatbody').html(y); 
	}	
}
function groupcontacts(){ //Group Messages
	var y;
	//document.getElementById('popuser').style.display='block';
	 y = "<h4>List of Contacts </h4>";
	 y =  y+ "<div>";
	 y = y + "<input type='text' id='txtsearchgroup' placeholder='Search' style='width:90%;'>";
	 y = y + "<button id='btnsearchgroup' name='btnsearchgroup' onclick='searchgroup();'>";
	 y = y + "<i class='fa fa-search'></i>";
	 y = y + "</button>";
	 y = y + "</div>";
	<?php 
		if($group){
		foreach ($group as $data)
		 { ?>
				y=y + "<a class='thismessage1' onclick='mes(<?=$data['chat_group_id']?>,2)'>";
				y= y + "<div class='first'><img src='/uploads/user/photo/group.png' alt='/uploads/user/photo/user.png' width='42' height='42'>&nbsp;<b>"+ '<?= $data['chatGroup']['group_name']?>' +"</div>";
				y= y + "</a>";
		<?php } 
		}
	?>
	
	$('#popchatbody').html(y); 
}

function sendchat(txt) {
   txt=txt.toLowerCase();
   const token =<?php 
	if(isset($_SESSION['usertoken'])){
		echo json_encode($_SESSION['usertoken']);
	}else{
		echo 0;
	}
	?>;
   var id=$('#dataid').text();
   var type = $('#ctype').text();
   var dataxtype = $('#chattype').text();
   const sender_userid=<?php 
		if(isset($_SESSION['userid'])){
			echo json_encode($_SESSION['userid']);
		}else{
			echo 0;
		}
	?>;
	$.ajax({
		url: "/chat/info/setmessage", //API LINK FROM THE CENTRAL
		type: 'POST',
		dataType: "JSON", 
		data: {
			sender_userid: sender_userid,
			message: txt,
			type: type,
			id:id,
			dataxtype: dataxtype
		},
		success: function(response) {
			var dt=new Date();
			dt=formatAMPM(dt);
			x = x+'<br>';
			x = x+"<div class='message-orange'>";
			if(type == 1){
				x = x+"<p class='message-content'>"+txt+"</p>";
			}else{
				x= x+ "<a href='http://eulims.onelab.ph/uploads/message/"+txt+"' download>"+txt+"</a>";
			}
			
			
			x = x+"<div class='message-timestamp-right'>"+dt+"</div>";
			x = x+"</div>";
			x = x+'<br>';
			$('#chatareapop').val(" ");
			$('#popchatbody').html(x); 
		},
		error: function(xhr, status, error) {
			//alert(error);
			location.reload();
		}
	}); 	
}
$("#sendmes").click(function(){
	sendmessage();
});

function getprofile(id) {
	const token =<?php 
	if(isset($_SESSION['usertoken'])){
		echo json_encode($_SESSION['usertoken']);
	}else{
		echo 0;
	}
	?>;
	$.ajax({
		url: "/chat/info/profile", //API LINK FROM THE CENTRAL	
		type: 'POST',
		dataType: "JSON",
		data: {
			id:id
		},
		success: function(response) {
			return response.fullname;
		},
		error: function(xhr, status, error) {
			//alert(error);
			location.reload();
		}
	}); 	
}

function countunread() {
	const userid=<?php 
		if(isset($_SESSION['userid'])){
			echo json_encode($_SESSION['userid']);
		}
	?>;	
	
	if (userid != ""){
		$.ajax({
			url: "/chat/info/getcountunread", //API LINK FROM THE CENTRAL
			type: 'POST',
			dataType: "JSON",
			data: {
				userid:userid
			},
			success: function(response) {
				//alert(response);
				if(response == 0){
					document.getElementById('counterunread').style.display = 'none';
				}
				else{
					document.getElementById('counterunread').style.display = 'block';
					$("#counterunread").html(response);
				}
				
			},
			error: function(xhr, status, error) {
				//alert(error);
				location.reload();
			}
		});  
	}
	
}

function search() {
	var txtsearch=$('#txtsearch').val();
	
	$.ajax({
		url: "/chat/info/searchcontact", //API LINK FROM THE CENTRAL
		type: 'POST',
		dataType: "JSON", 
		data: {
			txtsearch: txtsearch
		},
		success: function(response) {
		//console.log(response[0].fullname);
		//console.log(response);
			var y;
			 y = "<h4>List of Contacts </h4>";
			 y =  y+ "<div>";
			 y = y + "<input type='text' id='txtsearch' placeholder='Search' style='width:90%;'>";
			 y = y + "<button id='btnsearch' name='btnsearch' onclick='search();'>";
			 y = y + "<i class='fa fa-search'></i>";
			 y = y + "</button>";
			 y = y + "</div>";
			 y = y + "</br>";
			 
			for(x=0;x<response.length;x++){
				y=y + "<a class='thismessage' onclick='mes("+response[x].user_id+",1)'>";
				y= y + "<div class='first'><img src='/uploads/user/photo/user.png' alt='/uploads/user/photo/user.png' width='42' height='42'>&nbsp;<b>"+response[x].fullname+"</div>";
				y= y + "</a>";
			} 
			
			$('#popchatbody').html(y);  	
		},
		error: function(xhr, status, error) {
			//alert(error);
			location.reload();
		}
	});
}

function searchgroup() {
var txtsearchgroup=$('#txtsearchgroup').val();
}
 </script>