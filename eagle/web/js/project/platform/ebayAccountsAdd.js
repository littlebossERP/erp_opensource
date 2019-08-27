$(function(){
    $('#platform-ebayAccounts-list-commonwindow').dialog({
        title:"绑定ebay帐号",
        width:630,
        height:500,
        collapsible:true,
        minimizable:false,
        maximizable:false,
        draggable:false,
        resizable:false,
        modal:true,
    /*
        buttons:[{
            text:'保存',
            iconCls:'icon-ok',
            handler:function(){
                $('#platform-ebayAccounts-edit-form').form('submit', {
                    success:function(data){
                        if(data) {
                            $('#index-commonwindow').dialog('close');
                            $('#platform-ebayAccounts-list-datagrid').datagrid('reload');
                        }else{
                            $.messager.alert('新增失败!', '添加未成功，请重试!');
                        }
                    }
                });
            }
        },{
            text:'取消',
            iconCls:'icon-cancel',
            handler:function(){
                $('#index-commonwindow').dialog('close');
            }
        }],
        */
        onClose:function(){
            $('#platform-ebayAccounts-list-commonwindow').before('<div id="temp-commonwindow"></div>');
            $('#platform-ebayAccounts-list-commonwindow').remove();
            $('#temp-commonwindow').attr('id','platform-ebayAccounts-list-commonwindow');
        }
    });
    
    //alert($('#index-commonwindow').dialog);
});
