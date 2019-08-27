var Translate = function klass() {
	this.data = false;
	this.get = function (key){
		if (this.data[key] !== Object.prototype[key])
			return this.data[key];
	}
	
	this.set = function (key, value) {
		return this.data[key] = value;
	}
	
	this.initialize.apply(this, arguments);
	
}

Translate.prototype = {
    initialize: function(data){
		if("string" == typeof(data))
			this.data = eval('(' + data + ')');
		else if("object" == typeof(data)){
			this.data = data;
		}
    },
	// Translate
    t : function(){
        var args = arguments;
        var text = arguments[0];

        if(this.get(text)){
            return this.get(text);
        }
        return text;
    },
	// Adding translation data
    add : function() {
        if (arguments.length > 1) {
            this.set(arguments[0], arguments[1]);
        } else if (typeof arguments[0] =='object') {
			for(var key in arguments[0]){
				this.set(key , arguments[0][key]);
			}
        }
    }
}
// aa = new Translate({"Please select an option.":"hahaha"})
// aa.add({"Please select an option.":"hehehe"})
// aa.add({"What the XXXX.":"123456"})
// aa.add('hihihi','hihihi2')
// aa.translate('What the XXXX.')


