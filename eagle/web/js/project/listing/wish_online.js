
var fail = 0;
var success = 0;

$(function(){
    $('#wish_site_id').change(function(){
        $('#wish_form').submit();
    });
   
    if($(".slide-toggle i").hasClass('right')){
        setTimeout(function(){
            $('.slide-toggle').click();
        },5000);  
    }

    $('#wish_site_id').click(function(){
       if($(this).find('option').length == 1 && $(this).find('option').val() =='0'){
        $('.alert').remove();
        tips({type:'error',msg:'请重新绑定店铺,您的店铺可能未绑定或token失效'}); 
       }
    });  

    $('.dropdown-menu li a').click(function(){
        $('input[name="select_status"]').val($(this).parent().attr('name'));
        $('.wish_search').html($(this).html()+' <span class="glyphicon glyphicon-chevron-down">');
        $("#search_condition").val($(this).parent().attr('name'));
    });

    //修改在线商品
    $(".product_edit").click(function(){
        var enable = $("input[name=enable]").val() ;
        if(enable == 'Y'){
            window.location = "/listing/wish/online-fan-ben-edit?id="+$(this).data('id');
        } else {
            window.location = "/listing/wish/offline-fan-ben-edit?id="+$(this).data('id');
        }
    });

    //产品状态变更
    $("#product_status").change(function(){
        $("#pstatus").val($(this).val());
        $("#wish_form").submit();
    });

    //排序
    $("#saves").click(function(){
        var sort = $(this).data('sort');
        sort = sort =='saves-up' || sort == '' ? 'saves-down' : 'saves-up';
        $("#sort").val(sort);
        $("#wish_form").submit();
    });

    $("#sold").click(function(){
        var sort = $(this).data('sort');
        sort = sort =='sold-up' || sort == '' ? 'sold-down' : 'sold-up';
        $("#sort").val(sort);
        $("#wish_form").submit();
    });

    //下架在线商品
    $(".product_change").click(function(){
        var id = $(this).data('id');
        var status = $(this).data("status");

        if(id != null || status != null){
            bootbox.dialog({message:"正在变更商品状态"});
            $.post("/listing/wish-online/product-change",{
                id : id,
                status : status
            },function(data){
                if(data.code == 200){
                    $('.bootbox-body').text(data.message);
                    window.location.reload(true);
                } else {
                    $('.bootbox-body').text(data.message);
                }
            },'json');
        } else {
            return false;
        }
    });

    //下架变种
    $(".product_variaition_change").click(function(){
        var id = $(this).data('id');
        var status = $(this).data("status");
        if(id != null || status != null){
            bootbox.dialog({message:"正在变更商品状态"});
            $.post("/listing/wish-online/product-variation-change",{
                vid : id,
                status : status
            },function(data){
                if(data.code == 200){
                    $('.bootbox-body').text(data.message);
                    window.location.reload(true);
                } else {
                    $('.bootbox-body').text(data.message);
                }
            },'json');
        } else {
            return false;
        }
    });

    //批量上下架
    $(".check_product_online").click(function(){
        if($(this).is(":checked")){
            $(".check_product").prop("checked",true);
        } else {
            $(".check_product").removeAttr("checked");
        }
    });

    //商品选择
    $(".product").click(function(){
        var _id = $(this).data("id");
        if($(this).is(":checked")){
            $(".product_" + _id).prop("checked",true);
        } else {
            $(".product_" + _id).removeAttr("checked");
        }
    });

    $('#batch_modify').click(function(){
        if($('.product:checked').length <= 0){
            tips({type:'error',msg:'请至少选中一件商品！',existTime:3000});
            return false;
        }
        $('#wish_batch_modify input[name="site_id"]').val($('#wish_site_id').val());
        $(this).attr('disabled','disabled');
        var modify_ids = [];
        $('.product:checked').each(function(){
            var product_id = $(this).data('id');
            if($('#wish_batch_modify input[name="variance_'+product_id+'[]"]').val() != product_id){
                $('#wish_batch_modify').append('<input type="hidden" name="product_id[]" value="'+product_id+'">');
            }
            $(".product_"+ product_id + ":checked").each(function(){
                if($('#wish_batch_modify input[name="variance_'+product_id+'[]"]').val() != $(this).val()){
                    $('#wish_batch_modify').append('<input type="hidden" name="variance_'+product_id+'[]" value="'+$(this).val()+'">');
                }
            });

        });
        $(this).removeAttr('disabled');
        $('#wish_batch_modify').submit();

    });

    // $('.title_modify').click(function(){
    //     $('#modify-modal-title').html('批量修改标题');
    //     $('#modify-modal').modal('show');
    // });

    //变种选择
    $(".variation").click(function(){
        var _id = $(this).data("pid");
        if($(this).is(":checked") && !$("#product_"+_id).is(":checked")){
            $("#product_" + _id).prop("checked",true);
        } else {
            var type = false;
            $(".product_" + _id).each(function(){
                if($(this).is(":checked")){
                    type = true;
                }
            });
            if(!type){
                $("#product_" + _id).removeAttr("checked");
            }
        }
    });

    //展开显示
    $(".wish_product_show").click(function(){
        var _id = $(this).data("id");
        var product_count = $('.wish_fanben_id_list').length;
        if($(this).hasClass("glyphicon-plus")){
            $(this).removeClass("glyphicon-plus");
            $(this).addClass("glyphicon-minus").parents('td').attr('rowspan',$(this).parents('td').data('rowspan'));
            $(".variation_"+_id).show();
            if(product_count == 1){
                $(".wish_product_all_show").removeClass('glyphicon-plus').addClass('glyphicon-minus');
            }
        } else {
            $(this).removeClass("glyphicon-minus").parents('td').removeAttr('rowspan');
            $(this).addClass("glyphicon-plus");
            $(".variation_"+_id).hide();
            if(product_count == 1){
                $(".wish_product_all_show").removeClass('glyphicon-minus').addClass('glyphicon-plus');
            }
        }
    });

      //展开显示
    $(".product_show").click(function(){
        var _id = $(this).data("id");
        if($(this).hasClass("glyphicon-plus")){
            $(this).removeClass("glyphicon-plus");
            $(this).addClass("glyphicon-minus");
            $(".variation_"+_id).show();
        } else {
            $(this).removeClass("glyphicon-minus");
            $(this).addClass("glyphicon-plus");
            $(".variation_"+_id).hide();
        }
    });

    //展示所有商品
    $(".product_all_show").click(function(){
        if($(this).hasClass("glyphicon-plus")){
            $(this).removeClass("glyphicon-plus");
            $(this).addClass("glyphicon-minus");

            $(".product_show").removeClass("glyphicon-plus");
            $(".product_show").addClass("glyphicon-minus");

            $(".variation_tr").show();
        } else {
            $(this).removeClass("glyphicon-minus");
            $(this).addClass("glyphicon-plus");
            $(".product_show").removeClass("glyphicon-minus");
            $(".product_show").addClass("glyphicon-plus");
            $(".variation_tr").hide();
        }
    });

    //展示所有商品
    $(".wish_product_all_show").click(function(){
        if($(this).hasClass("glyphicon-plus")){
            $(this).removeClass("glyphicon-plus");
            $(this).addClass("glyphicon-minus");
            $('.wish_product_show').each(function(){
               $(this).removeClass("glyphicon-plus").addClass("glyphicon-minus").parents('td').attr('rowspan',$(this).parents('td').data('rowspan')); 
            });
            $(".variation_tr").show();
        } else {
            $(this).removeClass("glyphicon-minus");
            $(this).addClass("glyphicon-plus");
            $(".wish_product_show").removeClass("glyphicon-minus").addClass("glyphicon-plus").parents('td').removeAttr('rowspan'); 
            $(".variation_tr").hide();
        }
    });


    //批量下架
    $("#batch_disabled").click(function(){
        var vid = checkSelect();
        var count = vid.length;
        var status = $(this).data('status');
        if(count == 0){
            // alert('至少选择一个商品！');
            tips({type:"error",msg:'至少选择一个商品',existTime: 3000});
            return false;
        } else {
            bootbox.dialog({message:'正在变更' + count + "商品状态"});
            for(var i=0;i<count; i++){
                $.post("/listing/wish-online/product-variation-change",{
                    vid : vid[i],
                    status : status
                },function(data){
                    if(data.code == 200){
                        success++;
                    } else {
                        fail++;
                    }
                    reloadHtml(count,success,fail);
                    $('.bootbox-body').text('总共变更商品：'+count+' 成功：'+success+'失败：'+fail);
                },'json');
            }
            fail = 0;
            success=0;
        }

    });
    //同步商品
    // $("#batch_sync").click(function(){
    //     $("#wish_modal_site_id").val(0);
    //     $("#wish_log").html('');
    //     $("#sync_modal").modal("show");

    // });

    $('.error_tips').popover({'trigger':'hover'});

    var timmer;


    // $('#batch_sync').click(function(){
    //     $('.sync_tips').html('<img src="/images/wish/ajax-loader.gif" width="30" height="30">');
    //     if($('select option[data-type="site_id"]').length == 0){
    //         tips({type:'error',msg:'请重新绑定店铺,您的店铺可能未绑定或token失效'});
    //         return false;
    //     }
    //     $('#sync_modal').modal("show");
    //     if($('select option[data-type="site_id"]').length == 1){
    //         var site_id = $('select option[data-type="site_id"]:selected').val();
    //         syncProduct(site_id,function(syncInfo){
    //             console.log(syncInfo);
    //             if(syncInfo['success'] == true){
    //                 var syncId = syncInfo['id'];
    //             }
    //             console.log(syncId);
    //             if(syncId == undefined || syncId == 0){
    //                 tips({type:'error',msg:'获取同步信息进度失败'});
    //                 return false;
    //             }
    //             timmer = setInterval(function(){
    //                 console.log(syncId);
    //                 getQueueProcess(site_id,syncId); 
    //             },1000);
    //         });
    //     }
    // });

    $('#wish_modal_site_id').change(function(){
        var site_id = $('select option[data-type="site_id"]:selected').val(); 
        syncProduct(site_id,function(syncInfo){
            if(syncInfo['success'] == true){
               var syncId = syncInfo['id']; 
            }
            if(syncId == undefined || syncId == 0){
                tips({type:'error',msg:'获取同步信息进度失败'});
            return false;
            }
            timmer = setInterval(function(){
                getQueueProcess(site_id,syncId);
            },1000);                    
    });

    });


    function syncProduct(site_id,fn){
        $.post("/listing/wish-online/realtime-sync",{site_id:site_id},function(data){
            fn.call(this,data);
        });
    }


    function getQueueProcess(site_id,syncId){
        $.post("/listing/wish-online/get-queue-process",{site_id:site_id,syncId:syncId},function(data){ 
            console.log(data);
            if(data['status'] == 'completed'){
                clearInterval(timmer);
                $('.sync_tips').html('<span style="color:green;line-height:30px;">同步完成</span>');
                if(data['total_product'] != null){
                    $('.sync_finished_num').html(data['total_product']);
                }
            }else if(data['status'] == 'pending'){
                $('.sync_finished_num').html(data['completed_num']);
            }else{
                if(data['success'] == false){
                    $('.sync_tips').html('<span style="color:red;line-height:30px;">'+data['message']+'</span>');
                }
            }
        });
    }
    // //同步商品选择店铺信息
    // $("#wish_modal_site_id").change(function(){
    //     var site_id = $(this).val();
    //     $.post("/listing/wish-online/check-sync-status/",{
    //         type : site_id
    //     },function(data){
    //         if(data.code == 200){
    //             $("#wish_commit").show();
    //         } else {
    //             $("#wish_commit").hide();
    //         }
    //         $("#wish_log").html(data.message);
    //         return false;
    //     },'json');
    // });

    $("#wish_commit").click(function(){
        var site_id = $("#wish_modal_site_id").val();
        if(site_id == 0){
            $("#wish_log").html("至少选择一个店铺信息");
            return false;
        } else {
            $("#wish_modal_footer button").hide();
            $("#wish_modal_site_id").prop("disabled",true);
            $("#wish_log").html("正在同步商品，请耐心等待.....");
            $.post("/listing/wish-online/sync-product-info",{
                site:site_id
            },function(data){
                $("#wish_log").html(data.message);
                $("#wish_modal_footer button").show();
                $("#wish_modal_site_id").removeProp("disabled");
                $("#wish_commit").hide();
            },'json');

        }
    });

    $('#wish_flash').click(function(){
        window.location.reload();
    });

    $("#batch_edit").click(function(){
        var ids = getVid();

        if(ids.length == 0){
            bootbox.dialog({message:"至少选择一个商品"});
            return false;
        } else {
            $("#edit_modal").modal("show");
        }
        console.log(ids);
    });

    //批量修改
    $("#wish_edit_model").change(function(){
        var title = $("#wish_edit_model").val();
        var type = eval('('+  global.wish_type+ ')');
        var data = type[title];
        $("#wish_content").html('');
        if(data != ''){
            $("#wish_edit_type").empty();
            $("#wish_edit_type").append('<option value="0">请选择</option>');
            if(title == 'title'){
                $("#wish_edit_type").append('<option value="fadd">'+data.fadd+'</option>');
                $("#wish_edit_type").append('<option value="badd">'+data.badd+'</option>');
                $("#wish_edit_type").append('<option value="fdel">'+data.fdel+'</option>');
                $("#wish_edit_type").append('<option value="bdel">'+data.bdel+'</option>');
                $("#wish_edit_type").append('<option value="rp">'+data.rp+'</option>');
            } else {
                $("#wish_edit_type").append('<option value="add">'+data.add+'</option>');
                $("#wish_edit_type").append('<option value="divide">'+data.divide+'</option>');
                $("#wish_edit_type").append('<option value="minus">'+data.minus+'</option>');
                $("#wish_edit_type").append('<option value="ride">'+data.ride+'</option>');
                $("#wish_edit_type").append('<option value="rp">'+data.rp+'</option>');
                if(title == 'price'){
                    $("#wish_edit_type").append('<option value="increase">'+data.increase+'</option>');
                    $("#wish_edit_type").append('<option value="reduction">'+data.reduction+'</option>');
                }
                if(title == 'inventory'){
                    $("#wish_edit_type").append('<option value="conditionadd">'+data.conditionadd+'</option>');
                    $("#wish_edit_type").append('<option value="conditionmakeup">'+data.conditionmakeup+'</option>');
                }
            }
        }
    });

    $("#wish_edit_type").change(function(){
        var val = $(this).val();
        $("#wish_content").html('');
        switch(val){
            case 'fadd' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/>');
                break;
            case 'badd' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/>');
                break;
            case 'fdel' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/>');
                break;
            case 'bdel' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/>');
                break;
            case 'rp' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/><br/>替换<br/><input type="text" name="content_replace" style="width: 400px"/>');
                break;
            case 'add' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/>');
                break;
            case 'divide' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/>');
                break;
            case 'increase' :
                $("#wish_content").html('<span>价格增加%</span><input type="text" name="content" style="width: 330px"/>');
                break;
            case 'minus' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/>');
                break;
            case 'reduction' :
                $("#wish_content").html('<span>价格减少%</span><input type="text" name="content" style="width: 330px"/>');
                break;
            case 'ride' :
                $("#wish_content").html('<input type="text" name="content" style="width: 400px"/>');
                break;
            case 'conditionadd' :
                $("#wish_content").html('<span>库存少于</span><input type="text" name="content" style="width: 100px"/><span>则加</span><input type="text" name="content_replace" style="width: 200px"/>');
                break;
            case 'conditionmakeup':
                $("#wish_content").html('<span>库存少于</span><input type="text" name="content" style="width: 100px"/><span>则补充到</span><input type="text" name="content_replace" style="width: 200px"/>');
                break;
            default :
                return false;
                break
        }
    });

    //批量修改提交
    $("#wish_edit_commit").click(function(){
        var wish_model = $("#wish_edit_model").val();
        var wish_type = $("#wish_edit_type").val();
        var content = $("input[name=content]").val();
        var content_rp = '';

        if(wish_model == 'title' && wish_type == 'rp' ){
             content_rp = $("input[name=content_replace]").val();
        }else if( (wish_model == 'title' && wish_type == 'increase') || (wish_model == 'title' && wish_type == 'reduction' ) || (wish_model == 'inventory' && wish_type == 'conditionadd' ) || (wish_model == 'inventory' && wish_type == 'conditionmakeup' ) ){
             content_rp = $("input[name=content_replace]").val();
        }

        if(wish_model == 'title'){
            var ids = getVid();
        } else {
            var ids = getPVid();
        }
        var count = ids.length;

        bootbox.dialog({message:'正在更新' + ids.length + "商品"});
        for(var i=0;i<count;i++){

            $.post("/listing/wish-online/product-save",{
                wish_model : wish_model,
                wish_type : wish_type,
                content : content,
                content_rp : content_rp,
                pid : ids[i]
            },function(data){
                if(data.code == 200){
                    success++;
                } else {
                    fail++;
                }
                reloadHtml(count,success,fail);
                $('.bootbox-body').text('总共变更商品：'+ids.length+' 成功：'+success+'失败：'+fail);
            },'json');
        }
        success = 0;
        fail = 0;
    });
    
    $('#wish_search_btn').click(function(){
        $site_id = $('#wish_site_id').val();
        if($site_id == 0 || $site_id == undefined){
            alert('请选择绑定的wish店铺;如若没有,请绑定wish店铺!');
            return false;
        }
    });

});

