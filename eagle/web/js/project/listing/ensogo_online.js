
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
                $.get('/manual_sync/sync/get-queue',{
                    site_id:site_id,
                    type:'ensogo:product'
                }).done(function(res){

                    var progress = new Progress(self);
                    progress.url = '/manual_sync/sync/get-progress';
                    progress.params = {
                        site_id:site_id,
                        type:'ensogo:product'
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
                        $el(".text-danger").show();
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
        sync($el("#ensogo_modal_site_id").val());
    });
    $el("#ensogo_modal_site_id").on('change',function(){
        $el(".sync-done").hide();
        $el(".sync-cancel").hide();
        $el(".sync-start").show();
    })

});