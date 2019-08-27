$.domReady(function($el){
	var $document = $(this);

	i=0;
	j=0;
	$el('.qiehuan').click(function(){
		if(i%2==0){
			$(this).children(0).css({'display':'none'}).next().css({'display':'block'}).parent().parent().parent().next().css({'display':'none'});
		}else{
			$(this).children(0).css({'display':'block'}).next().css({'display':'none'}).parent().parent().parent().next().css({'display':'block'});
		}
		i++;
		//$(this).children(1).css({'display':'block'});
		return false;
	});

	// 添加一个选中项操作
	var addOne = function(text,id){
		var html = "<div class='decora' data-id='"+ id +"'>"+text+"<span class='disapper-close'>×</span>",
		$html = $el(".bottom-box").$append(html);
		$html.on('click',function(){
			// 这里只是触发checkbox的勾选操作，一处元素通过checkbox事件来带动，否则会进入死循环
			var $checkbox = $el(".many-check [value="+id+"]");
			$checkbox.prop('checked',false).removeAttr('checked').trigger('change');
		});
	};

	var removeOne = function(id){
		$el("[data-id="+id+"]").remove();
	};

	// 选择店铺操作(反向操作)
	$el(".line-bottom input").on('change',function(){
		var $this = $(this);
		if($this.is(':checked')){
			addOne($this.next().text(),$this.val());
		}else{
			removeOne($this.val());
		}
	});

	$el('.b-fontsize-color').click(function(){
		$('.bottom-box').children().hide().parent().parent().find(':checked').removeAttr("checked");
		
	});

	$el('#sures').click(function(){
		
	});

		
	
})