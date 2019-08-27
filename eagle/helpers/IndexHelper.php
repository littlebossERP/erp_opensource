<?php

namespace eagle\helpers;

use eagle\models\UserToken;
use eagle\models\UserBase;
use eagle\models\UserInfo;
use eagle\models\UserDatabase;
use eagle\models\UserRegisterApp;
use eagle\modules\app\models\UserAppInfo;
use eagle\modules\util\models\EDBManager;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\models\SysArticle;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\models\UserInvitationCode;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\UserRegisterLog;
use eagle\models\SaasPaypalsupplierUser;
use eagle\modules\util\helpers\SMSHelper;
use eagle\modules\util\helpers\RedisHelper;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: hxl <plokplokplok@163.com>
+----------------------------------------------------------------------
| Create Date: 2014-01-30
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * INDEX helper方法类
 +------------------------------------------------------------------------------
 * @category	Application
 * @package		Helper/Index
 * @subpackage  Exception
 * @author		hxl <plokplokplok@163.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class IndexHelper {
	private static $MapOrUserIdName;
	/**
	+----------------------------------------------------------
	 * 官网加载数据
	+----------------------------------------------------------
	 * @access static
	+----------------------------------------------------------
	 * @return				菜单树
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	+----------------------------------------------------------
	**/
	public static function guanwangIndexData() {
		$result = array();
		//首页文章显示
		$result['article1'] = SysArticle::find()->where("cat_id = 1 ORDER BY create_time DESC limit 3")->all();
		$result['article2'] = SysArticle::find()->where("cat_id = 2 ORDER BY create_time DESC limit 3")->all();
		$result['article3'] = SysArticle::find()->where("cat_id = 3 ORDER BY create_time DESC limit 3")->all();
		return $result;
	}

		/**
	 +----------------------------------------------------------
	 * 递归获取层级菜单树方法
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @params	$tree		数据
	 * @params	$tree		父类id
	 +----------------------------------------------------------
	 * @return				菜单树
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
		**/
	public static function getTree(&$tree, $pid = 0, $childMenu = null) {
		$result = array();
		foreach($tree as $leaf) {
			//剔除子级
			if($leaf->pid > $pid) {
				break;
			}
			if($leaf->pid != $pid) {
				continue;
			}
			if($leaf->text=='财务管理')continue;
			$tempArr = array(
					'id' => $leaf->id,
					'text' => $leaf->text,
					'iconCls' => $leaf->icon_class
			);
			if(!empty($leaf->url)) {
				$tempArr['attributes'] = array('url' => $leaf->url);
			}
			$children = self::getTree($tree, $leaf->id, $childMenu);
			if(!empty($children)) {
				$tempArr['state'] = 'open';
				$tempArr['children'] = $children;
			} else if(is_array($childMenu) && !array_key_exists($tempArr['id'], $childMenu)) {
				continue;
			}
			$result[] = $tempArr;
			unset($leaf);
		}
		return $result;
	}

	/**
	+----------------------------------------------------------
	 * 获取菜单方法
	+----------------------------------------------------------
	 * @access static
	+----------------------------------------------------------
	 * @return				菜单
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	+----------------------------------------------------------
	**/
	public static function getMenuData() {
		//根据当前用户id得到用户的详细信息
		$uid = Yii::app()->muser->getId();
		//按pid排序, 得到所有菜单项
		$tree = Authmenu::model()->findAll(array(
			'order' => '`pid` ASC, `sort` DESC'
		));
		//如果是主用户
		$user = Yii::app()->muser->getUser();
		if($user['puid'] == 0) {
			return self::getTree($tree);
		}
		//得到当前帐号的角色
		$userRole = AuthRoleHasUser::model()->findAllByAttributes(array('user_id' => $uid));
		$roleIds = array();
		if(count($userRole) > 0) {
			foreach($userRole as $v) {
				$roleIds[] = $v->role_id;
			}
		}
		//查询当前帐号的所有menu
		$menuCriteria = new CDbCriteria;
		$menuCriteria->addInCondition('role_id', $roleIds);
		$userMenu = AuthRoleHasMenu::model()->findAll($menuCriteria);
		//将所有menu的id 保存到一个数组
		$menu = array();
		if(count($userMenu) > 0){
			foreach($userMenu as $v){
				$menu[$v['menu_id']] = 1;
			}
		}
		return self::getTree($tree, 0, $menu);
	}

	/**
	+----------------------------------------------------------
	 * 帐户信息返回数据
	+----------------------------------------------------------
	 * @access static
	+----------------------------------------------------------
	 * @return				菜单
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	+----------------------------------------------------------
	**/
	public static function myaccounts() {
		$userid = Yii::app()->muser->getId();
		//查询系统数据库帐号表
		$userbase = UserBase::model()->findByPk($userid)->attributes;
		//如果不是管理员就退出
		if(!$userbase['puid'] == 0) return '403';
		//查询用户基本配置信息表
		$userCriteria = new CDbCriteria;
		$inarray = array(
			'myaccounts_mobile',
			'myaccounts_areacode',
			'myaccounts_telephone',
			'telephone_ext_number',
			'myaccounts_company',
			'user_city',
			'user_country',
			'user_district',
			'user_province',
            'myaccounts_postcode',
            'user_street'
		);
		$userCriteria->select = 'keyid, value';
		$userCriteria->addInCondition('keyid', $inarray);
		$userconfig = UserConfig::model()->findAll($userCriteria);
		foreach($userconfig as $v){
			$result['userconfig'][$v['keyid']] = $v['value'];
		}
		$result['userbase'] = $userbase;
		return $result;
	}
	/**
	+----------------------------------------------------------
	 * 验证码生成器
	+----------------------------------------------------------
	 * @access static
	+----------------------------------------------------------
	 * @return				图片资源
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	+----------------------------------------------------------
	**/
	public static function vericode() {
		Header("Content-type:image/png");
		$authnum_session = '';
		$str = 'abcdefghijkmnpqrstuvwxyz1234567890';
		//定义用来显示在图片上的数字和字母;
		$l = strlen($str); //得到字串的长度;
		//循环随机抽取四位前面定义的字母和数字;
		for($i=1;$i<=4;$i++){
			$num=rand(0,$l-1);
			//每次随机抽取一位数字;从第一个字到该字串最大长度,
			//减1是因为截取字符是从0开始起算;这样34字符任意都有可能排在其中;
			$authnum_session .= $str[$num];
			//将通过数字得来的字符连起来一共是四位;
		}
		///session_register("authnum_session");
		\Yii::$app->session['authnum_session'] = $authnum_session;
		\Yii::$app->session['authnum_session_create_time'] = time();
		
		//用session来做验证也不错;注册session,名称为authnum_session,
		//其它页面只要包含了该图片
		//即可以通过$_SESSION["authnum_session"]来调用
		//生成验证码图片，
		srand((double)microtime()*1000000);
		$im = imagecreate(50,20);//图片宽与高;
		//主要用到黑白灰三种色;
		$black = ImageColorAllocate($im, 0,0,0);
		$white = ImageColorAllocate($im, 255,255,255);
		$gray = ImageColorAllocate($im, 200,200,200);
		//将四位整数验证码绘入图片
		imagefill($im,68,30,$gray);
		//如不用干扰线，注释就行了;
		$li = ImageColorAllocate($im, 220,220,220);
		for($i=0;$i<3;$i++)
		{//加入3条干扰线;也可以不要;视情况而定，因为可能影响用户输入;
		imageline($im,rand(0,30),rand(0,21),rand(20,40),rand(0,21),$li);
		}
		//字符在图片的位置
		imagestring($im, 5, 8, 2, $authnum_session, $white);
		for($i=0;$i<90;$i++){//加入干扰象素
			imagesetpixel($im, rand()%70 , rand()%30 , $gray);
		}
		ImagePNG($im);
		ImageDestroy($im);
	}
	/**
	+----------------------------------------------------------
	 * 注册帮助类
	+----------------------------------------------------------
	 * @params	$post		用户POST提交注册信息(数组)
	+----------------------------------------------------------
	 * @access public
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	+----------------------------------------------------------
	**/
	public static function register($post) {
	    
	    //ip 黑名单
	    $blacklistIp=array("125.121.38.198","120.199.148.6");
	    
// 		if(!$post['applist'])return TranslateHelper::t('请选择要开通的应用');
		if (!preg_match("/^([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/i",$post['email'])) return '邮箱格式不合法';
		if(strlen(trim($post['password'])) < 6) return TranslateHelper::t('密码长度不得少于六个字符');
// 		if($post['repassword'] != $post['password']) return TranslateHelper::t('两次密码输入不一样');// dzt20170317 comments 简化注册界面
// 		if(!$post['familyname'])return TranslateHelper::t('输入用户名');
// 		if(!preg_match('/^[a-zA-Z_]{1}[a-zA-Z_0-9]{4,18}/', $post['username'])) return TranslateHelper::t('用户名必须是由字母或下划线开头的，字母、数字、下划线组成的六到二十个字符');
// 		if(!preg_match('/\d{2,}$/', $post['cellphone'])) return TranslateHelper::t('手机号码格式不正确');// dzt20170317 comments 简化注册界面
// 		if(!empty($post['telephone']) && !preg_match('/\d{2,}$/', $post['telephone'])) return TranslateHelper::t('电话号码格式不正确');// dzt20170317 comments 简化注册界面
// 		if(!$post['agreement']) return TranslateHelper::t('必须同意协议才可以注册');
		if(!preg_match('/^[0-9]{4,15}$/', $post['qq'])) return TranslateHelper::t('QQ号码不合法');
		if($post['authcode'] != \Yii::$app->session['authnum_session']) return TranslateHelper::t('验证码不正确');
		
		// 通过高青的api验证过邮箱 ，所以高青的邮箱不用验证
		if(empty($post['source']) || $post['source'] != 'gozens'){
			//验证邮箱 // dzt20170317 comments 简化注册界面
			if($post['source'] == 'kandeng'){// dzt20170804 个别客户不停通过这个前端注册账号，对这个前端重新添加邮箱验证。
			    // dzt20170807 控制邮箱注册 email白名单
			    $email = trim($post['email']);
			    if(stripos($email, '@hotmail.com') !== false || strpos($email, '@qq.com') !== false
        		        || strpos($email, '@163.com') !== false || strpos($email, '@sina.com') !== false
        		        || strpos($email, '@126.com') !== false || strpos($email, '@gmail.com') !== false
        		        || strpos($email, '@foxmail.com') !== false || strpos($email, '@Aliyun.com') !== false
        		        || strpos($email, '@outlook.com') !== false || strpos($email, '@sohu.com') !== false
		                || strpos($email, '@yahoo.com') !== false || strpos($email, '@vip.qq.com') !== false
        		        ){
        		}else
			        return $post['email'].":".TranslateHelper::t('注册失败。这个企业邮箱注册账号太多，请联系客服！');
			    
			    if(strpos($post['email'], '@139.com') !== false)
			        return TranslateHelper::t('注册失败。这个139.com邮箱接受验证码有问题，请换个邮箱');
			    
			    $token = UserToken::findOne($post['email']);
    			if(!$token) return TranslateHelper::t('请点击"发送注册码"按钮发送注册码到注册邮箱，并将获取到的注册码输入。');
    			if($post['registercode'] != $token->token) return TranslateHelper::t('注册码不正确或已失效，请重新进行邮箱验证');
			}
		}
		
		$existUser = UserBase::findOne(['email'=>$post['email']]);
		if(!empty($existUser)){
	        return TranslateHelper::t('该用户名或邮箱已经存在');
		}
		
		
		\Yii::info('用户注册:' . print_r($post,true),"file");
		//写入用户表
		$clientIP=IndexHelper::getClientIP();
		
		if ($clientIP<>null and $clientIP<>"" and in_array($clientIP,$blacklistIp)){
		    \Yii::error("hit blacklistip ip:{$clientIP}","file");
		    return TranslateHelper::t('你短时间注册的账号太多了，已列入可疑名单。');
		}
		
		$userbase = new UserBase();
// 		$userbase->user_name = $post['username'];// 用户名改为注册邮箱，不用输入
		$userbase->user_name = trim($post['email']);
		$userbase->password = md5(trim($post['password']));
		$userbase->auth_key = \Yii::$app->getSecurity()->generateRandomString();
		$userbase->register_date = time();
		$userbase->last_login_date = time();
		$userbase->last_login_ip = $clientIP;
		$userbase->register_ip = $clientIP;
		$userbase->puid = 0;
		$userbase->email = trim($post['email']);
		$userbase->is_active = 0;
		
		$userbase->save(false);
		//取得最后一条记录的ID
		$uid = $userbase->uid;
        if(!$uid) {
        	\Yii::error('保存UserBase信息失败:' . print_r($userbase->getErrors(),true) . ' params:' . print_r($post,true),"file");
        	return TranslateHelper::t('注册信息保存失败');
        }
        	
		//写入用户信息表
		$userinfo = new UserInfo();
		$userinfo->uid = $uid;
		$userinfo->familyname = trim($post['familyname']);
		// dzt20160712 注册信息去掉公司和地址
// 		$userinfo->company = trim($post['company']);
// 		$userinfo->address = trim($post['address']);
		$userinfo->cellphone = trim($post['cellphone']);
// 		$userinfo->telephone = trim($post['telephone']);
		$userinfo->qq = trim($post['qq']);
		
		if(!$userinfo->save(false)){
			\Yii::error('UserInfo保存失败:' . print_r($userinfo->getErrors(),true) . ' params:' . print_r($post,true),"file");
			return TranslateHelper::t('注册失败！');
		}
		
        \Yii::info('用户注册成功 email:' . $post['email'].', uid:' . $uid ,"file");
        
        // 20150410 for new requirements: tracker eagle2注册自动激活并创建数据库后，登录
        // dzt20151104 所有source都自动激活
        $timeMS1 = TimeUtil::getCurrentTimestampMS();
		$isActive = self::doActivation($userbase->uid, $userbase->email);
		$timeMS2 = TimeUtil::getCurrentTimestampMS();
		\Yii::info('用户'.$post['email'].',uid:'.$uid.' 用户自动激活用时:'.($timeMS2-$timeMS1) ,"file");
		if($isActive){
			\Yii::info('普通用户账号自动激活成功 email:' . $post['email'].', uid:' . $uid ,"file");
			$userbase->is_active = 1;
			$userbase->save(false);
			return 'success';
		}else{
			\Yii::error('普通用户账号自动激活失败 email:' . $post['email'].', uid:' . $uid ,"file");
			return 'not active';
		}
	}
	
	/**
	+----------------------------------------------------------
	 * 验证邮箱帮助类
	+----------------------------------------------------------
	 * @params	$email		邮箱信息
	+----------------------------------------------------------
	 * @access public
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	+----------------------------------------------------------
	**/
	public static function verifyEmail($sendto_email) {
		//生成随机数字
		srand((double)microtime()*1000000);//create a random number feed.
		$ychar="0,1,2,3,4,5,6,7,8,9";
		$list=explode(",",$ychar);
		$authnum = '';
		for($i=0;$i<6;$i++){
			$randnum=rand(0,9); // 10+26;
			$authnum .= $list[$randnum];
		}
		

		
		// TODO add send mail info @xxx@
		$emailsArr=array(
				array("email"=>"@xxx@@163.com","password" =>"@xxx@" ,"host"=>"smtp.163.com"),
				array("email"=>"@xxx@@qq.com","password" =>"@xxx@" ,"host"=>"smtp.qq.com"),
				
		);
		$emailNum=count($emailsArr);
		
		$emailIndex=rand(1,1000) % $emailNum;

		$littlebossEmail=$emailsArr[$emailIndex]["email"];
		$littlebossEmailPW=$emailsArr[$emailIndex]["password"];
		$emailHost=$emailsArr[$emailIndex]["host"];
		
		
		$nowTime = time();
		$URLog = new UserRegisterLog();
		$URLog->email = $littlebossEmail."===".$sendto_email;
		$URLog->status = 'before_send_mail';
		$URLog->token = $authnum;
		$URLog->create_time = $nowTime;
		$URLog->update_time = $nowTime;
		if(!$URLog->save(false)){
			\Yii::error('verifyEmail $URLog->save fail：'.print_r($URLog->errors,true),"file");
		}
				
		try {
			$mail = new \PHPMailer();
			$mail->CharSet='UTF-8';
			$mail->IsSMTP();                            // 经smtp发送
			$mail->Host     = $emailHost;           // SMTP 服务器
			$mail->SMTPAuth = true;                     // 打开SMTP 认证
			$mail->Username = $littlebossEmail;    // 用户名
			$mail->Password = $littlebossEmailPW;// 密码
			$mail->From     = $littlebossEmail;            // 发信人
			$mail->FromName = "小老板 ERP";        // 发信人别名
			$mail->AddAddress($sendto_email);                 // 收信人
			$mail->WordWrap = 50;
			$mail->IsHTML(true);                            // 以html方式发送
			$mail->AltBody  =  "请使用HTML方式查看邮件。";
			// 邮件主题
			$mail->Subject = 'littleboss verify code: '.$authnum;
			//如果标题有中文，用下面这行
			//$mail->Subject = "=?utf-8?B?" . base64_encode("信件标题") . "?=";
			//邮件内容
			$mail->Body =  '
				<html><head>
				<meta charset="UTF-8">
				</head>
				<body>
					<table width="700" border="0" cellspacing="0" cellpadding="0" align="center" style="font-size:14px;">
						  <tr>
						    <td><a href="www.littleboss.com" target="_blank"><img src="http://www.littleboss.com/images/logo.png" style="border:0; width:180px;" /></a></td>
						  </tr>
						  <tr style=" display:block; margin:25px 0;">
						    <td>感谢您注册小老板ERP系统</td>
						  </tr>
						  <tr style=" display:block; margin:25px 0; font-size:25px; color:#f00;">
						    <td><strong>您的注册码是：'.$authnum.'</strong></td>
						  </tr>
						  <tr style=" display:block; margin:25px 0; line-height:24px;">
						    <td>输入注册码后，加入小老板ERP讨论群（317561579），管理员将会在后台及时为您激活账户，然后您输入用户名和密码，就可以<strong>免费</strong>使用小老板ERP系统了。</td>
						  </tr>
						  <tr style=" display:block; margin:25px 0;">
						    <td>小老板ERP系统经多年沉淀积累，融合了卖家多样化的真实需求，构建前瞻性的运营模式。</td>
						  </tr>
						  <tr style=" display:block; margin:25px 0; line-height:24px;">
						    <td>提供包括商品管理，订单处理，客服管理，采购仓储，统计分析等在内的综合功能，能有效提高员工操作效率及准确率，帮助企业完成客户数据分析、全面掌控销售趋势，提高仓储周转率，为外贸电商商户提供一站式的解决方案。</td>
						  </tr>
						  <tr style=" display:block; margin:25px 0; line-height:24px;">
						    <td>帮助中心（<a href="http://help.littleboss.com/word_list.html" target="_blank">http://help.littleboss.com/word_list.html</a>），能够帮助您快速掌握并熟练使用小老板ERP系统。</td>
						  </tr>
						  <tr style=" display:block; margin:25px 0;">
						    <td>此邮件无需直接回复，如有任何疑问请使用下面的联系方式：</td>
						  </tr>
						  <tr style=" display:block; margin:25px 0;">
						    <td>服务热线：021-60950388</td>
						  </tr>
						  <tr style=" display:block; margin:25px 0;">
						    <td>小老板ERP 讨论群：317561579</td>
						  </tr>
						  <tr style=" display:block; margin:25px 0;">
						    <td>论坛：<a href="http://bbs.littleboss.com/" target="_blank">bbs.littleboss.com</a></td>
						  </tr>
						  <tr style=" display:block; margin:25px 0;">
						    <td>微信号：LittleBossERP</td>
						  </tr>
						  <tr>
						    <td><img src="http://www.littleboss.com/images/qrcode.jpg" style="width:200px;" /></td>
						  </tr>
					</table>
				</body>
				</html>
				';
			
			$timeMS1 = TimeUtil::getCurrentTimestampMS();
			$sendResult = $mail->send();
			$timeMS2 = TimeUtil::getCurrentTimestampMS();
			$URLog->send_mail_time = $timeMS2 - $timeMS1;
			$nowTime = time();
			
			//如果没有发送成功，直接反回假
			if($sendResult != 1) {
				$URLog->status = 'send_mail_fail';
				$URLog->update_time = $nowTime;
				if(!$URLog->save(false)){
					\Yii::error('verifyEmail $URLog->save fail 2：'.print_r($URLog->errors,true),"file");
				}
				return "发送邮件失败，请重试";
			}else{
				$URLog->status = 'send_mail_success';
				$URLog->update_time = $nowTime;
				if(!$URLog->save(false)){
					\Yii::error('verifyEmail $URLog->save fail 3：'.print_r($URLog->errors,true),"file");
				}
			}
			
		} catch (\Exception $e) {
			\Yii::error("verifyEmail Exception：".print_r($e,true),"file");
			$errorMessage = "file:".$e->getFile().", line:".$e->getLine().", message:".$e->getMessage();
			
			$URLog->error_message = $errorMessage;
			$URLog->status = 'send_mail_exception';
			$URLog->update_time = time();
			if(!$URLog->save(false)){
				\Yii::error('verifyEmail $URLog->save fail 4：'.print_r($URLog->errors,true),"file");
			}
			
			return "发送邮件失败";
		}

		//将TOKEN写入数据库
		$oldtoken = UserToken::findOne($sendto_email);
		$nowTime = time();
		if($oldtoken){
			$URLog->status = "before_update_old_token";
			$URLog->update_time = $nowTime;
			if(!$URLog->save(false)){
				\Yii::error('verifyEmail $URLog->save fail 5：'.print_r($URLog->errors,true),"file");
			}
			
			$oldtoken->token = $authnum;
			$oldtoken->create_time = $nowTime;
			return $oldtoken->save();
		}else{
			$URLog->status = "before_save_new_token";
			$URLog->update_time = $nowTime;
			if(!$URLog->save(false)){
				\Yii::error('verifyEmail $URLog->save fail 6：'.print_r($URLog->errors,true),"file");
			}
			
			$usertoken = new UserToken();
			$usertoken->key = $sendto_email;
			$usertoken->token = $authnum;
			$usertoken->create_time = $nowTime;
			return $usertoken->save();
		}
	}

	/**
	 +----------------------------------------------------------
	 * 发送  Ensogo 短信验证码
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @invoking IndexHelper::verifyPhone('xxxxxxxx', 'xxxx');
	 +----------------------------------------------------------
	 * @param $phoneNumber			手机号码
	 +----------------------------------------------------------
	 * @return array(boolean,string) 
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2016/07/13				初始化
	 +----------------------------------------------------------
	 **/
	static public function verifyPhone($phoneNumber){
		$tryMax = 20;// 一个电话号码支持发送验证码次数 
		
		//生成 6位验证码
		$verifyCode = null;
		$length = 6;
		$strPol = "0123456789";
		$max = strlen($strPol)-1;
	
		for($i=0;$i<$length;$i++){
			$verifyCode.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
		}
		
		// 同一个电话号码 发送验证短信次数限制
		$sentCount = self::getVerifyPhoneTimesFromRedis($phoneNumber);
		if(empty($sentCount)) $sentCount = 0;
		if ($sentCount >= $tryMax){
			return array(false ,'号码:'.$phoneNumber.' 已经发送了'.$tryMax.'次,若未收到短信，请寻求客服帮助， 谢谢合作！');
		}
		
		//发送 短信
		list($success , $msg) = SMSHelper::sendVerifyCode($phoneNumber, $verifyCode);
		
		//写文件日志
		\Yii::info("verifyPhone at ".date("Y-m-d H:i:s")." use $phoneNumber send a VerifyCode :".$verifyCode,'file');
		if ($success){
			self::setVerifyPhoneTimesToRedis($phoneNumber, $sentCount+1);
			
			//将TOKEN写入数据库
			$oldtoken = UserToken::findOne($phoneNumber);
			$nowTime = time();
			if($oldtoken){
				$oldtoken->token = $verifyCode;
				$oldtoken->create_time = $nowTime;
				if(!$oldtoken->save()){
					\Yii::error("oldtoken->save false:".json_encode($usertoken->errors),'file');
					return array(false , "验证码记录失败，请重试！");
				}else{
					return array(true , "");
				}
			}else{
				$usertoken = new UserToken();
				$usertoken->key = $phoneNumber;
				$usertoken->token = $verifyCode;
				$usertoken->create_time = $nowTime;
				if(!$usertoken->save()){
					\Yii::error("usertoken->save false:".json_encode($usertoken->errors),'file');
					return array(false , "验证码记录失败，请重试！");
				}else{
					return array(true , "");
				}
			}
		}else{
			return array(false , $msg);
		}
	}
	
	// 获取电话号码已发送验证码次数
	private static function getVerifyPhoneTimesFromRedis($phoneNumber){
		$key = 'verify_phone_'.$phoneNumber;
		//return \Yii::$app->redis->hget("verify_phone_counter",$key);
		return RedisHelper::RedisGet("verify_phone_counter",$key );
	}
	
	// 设置电话号码已发送验证码次数
	private static function setVerifyPhoneTimesToRedis($phoneNumber,$val){
		$key = 'verify_phone_'.$phoneNumber;
		//return \Yii::$app->redis->hset("verify_phone_counter",$key,$val);
		return RedisHelper::RedisSet("verify_phone_counter",$key,$val);
	}
	
	/**
	+----------------------------------------------------------
	 * 验证成功用户创建数据库
	+----------------------------------------------------------
	 * @params	$uid		用户id
	+----------------------------------------------------------
	 * @access public
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	+----------------------------------------------------------
	**/
	public static function doActivation($uid, $email) {
		if (UserDatabase::find()->where("`uid` = {$uid} AND `user_name` = '{$email}'")->count() > 0) {
			\Yii::error('UserDatabase：中止用户创建数据库，user_name:' . $email.', uid:' . $uid .'记录已存在！' ,"file");
			return false;
		}
		//写入建设子用户的数据库表
		list($dbserverId,$host,$dbusername,$password)=\Yii::$app->subdb->getRegisterAccountDBInfo();
		
		
		//$newDbServerId=2;
		$userDatabase = new UserDatabase();
		$userDatabase->did = $uid;		
		$userDatabase->uid = $uid;
		$userDatabase->user_name = $email;
		$userDatabase->status = 0;
		
		$userDatabase->dbserverid = $dbserverId;
		$userDatabase->ip = $host;
		$userDatabase->dbusername = $dbusername;
		$userDatabase->password = $password;
		$result = $userDatabase->save(false);
		
		if(!$result) {
			\Yii::error('UserDatabase：user_name:' . $email.', uid:' . $uid .'创建record失败！' ,"file");
			return false;
		}
		
		try{
		    //lolo20151214 db_split
			//\Yii::$app->db->createCommand("CREATE DATABASE `user_{$uid}` CHARACTER SET utf8 COLLATE utf8_general_ci;")->execute();
			
		    $conn = new \mysqli($host,$dbusername,$password);
		    $ret=$conn->query("CREATE DATABASE `user_{$uid}` CHARACTER SET utf8 COLLATE utf8_general_ci;");
		    if ($ret===true){
		        \Yii::info('UserDatabase：user_name:' . $email.', uid:' . $uid .' database created!!' ,"file");
		    }else{
		        \Yii::error('UserDatabase：user_name:' . $email.', uid:' . $uid .' creation fails!!' ,"file");
		        return false;
		    }
	
			
			$dbM = new EDBManager($host,$dbusername,$password,"user_{$uid}");
			return $dbM->createFromFile(\Yii::getAlias('@eagle').'/doc/sql/user_0.sql',null,'');
		} catch (\Exception $e) {
			\Yii::error("自动激活doActivation Exception:".print_r($e,true),"file");
			return false;
		}
	}

	/**
	 +----------------------------------------------------------
	 * 根据uid获取用户主表信息
	 +----------------------------------------------------------
	 * @params	$uid_array		array of 用户id
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		qill		2014/06/26				初始化
	 +----------------------------------------------------------
	**/
	public static function getUserNameByIds($uid_array) {
		if(empty($uid_array)) {
			return false;
		}

		$user = array();
		foreach ($uid_array as $anUserId) {
			//check if this user id has been looked up before, if no, load it
			if (! isset(self::$MapOrUserIdName[$anUserId])){
				//get the model and return the name only, not entire model data
				$userModel = UserBase::model()->findByPk($anUserId);
				if ($userModel <> null)
					self::$MapOrUserIdName[$anUserId] = $userModel->user_name;
				else 
					self::$MapOrUserIdName[$anUserId] = "N/A";
			}

			$user[$anUserId] = self::$MapOrUserIdName[$anUserId];
		}//end of each user Id

		return $user;
	}

	/**
	 +----------------------------------------------------------
	 * 根据uid获取用户主表信息
	 +----------------------------------------------------------
	 * @params	$uid		用户id
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/06/26				初始化
	 +----------------------------------------------------------
	**/
	public static function getUserNameById($uid) {
		$rtn = self::getUserNameByIds(array($uid));	
		if (isset($rtn[$uid]))
			return $rtn[$uid];
		else
			return "";
	}

	/**
	+----------------------------------------------------------
	 * 取得客户端IP地址
	+----------------------------------------------------------
	 * @access public
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxlu    2014/06/26				初始化
	+----------------------------------------------------------
	**/
	public static function getClientIP(){
		if(!empty($_SERVER["HTTP_CLIENT_IP"])){
			return $_SERVER["HTTP_CLIENT_IP"];
		}elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		}elseif(!empty($_SERVER["REMOTE_ADDR"])){
			return $_SERVER["REMOTE_ADDR"];
		}elseif (getenv("HTTP_X_FORWARDED_FOR")){
			return getenv("HTTP_X_FORWARDED_FOR");
		}elseif (getenv("HTTP_CLIENT_IP")){
			return getenv("HTTP_CLIENT_IP");
		}elseif (getenv("REMOTE_ADDR")){
			return getenv("REMOTE_ADDR");
		}else{
			return "127.0.0.1";
		}
	}
	
	/**
	+----------------------------------------------------------
	 * 取得user_config "用户表全局性的配置" 记录
	+----------------------------------------------------------
	 * @params	$key |  查询的键
	+----------------------------------------------------------
	 * @access public
	+----------------------------------------------------------
	 * @return  对应$key记录的信息
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxlu    2014/06/26				初始化
	+----------------------------------------------------------
	**/
	public static function getUserConfig($key){
		return UserConfig::model()->findByPk($key);
	}
	
	/**
	+----------------------------------------------------------
	 * 取得user_config "用户表全局性的配置" 记录
	+----------------------------------------------------------
	 * @params	$key |  查询的键
	 * @params	$value |  值
	 * @params	$type|  类型
	 * @params	$description |  该记录的描述信息
	+----------------------------------------------------------
	 * @access public
	+----------------------------------------------------------
	 * @return 对应$key记录的信息
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxlu    2014/06/26				初始化
	+----------------------------------------------------------
	**/
	public static function setUserConfig($key, $value=null, $type=null, $description=null){
		$userconfig = UserConfig::model()->findByPk($key);
		if(!$userconfig){
			$userconfig = new UserConfig;
			$userconfig->$key         = $key;
			$userconfig->$create_time = time();
			$userconfig->$update_time = time();
			$userconfig->$value       = $value;
			$userconfig->$type        = $type;
			$userconfig->$description = $description;
			$userconfig->save();
		}else{
			if($value) $userconfig->$value       = $value;
			if($type) $userconfig->$type        = $type;
			if($description) $userconfig->$description = $description;
			$userconfig->$update_time = time();
			$userconfig->save();
		}
		return $userconfig; 
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 获取用户注册应用key
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/07				初始化
	 +----------------------------------------------------------
	 **/
	public static function getUserChosenApp($email) {
		if(isset($email)){
			return UserRegisterApp::findOne($email);
		}else 
			return null;
		
	}
	
	// 生成find_pass_token 
	public static function generatePasswordResetToken(){
		return \Yii::$app->security->generateRandomString() . '_' . time();
	}
	
	// 通过find_pass_token 获取用户对象
	public static function findUserByPasswordResetToken($token){
		// 先检验find_pass_token 是否过期
		if( !self::isPasswordResetTokenValid($token)){
// 			return ResultHelper::getResult(400, '', 'Password reset token expired' , 0);
			return ResultHelper::getResult(400, '', '重置密码链接已过期，请重发邮件' , 0);
		}
		
		$userToken = UserToken::findOne(['find_pass_token' => $token]);
		if( $userToken == null ){
// 			return ResultHelper::getResult(400, '', 'Password reset token not exist.' , 0);
			return ResultHelper::getResult(400, '', '重置密码链接不存在' , 0);
		}
		
		$user = UserBase::findOne([ 'email' => $userToken->key ]);
		if( $user == null ){
// 			return ResultHelper::getResult(400, '', 'Wrong password reset token.' , 0);3
			return ResultHelper::getResult(400, '', '重置密码链接错误' , 0);
		} else {
			return ResultHelper::getResult(200, $user , '' , 0);
		}
	}
	
	// 验证find_pass_token 是否过期 
	public static function isPasswordResetTokenValid($token) {
		if (empty($token)) {
			return false;
		}
		$expire = 86400; // a day
		$parts = explode('_', $token);
		$timestamp = (int) end($parts);
		return $timestamp + $expire >= time();
	}
	
	// 发送修改密码链接到用户邮箱
	public static function sendResetTokenLinkToUserMail($get) {
		$email = $get['email'];
		$userTokenObj = UserToken::findOne([
			'key' => $email,
		]);
		
		if ($userTokenObj) {
			if (!self::isPasswordResetTokenValid($userTokenObj->find_pass_token)) {
				$userTokenObj->find_pass_token = self::generatePasswordResetToken();
			}
			
			
			if ($userTokenObj->save()) {
				$mail = new \PHPMailer();
				$mail->CharSet='UTF-8';
				$mail->IsSMTP();                            // 经smtp发送
				$mail->Host     = "smtp.163.com";           // SMTP 服务器
				$mail->SMTPAuth = true;                     // 打开SMTP 认证
				// TODO add send mail info @xxx@
				$mail->Username = "@xxx@@163.com";    // 用户名
				$mail->Password = "@xxx@";// 密码
				$mail->From     = "@xxx@@163.com";            // 发信人
				$mail->FromName = "小老板 ERP";        // 发信人别名
				$mail->AddAddress($email);                 // 收信人
				$mail->WordWrap = 50;
				$mail->IsHTML(true);                            // 以html方式发送
				$mail->AltBody  =  "请使用HTML方式查看邮件。";
				
				if(!isset($get["source"]))
					$get["source"] = 'v2';
				$backendUrl = AppApiHelper::getResetTokenBackendUrl($get["source"]);
				if($backendUrl != false){
					$resetLink = $backendUrl.'/site/reset-password?token='. $userTokenObj->find_pass_token;
				}else{
					$resetLink = \Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $userTokenObj->find_pass_token]);
				}
				
				// 邮件主题
				$mail->Subject = '重置密码';
				//如果标题有中文，用下面这行
				//$mail->Subject = "=?utf-8?B?" . base64_encode("信件标题") . "?=";
				//邮件内容
				$mail->Body =  '
				<html>
				<head>
				<meta charset="UTF-8">
				</head>
				<body>
					<table width="700" border="0" cellspacing="0" cellpadding="0"
						align="center" style="font-size: 14px;">
						<tr>
							<td>
								<a href="www.littleboss.com" target="_blank">
									<img
										src="http://www.littleboss.com/images/logo.png"
										style="border: 0; width: 180px;" />
								</a>
							</td>
						</tr>
						<tr>
							<td>尊敬的小老板用户</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>您好！</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>请点击如下验证链接来重置密码：</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>
								<a href="'.$resetLink.'" target="_blank">'.$resetLink.'</a>
							</td>
						</tr>
				
						<tr style="display: block; margin: 25px 25px;">
							<td>如果上面的链接无法点击，您也可以复制链接，粘贴到您浏览器的地址栏内，然后按“回车”键打开预设页面，完成相应功能。</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>验证链接将会在24小时后失效，请尽快完成密码重置，否则需要重新发送验证链接。</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>如果有其他问题，请联系我们：qianqian.zhu@littleboss.com 谢谢！</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>此邮件无需直接回复，如有任何疑问请使用下面的联系方式：</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>服务热线：021-65343625</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>小老板ERP 讨论群：317561579</td>
						</tr>
						<tr style="display: block; margin: 25px 25px;">
							<td>微信号：LittleBossERP</td>
						</tr>
						<tr>
							<td>
								<img
									src="http://www.littleboss.com/images/qrcode.jpg"
									style="width: 200px;" />
							</td>
						</tr>
					</table>
				</body>
				</html>
				';
				$sendResult = $mail->send();
				//如果没有发送成功，直接反回假
				if($sendResult != 1) 
					return false;
				else 
					return true;
			}
		}
		
		return false;
	}
	
}
