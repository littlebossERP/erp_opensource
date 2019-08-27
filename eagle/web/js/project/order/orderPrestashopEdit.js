var sta;
if($('#statushide').val()=='600'){
	$(".ystep1").loadStep({
	    size: "large",
	    color: "green",
	    steps: [{ title: "已取消",content: "小老板系统内是已取消状态"}]
	  });
	sta=1;
}else{
	$(".ystep1").loadStep({
	    //ystep的外观大小
	    //可选值：small,large
	    size: "large",
	    //ystep配色方案
	    //可选值：green,blue
	    color: "green",
	    //ystep中包含的步骤
	    steps: [{
	      //步骤名称
	      title: "已付款",
	      //步骤内容(鼠标移动到本步骤节点时，会提示该内容)
	      content: "小老板系统内是已付款状态"
	    },{
	      title: "发货中",
	      content: "小老板系统内是发货中状态"
	    },{
	      title: "已完成",
	      content: "小老板系统内是已完成状态"
	  }]
	});
	if($('#statushide').val()=='200'){
		sta=1;
	}else if($('#statushide').val()=='300'){
		sta=2;
	}else if($('#statushide').val()=='500'){
		sta=3;
	}else{
		sta=1;
	}
}

$(".ystep1").setStep(sta);

// 添加产品
function TableTransactionModifyAdd(){
	$('#TableTransactionModify').append($('#new').clone().show());
}
// 删除产品
function  removeTransaction(obj){
	$(obj).parent().parent().remove();
}

