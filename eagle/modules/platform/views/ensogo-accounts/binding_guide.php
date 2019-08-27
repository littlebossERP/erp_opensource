<?php 
use eagle\modules\app\apihelpers\AppApiHelper;
$bindingLink = AppApiHelper::getAutoLoginPlatformBindUrl();
?>
<div>
<p><font color="red">授权时需要登录wish商户平台，为避免账号关联，建议使用该店铺常用的电脑完成授权操作。建议使用谷歌浏览器。</font></p>
<p><font color="red">wish官方已启用新的授权方式。在小老板已授权的店铺需在2015年11月1日前完成【重新绑定】，过期则绑定失效。</font></p>
<h4>第一步</h4>
<p>未授权任何店铺的情况下，直接点击右上角的平台绑定。也点击<a href="<?= $bindingLink?>" target="_blank">【平台绑定】</a>进入绑定页面。</p>

<p><img src="/images/wish/wish-v2-binding-guide-step-1.png" alt=" "></p>
<p>点击【添加绑定】，自定义一个店铺名称，提交【新建】。</p>
<p><img src="/images/wish/wish-v2-binding-guide-step-2.png" alt=" "></p>
<p>　若是对已有的绑定【重新绑定】，则直接点击相应的操作。</p>
<p><img src="/images/wish/wish-v2-binding-guide-step-3.png" alt=" "style="width: 750px;"></p>

<h4>第二步</h4>
<p>提交授权后，将在新页面打开wish商户平台，使用您要授权的wish店铺账号完成登录，跳转至如下页面，按页面提示逐步完成【Accept】操作。</p>
<p><img src="/images/wish/wish-v2-binding-guide-step-4.png" alt=" " style="width: 750px;"></p>

<p><img src="/images/wish/wish-v2-binding-guide-step-5.png" alt=" "></p>
<p>【Accept】确认后，将自动跳转至如下页面，并提示授权成功。</p>
<p><img src="/images/wish/wish-v2-binding-guide-step-6.png" alt=" "></p>

</div>