function checkSelect(){
    var str = [];
    $(".variation").each(function(){
        if($(this).is(":checked")){
            str.push($(this).val());
        }
    });
    return str ;
}

function getVid(){
    var ids = [];
    var old_id = 0;
    var pid = 0;
    $(".variation").each(function(){
        if($(this).is(":checked")){
             pid = $(this).data('pid');
            if(old_id != pid){
                ids.push(pid);
                old_id = pid;
            }
        }
    });
    return ids;
}

function getPVid(){
    var ids = [];
    $(".variation").each(function(){
        if($(this).is(":checked")){
            ids.push($(this).val());
        }
    });
    return ids;
}

function reloadHtml(count,success,fail){
    if(count == success+fail){
        $.location.reload(true);
    }
}

function tips(args){

    var tips = args['type'];
    var tips_content= args['msg'];
    if(args['existTime'] != undefined){
        var tips_time = args['existTime'];
    }
    // console.log(tips);
    // alert(tips_content);
    // alert(tips);
    if(tips == 'error'){
        $warning = '错误提醒:';
        $colorclass = 'alert-danger';
    }else{
        $warning = '温馨提示:';
        $colorclass = 'alert-success';
    }
    $content = ' <div class="alert '+ $colorclass +'" role="alert" style="z-index: 9999999; width: 680px; left: 30%; right: 30%; margin: auto; top: 8%; position: fixed;"><button type="button" class="close" data-dismiss="alert">×</button>';
    $tip = '<div class="pull-left mLeft10"><strong>'+ $warning+'</strong></div>';
    $tip_content = '<div class="pull-left mLeft10"><span>'+ tips_content +'</span></div>'
    $content += $tip + $tip_content;
    $('.right_content').append($content);
    if(args['existTime'] != undefined){
        setTimeout(function(){
            $('.alert').remove();
        },tips_time);
    }
}


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
                    type:'wish:product'
                }).done(function(res){

                    var progress = new Progress(self);
                    progress.url = '/manual_sync/sync/get-progress';
                    progress.params = {
                        site_id:site_id,
                        type:'wish:product'
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
                        var obj = $.parseJSON(response.queue._data);
                        console.log(obj);
                        $el(".sync-cancel").hide();
                        $el(".sync-done").show();
                        $el(".text-danger").text('同步失败，'+obj.error_message).show();
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
        sync($el("#wish_modal_site_id").val());
    });
    $el("#wish_modal_site_id").on('change',function(){
        $el(".sync-done").hide();
        $el(".sync-cancel").hide();
        $el(".sync-start").show();
    })
    // if($el("#wish_modal_site_id").find('option').size()<2){
    //     sync($el("#wish_modal_site_id").val());
    // }
    
	//Wish在线商品批量修改
	var $document = this;
    $el('#modify_title_ensure').on('click',function(){

        var ids = [];
        var choosed_type = $el('input[name="title_modify_type"]:checked').val();
        switch(choosed_type){
            case 'fadd':
                var add_before_title = $el('input[name="fadd"]').val();
                 $('#wish-online-list tr').find('.modify_title').each(function(){
                    var title = add_before_title !== '' ? (add_before_title + $(this).html()) : $(this).html();
                    $(this).html(title);
                    if($('#modify-post input[name="product_title_'+$(this).data("pid") +'"]').val()){
                        $('#modify-post input[name="product_title_'+ $(this).data("pid") +'"]').val(title);
                    }else{
                        $('#modify-post').append('<input type="hidden" class="modify_data" name="product_title_'+ $(this).data("pid") +'" value="'+ title +'">');
                    }
                });
    
                break;
            case 'badd':
                var add_after_title = $el('input[name="badd"]').val();
                $('#wish-online-list tr').find('.modify_title').each(function(){
                    var title = add_after_title !== '' ?  ($(this).html() + add_after_title) : $(this).html()
                    $(this).html(title);
                    if($('#modify-post input[name="product_title_'+$(this).data("pid") +'"]').val()){
                        $('#modify-post input[name="product_title_'+ $(this).data("pid") +'"]').val(title);
                    }else{
                        $('#modify-post').append('<input type="hidden" class="modify_data" name="product_title_'+ $(this).data("pid") +'" value="'+ title +'">');
                    }

});
                break;
            case 'fdel':
                var del_before_title = $el('input[name="fdel"]').val();
                $('#wish-online-list tr').find('.modify_title').each(function(){
                    var title = ($(this).html().indexOf(del_before_title)== 0) ? $(this).html().substring(del_before_title.length) : $(this).html();
                    $(this).html(title);
                    if($('#modify-post input[name="product_title_'+$(this).data("pid") +'"]').val()){
                        $('#modify-post input[name="product_title_'+ $(this).data("pid") +'"]').val(title);
                    }else{
                        $('#modify-post').append('<input type="hidden" class="modify_data" name="product_title_'+ $(this).data("pid") +'" value="'+ title +'">');
                    }
                });
                break;
            case 'bdel':
                var del_after_title = $el('input[name="bdel"]').val();
                $('#wish-online-list tr').find('.modify_title').each(function(){
                    var title = ($(this).html().indexOf(del_after_title)== ($(this).html().length-del_after_title.length)) ? $(this).html().substring(0,$(this).html().length-del_after_title.length) :$(this).html()
                    console.log(title);
                    $(this).html(title);
                    if($('#modify-post input[name="product_title_'+$(this).data("pid") +'"]').val()){
                        $('#modify-post input[name="product_title_'+ $(this).data("pid") +'"]').val(title);
                    }else{
                        $('#modify-post').append('<input type="hidden" class="modify_data" name="product_title_'+ $(this).data("pid") +'" value="'+ title +'">');
                    }
                });
                break;
            case 'rp':
                var content = $el('input[name="content"]').val();
                var content_replace = $el('input[name="content_replace"]').val();
                $('#wish-online-list tr').find('.modify_title').each(function(){
                    var title = $(this).html().replace(new RegExp(content, 'g'),content_replace)
                    $(this).html(title);
                    if($('#modify-post input[name="product_title_'+$(this).data("pid") +'"]').val()){
                        $('#modify-post input[name="product_title_'+ $(this).data("pid") +'"]').val(title);
                    }else{
                        $('#modify-post').append('<input type="hidden" class="modify_data" name="product_title_'+ $(this).data("pid") +'" value="'+ title +'">');
                    }
                });

                break; 


        }
        $document.close();
    });

    $el('#modify_price_ensure').on('click',function(){
        type = $('input[name="price_modify_type"]:checked').val();
        switch(type){
            case 'batch':
                var rp_price = $el('input[name="price_modify1"]').val();
                console.log(rp_price);
                rp_price = parseFloat(rp_price).toFixed(2);
                $('#wish-online-list tr').find('.modify_price').each(function(){
                   if(rp_price > 0)  {
                        $(this).html(rp_price);
                        if($('#modify-post input[name="variance_price_'+ $(this).data("id") +'"]').val()){
                            $('#modify-post input[name="variance_price_'+ $(this).data("id") +'"]').val(rp_price);
                        }else{
                            $('#modify-post').append('<input type="hidden" class="modify_data" name="variance_price_'+ $(this).data("id") +'" value="'+ rp_price +'">');
                        }
                    }

                });
                break;
            case 'other':
                var modify_type = $el('select[name="modify_price_select"]').val();
                var modify_val = $el('input[name="price_modify2"]').val();
                $('#wish-online-list tr').find('.modify_price').each(function(){
                    var price = $(this).html();
                    console.log(parseFloat(price)*parseFloat(modify_val)/100);            
                    var rp_price =  modify_type == 'price'? parseFloat(price) + parseFloat(modify_val) : parseFloat(price) + parseFloat(price) * parseFloat(modify_val)/100;
                    rp_price = rp_price.toFixed(2);
                    if(rp_price > 0) {
                        $(this).html(rp_price); 
                        if($('#modify-post input[name="variance_price_'+ $(this).data("id") +'"]').val()){
                            $('#modify-post input[name="variance_price_'+ $(this).data("id") +'"]').val(rp_price);
                        }else{
                            $('#modify-post').append('<input type="hidden" class="modify_data" name="variance_price_'+ $(this).data("id")+'" value="'+ rp_price +'">');
                        }
                    }
                });
                break;
        } 
        $document.close();
    });

    $el('#modify_inventory_ensure').on('click',function(){
        type= $('input[name="inventory_modify_type"]:checked').val();
        switch(type){
            case 'batch':
                var rp_inventory = $el('input[name="inventory_modify1"]').val();
                rp_inventory = Math.ceil(rp_inventory);
                $('#wish-online-list tr').find('.modify_inventory').each(function(){
                    if(rp_inventory > 0){
                        if(rp_inventory >= 10000) rp_inventory = 9999;
                        $(this).html(rp_inventory);
                        if($('#modify-post input[name="variance_inventory_'+ $(this).data("id") +'"]').val()){
                            $('#modify-post input[name="variance_inventory_'+ $(this).data("id") +'"]').val(rp_inventory);
                        }else{
                            $('#modify-post').append('<input type="hidden" class="modify_data" name="variance_inventory_'+ $(this).data("id")+'" value="'+ rp_inventory +'">');
                        }
                    }
                });
                break;
            case 'other':
                var modify_type = $el('select[name="modify_inventory_select"]').val();
                var modify_val = $el('input[name="inventory_modify2"]').val();
                $('#wish-online-list tr').find('.modify_inventory').each(function(){
                    var inventory = $(this).html();
                    var rp_inventory =  modify_type == 'inventory' ? parseFloat(inventory) + parseFloat(modify_val) : parseFloat(inventory) + parseFloat(inventory) * parseFloat(modify_val)/100;
                    rp_inventory = Math.ceil(rp_inventory);
                    if(rp_inventory > 0){
                        if(rp_inventory >= 10000) rp_inventory = 9999;
                        $(this).html(rp_inventory);
                        if($('#modify-post input[name="variance_inventory_'+ $(this).data("id") +'"]').val()){
                            $('#modify-post input[name="variance_inventory_'+ $(this).data("id") +'"]').val(rp_inventory);
                        }else{
                            $('#modify-post').append('<input type="hidden" class="modify_data" name="variance_inventory_'+ $(this).data("id") +'" value="'+ rp_inventory +'">');
                        }
                    }
                });
                break;
        }
        $document.close();
    });

    $el('#modify_ensure').click(function(){
        if(!$('.modify_data').length){
            tips({type:'error',msg:'只有修改了商品信息才能提交',existTime: 3000});
            return false;
        }
        $.showLoading();
        $('#wish-online-list tr').each(function(){
            var product_id = $(this).find('input[name="product_id"]').val();
            var variance_id = $(this).find('input[name="variance_id"]').val();
            if($('#modify-post input[name="product[]"]').val() != product_id){
               $('#modify-post').append('<input type="hidden" name="product[]" value="'+ product_id +'">'); 
            }
            if($('#modify-post input[name="variance_'+product_id+'"]').val() != variance_id){
                $('#modify-post').append('<input type="hidden" name="variance_'+ product_id +'[]" value="'+ variance_id +'">');
            }
        });
        $.ajax({
            type:"POST",
            url :'deal-batch-modify',
            data: $('#modify-post').serialize(),
            success:function(data){
                $.hideLoading();
                if(data['success']){
                    tips({type:'success',msg:data['message'],existTime:3000});
                    setTimeout(function(){
                        window.location.href="/listing/wish-online/wish-product-list";
                    },2000);
                }else{
                    $('.modify_error_tips').html(data['message']);
                }
            }
        });
    });

    $document.on('close',function(){
        $el('input[type="text"]').attr('disabled','disabled');
        $('input[name="fadd"]').removeAttr('disabled');
        $('input[name="price_modify1"]').removeAttr('disabled');
        $('input[name="inventory_modify1"]').removeAttr('disabled');

    })

    $el('input[name="title_modify_type"]').on('click',function(){
       var type = $(this).val();
       // console.log($('#title-modal input[type="text"]').length);
        $el('input[type="text"]').attr('disabled','disabled');
        if(type == 'rp'){
           $('input[name="content"]').removeAttr('disabled'); 
           $('input[name="content_replace"]').removeAttr('disabled');
        }else{
            $('input[name="'+ type +'"]').removeAttr('disabled');
        }
    });

    $el('input[name="price_modify_type"]').on('click',function(){
       var type = $(this).val(); 
       $el('input[type="text"]').attr('disabled','disabled');
        type == 'other' ? $('input[name="price_modify2"]').removeAttr('disabled') : $('input[name="price_modify1"]').removeAttr('disabled');
    });

    $el('input[name="inventory_modify_type"]').on('click',function(){
        var type = $(this).val();
        $el('input[type="text"]').attr('disabled','disabled');
        type == 'other' ? $('input[name="inventory_modify2"]').removeAttr('disabled') : $('input[name="inventory_modify1"]').removeAttr('disabled');
    });
    

});
