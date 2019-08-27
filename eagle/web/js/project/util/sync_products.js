(function($){

	window.LBSyncProducts = function(dom){

		var $this = $(dom),
			$modal = $this.getModal(),
			$progress = $this.find('progress'),
			$cancel = $this.find('.modal-close'),
			$submit = $this.find('.btn-important');

		$submit.on('click',function(e){
			$submit.attr('disabled','disabled');
			var data = $modal.serializeObject();
			$.post('/manual_sync/sync/get-queue-by-user',data,function(res){
				// 开始同步
				var queue_id = res.queue_id,
					done = function(){
						clearInterval(st);
						$submit.removeAttr('disabled');
						$cancel.on('click',function(){
							setTimeout(function(){
								$.location.reload();
							},1000);
						});
					},
					getProgress = function(){
						return $.get('/manual_sync/sync/get-progress-by-user',{
							type:data.type
						}).then(function(res){
							var v = res.progress % 100;
							$progress.val(v);
							$modal.find('[data-count]').text(res.progress);
							if(res.status=='C'){
								$progress.val(100);
								$modal.find('p.text-success').show();
								$modal.find('.sending').hide();
								done();
							}
							if(res.status=='F'){
								$modal.find('p.text-danger').show();
								done();
							}

						});	
					},
					st = setInterval(getProgress,3000);
			});
		});
	};


})(jQuery);