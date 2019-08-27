$.domReady(function($el){
    var $doc = this,
        sync = function(site_id){

            // 同步功能
            $el('.iv-progress').registerPlugin('Progress',function(Progress){
                var self = this;

                $el("select").attr('disabled','disabled');
                $el(".text-success").hide();
                $el(".text-danger").hide();
                $el(".sending").show();
                $el(".sync-start").hide();
                $el(".sync-done").hide();
                $el(".sync-cancel").show();

                // 开始同步
                $.get('/order/ebay-order/get-queue',{
                    site_id:site_id,
                    sync_order_time:$("input#sync_order_time").val(),
                }).done(function(res){
					if(!res.success){
						if(res.code!=undefined){
							bootbox.alert({
								title:'错误操作',
								message:Translator.t('后台传输出错，请联系客服'),
								callback:function(){
									$("a[data-event='reject']").click();
								}
							});
						}else{
							bootbox.alert({
								title:'后台错误',
								message:res.message,
								callback:function(){
									$("a[data-event='reject']").click();
								}
							});
						}
						return false;
					}
                    var progress = new Progress(self);
                    progress.url = '/order/ebay-order/get-progress';
                    progress.params = {
                        site_id:site_id,
                        //type:sync_order_time
                    };
                    progress.start(function(response){
                        $(self).find('[data-count]').text(response.progress);
                    },1500);

                    progress.done(function(e,response){
                        $(self).find('[data-count]').text(response.progress);
                        $el(".sync-cancel").hide();
                        $el(".sync-done").show();
                        $el(".text-success").show();
                        $el(".sending").hide();
                        $el("select").removeAttr('disabled');
                    });
                    progress.fail(function(e,response){
                        $el(".sync-cancel").hide();
                        $el(".sync-done").show();
                        $el(".text-danger").text('同步失败，'+response.message).show();
                        $el(".sending").hide();
                        $el("select").removeAttr('disabled');
                    });
                    $doc.on('close',function(){
                        progress.stop();
                    });
                });

            });

        };
    // 触发
    $el(".sync-start").on('click',function(){
		if($el("#ebay_uid").val()=='' || $el("#ebay_uid").val()==0){
			bootbox.alert(Translator.t('请选择店铺'));
			return;
		}
		
        sync($el("#ebay_uid").val());
    });
    $el("#ebay_uid").on('change',function(){
        $el(".sync-done").hide();
        $el(".sync-cancel").hide();
        $el(".sync-start").show();
    });

});

