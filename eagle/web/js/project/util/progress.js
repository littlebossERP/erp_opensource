(function($){

	var Progress = function(div){
		this.url;
		this.method = 'get';
		this.params = {};
		this.timer;
		this.loadImg;
		this.$div = $(div);
		this.$progress = this.$div.find('progress');
		this.max = parseInt(this.$progress.attr('max'));
		return this;
	};

	Progress.prototype.start = function(fn,loop){
		var self = this;
		if(!this.url){
			console.error('url is undefined');
		}

		this.timer = setInterval(function(){
			$.ajax({
				method:self.method,
				url:self.url,
				data:self.params,
				success:function(res){
					typeof fn === 'function' && fn.apply(self,arguments);
					var v = res.progress % self.max;
					console.log(v);
					self.$progress.val(v);
					if(res.status=='C'){
						self.$div.trigger('progress.done',[res]);
						self.stop();
						self.$progress.val(self.max);
					}
					if(res.status=='F'){
						self.$div.trigger('progress.fail',[res]);
						self.stop();
					}
				}
			});
		},loop || 1000);
	};

	Progress.prototype.stop = function(){
		clearInterval(this.timer);
	};

	Progress.prototype.done = function(fn){
		return this.$div.on('progress.done',fn);
	};

	Progress.prototype.fail = function(fn){
		return this.$div.on('progress.fail',fn);
	};

	window.Progress = Progress;

})(jQuery);