jQuery.extend({
	createUploadIframe: function(id, uri)
	{
			//create frame
            var frameId = 'jUploadFrame' + id;
            var iframeHtml = '<iframe id="' + frameId + '" name="' + frameId + '" style="position:absolute; top:-9999px; left:-9999px"';
			if(window.ActiveXObject)
			{
                if(typeof uri== 'boolean'){
					iframeHtml += ' src="' + 'javascript:false' + '"';

                }
                else if(typeof uri== 'string'){
					iframeHtml += ' src="' + uri + '"';

                }	
			}
			iframeHtml += ' />';
			jQuery(iframeHtml).appendTo(document.body);

            return jQuery('#' + frameId).get(0);			
    },
	
	
    createUploadForm: function(id, fileElementId, data)
	{
		//create form	
		var formId = 'jUploadForm' + id;
		var fileId = 'jUploadFile' + id;
		var form = jQuery('<form  action="" method="POST" name="' + formId + '" id="' + formId + '" enctype="multipart/form-data"></form>');	
		if(data)
		{
			for(var i in data)
			{
				jQuery('<input type="hidden" name="' + i + '" value="' + data[i] + '" />').appendTo(form);
			}			
		}		
		var oldElement = jQuery('#' + fileElementId);
		var newElement = jQuery(oldElement).clone();
		jQuery(oldElement).attr('id', fileId);
		jQuery(oldElement).addClass('done');//dzt20150210 add for filter input files 
		jQuery(oldElement).before(newElement);
		jQuery(oldElement).appendTo(form);


		
		//set attributes
		jQuery(form).css('position', 'absolute');
		jQuery(form).css('top', '-1200px');
		jQuery(form).css('left', '-1200px');
		jQuery(form).appendTo('body');		
		return form;
    },

	createXHR : function(){
		if (typeof XMLHttpRequest != "undefined") {
            return new XMLHttpRequest();
        } else if (typeof ActiveXObject != "undefined") {
            if (typeof arguments.callee.activeXString != "string") {
                var versions = ["MSXML2.XMLHttp.6.0", "MSXML2.XMLHttp.3.0", "MSXML2.XMLHttp"],
                    i, len;

                for (i = 0, len = versions.length; i < len; i++) {
                    try {
                        var xhr = new ActiveXObject(versions[i]);
                        arguments.callee.activeXString = versions[i];
                        return xhr;
                    } catch (ex) {
                        //skip
						//jQuery.handleError: function( s, xhr, status, e );
                    }
                }
            }

            return new ActiveXObject(arguments.callee.activeXString);
        } else {
            throw new Error("No XHR object available.");
			//jQuery.handleError: function( s, xhr, status, e );
        }
	},
	
	//Image file type validation
	CheckFileType : function(file){
		var msg = {};
		if('string' == typeof(file))
			var filepath1 = file;
		else	
			var filepath1 =file.name;
		var fileextend1 = filepath1.substring(filepath1.lastIndexOf('.')+1,filepath1.length);
		if(fileextend1==""){
			msg.check = false;
			msg.content = "Please choose a file!";
			return msg;
		}
		fileextend1 = fileextend1.toLowerCase();
		if(fileextend1 != "jpg" && fileextend1 !="jpeg" && fileextend1 !="gif" && fileextend1 !="pjpeg" && fileextend1 !="png"){
			msg.check = false;
			msg.content = "Please choose file type as follow: gif,jpeg,png,pjpg!";
			return msg;
		}
		msg.check = true;		
		return msg;
	},
	
	// log : dzt20150210 turn down most global callback for yii may catch and occur errors.
    ajaxFileUpload: function(s) {
        s = jQuery.extend({}, jQuery.ajaxSettings, s);
        var id = new Date().getTime();
		var fileId = s.fileElementId + id;
//		var maxsize = 5 * 1024 * 1024;
		if(s.uploadFile){
			var file = s.uploadFile;
		}else{
			var file = document.getElementById(s.fileElementId);
		}
		
		// 通过fileList对象或input file value检查图片类型.
		if(!s.isNotCheckFile){
			var msg = jQuery.CheckFileType(file);
			if(msg.check === false){
				jQuery.handleError( s, xhr, null, msg.content); 
				return;
			}
		}
		
		//Deal with sigle file upload
		var fileSize = 0;
		var Sys = {};
		if(navigator.userAgent.indexOf("MSIE")>0) {
			Sys.ie = true;
			var version = navigator.userAgent.split(";"); 
			var trim_Version = version[1].replace(/[ ]/g,""); 
			Sys.ieVersion = trim_Version;
		}
		if(isFirefox=navigator.userAgent.indexOf("Firefox")>0){
			Sys.firefox=true;
		}
		
		if(s.isUploadFile || (Sys.ie &&  Sys.ieVersion != "MSIE10.0")){
			var form = jQuery.createUploadForm(id, s.fileElementId, (typeof(s.data)=='undefined'?false:s.data));
			var io = jQuery.createUploadIframe(id, s.secureuri);
			var frameId = 'jUploadFrame' + id;
			var formId = 'jUploadForm' + id;		
			
		}else{
			var oldElement = jQuery('#' + s.fileElementId);
			var newElement = jQuery(oldElement).clone();
			jQuery(oldElement).attr('id', fileId);
			jQuery(oldElement).before(newElement);
			jQuery(oldElement).remove();
			
			fileSize = file.size;
			var fd = new FormData();//dzt20130829 changes
			if(s.fileName)
				fd.append(s.fileName, file);
			else
				fd.append(jQuery(oldElement).attr('name'), file);
			
			if(s.data){
				for(var i in s.data){
					fd.append(i, s.data[i]);
				}	
				
			}
			 // Create the request object
			var xhr = jQuery.createXHR();
			if(s.progress){
				xhr.upload.onprogress = function(evt){
					s.progress(evt , s);
				};
			}
		}
		
        // Watch for a new set of requests
//        if ( s.global && ! jQuery.active++ )
//		{
//			jQuery.event.trigger( "ajaxStart" );
//		}            
        var requestDone = false;
        
		var xml = {};
//        if ( s.global )
//            jQuery.event.trigger("ajaxSend", [xml, s]);
        // Wait for a response to come back
		
        var uploadCallback = function(Sys , isTimeout)
		{	
			if( s.isUploadFile || Sys.ieVersion == "MSIE9.0"){
				var io = document.getElementById(frameId);
				try 
				{				
					if(io.contentWindow)
					{
						 xml.responseText = io.contentWindow.document.body?io.contentWindow.document.body.innerHTML:null;
						 xml.responseXML = io.contentWindow.document.XMLDocument?io.contentWindow.document.XMLDocument:io.contentWindow.document;
						 
					}else if(io.contentDocument)
					{
						 xml.responseText = io.contentDocument.document.body?io.contentDocument.document.body.innerHTML:null;
						xml.responseXML = io.contentDocument.document.XMLDocument?io.contentDocument.document.XMLDocument:io.contentDocument.document;
					}						
				}catch(e)
				{
					jQuery.handleError(s, xml, null, e);
				}
			}else{
				xml = xhr;
			}
	
            if ( xml || isTimeout == "timeout") 
			{				
                requestDone = true;
                var status;
                try {
                    status = isTimeout != "timeout" ? "success" : "error";
                    // Make sure that the request was successful or notmodified
                    if ( status != "error" )
					{
                        // process the data (runs the xml through httpData regardless of callback)
                        var data = jQuery.uploadHttpData( xml, s.dataType ); 
                        // If a local callback was specified, fire it and pass it the data
                        if ( s.success )
                            s.success( data, status , s );
    
                        // Fire the global callback
//                        if( s.global )
//                            jQuery.event.trigger( "ajaxSuccess", [xml, s] );
                    } else
                        jQuery.handleError(s, xml, status);
                } catch(e) 
				{
                    status = "error";
                    jQuery.handleError(s, xml, status, e);
                }

                // The request was completed
//                if( s.global )
//                    jQuery.event.trigger( "ajaxComplete", [xml, s] );

                // Handle the global AJAX counter
//                if ( s.global && ! --jQuery.active )
//                    jQuery.event.trigger( "ajaxStop" );

                // Process result
                if ( s.complete )
                    s.complete(xml, status);
				
				if(s.isUploadFile || Sys.ieVersion == "MSIE9.0"){
					 jQuery(io).unbind();

					setTimeout(function()
					{	try 
						{
							jQuery(io).remove();
							jQuery(form).remove();	
							
						} catch(e) 
						{
							jQuery.handleError(s, xml, null, e);
						}									

					}, 100);			
				}
		
                xml = null;

            }
        };
        // Timeout checker
        if ( s.timeout > 0 ) 
		{
            setTimeout(function(){
                // Check to see if the request is still happening
                if( !requestDone ) uploadCallback( Sys , "timeout" );
            }, s.timeout);
        }
        try 
		{	
			if(s.isUploadFile || Sys.ieVersion == "MSIE9.0"){
				var form = jQuery('#' + formId);
				jQuery(form).attr('action', s.url);
				jQuery(form).attr('method', 'POST');
				jQuery(form).attr('target', frameId);
				if(form.encoding)
				{
					jQuery(form).attr('encoding', 'multipart/form-data');      			
				}
				else
				{	
					jQuery(form).attr('enctype', 'multipart/form-data');			
				}

				jQuery(form).submit();
			}else{
//				if(fileSize >= maxsize){
//					jQuery.handleError( s, xhr, null, "File size must less than 5M"); 
//					return;
//				}
				var method = s.method ?  s.method : 'POST';
				xhr.open(method,  s.url);
				xhr.send(fd);
			}  
        } catch(e) 
		{			
        	jQuery.handleError(s, xml, null, e);
        }
		if(s.isUploadFile || Sys.ieVersion == "MSIE9.0"){
			document.getElementById(frameId).onload=function(){uploadCallback( Sys )}
//			jQuery('#' + frameId).on('load' , uploadCallback( Sys ));
		}else{
			xhr.onreadystatechange = function(e) {
				if (xhr.readyState == 4) {
					if(xhr.status >= 200 && xhr.status < 300)
						uploadCallback( Sys );
					else
						jQuery.handleError(s, xhr, null, null);//从xhr对象可以获取所需的error 信息；
				}
			};
		}
        return {abort: function () {}};	

    },

    uploadHttpData: function( r, type ) {
        var data = !type;
        data = type == "xml" || data ? r.responseXML : r.responseText;
        // If the type is "script", eval it in global context
        if ( type == "script" )
            jQuery.globalEval( data );
        // Get the JavaScript object, if JSON is used.
        if ( type == "json" )
            eval( "data = " + data );
        // evaluate scripts within html
//        if ( type == "html" )
//            jQuery("<div>").html(data).evalScripts();
//        	data = jQuery.parseHTML( data );
        return data;
    },
    handleError: function( s, xhr, status, e ) 		{
    	// If a local callback was specified, fire it
		if ( s.error ) {
			s.error.call( s.context || s, xhr, status, e );
		}

		// Fire the global callback
		if ( s.global ) {
			(s.context ? jQuery(s.context) : jQuery.event).trigger( "ajaxError", [xhr, s, e] );
		}
	}
});