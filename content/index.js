
// Init
$(function() {
	$.expr[':'].focus = function(a){ return (a == document.activeElement); } // Make the :focus selector work in IE7
});



// Functions
function ajax(vars, arg) {
	$.ajax({
		type: 'POST', url: (typeof(ajaxServer)!='undefined'?ajaxServer:'/'), dataType: 'json', data: vars,
		success: function(msg) {
			ajaxCallback(msg, arg);
		},
		error: function (xhr, desc, exceptionobj) { alert(xhr.responseText ? 'Parser error: '+xhr.responseText : 'Unknown error'); }
	});
	return false;
}
function ajaxCallback(msg, arg) {
	if(!$.isArray(msg)) alert(msg || 'Unknown error');
	else switch(msg[0]) {
		case 'errLogin':
			alert('You need to be logged in to use this function.');
			break;
		case 'reload':
			window.location.reload();
			break;
		case 'redirect':
			window.location = msg[1];
			break;
		case 'msg':
		case 'err':
			KBAlert(msg[1]);
			break;
		case 'callback':
			if(typeof(arg)=='undefined') alert('Error: Invalid internal call');
			else arg(msg[1]);
			break;
		case 'content':
			$(typeof(arg['contentBox'])=='undefined'?'#content':arg['contentBox']).html(msg[1]);
			break;
		case 'unsupported':
			unsupported();
			break;
		case 'fieldErrs':
			if(typeof(arg)=='undefined') alert('Error: Invalid internal call');
			else {
				if(typeof(Recaptcha)!='undefined') Recaptcha.reload();
				$('input,select',arg).css('background','');
				$('input[type=radio]',arg).parent().css('background','');
				$('input + span.validationResponse, select + span.validationResponse',arg).hide();
				var clearCaptchaFun = function() {
					$(this).css('background','');
				};
				var clearFun = function() {
					$(this).css('background','');
					$('+ span.validationResponse',this).hide();
				};
				for(var key in msg[1]) {
					if(key == 'captcha') {
						var field = $('#recaptcha_response_field',arg);
						if(field.length) {
							field.css('background','#f99');
							field.focus(clearCaptchaFun);
						} else alert('Error finding captcha field.');
					}else{
						if(!$('[name="'+key+'"]',arg).length) alert('Error finding field with key: '+key);
						$('[name="'+key+'"]',arg).css('background','#f99');
						$('[name="'+key+'"][type=radio]',arg).parent().css('background','#f99');
						$('[name="'+key+'"] + span.validationResponse',arg).html(msg[1][key]);
						$('[name="'+key+'"] + span.validationResponse',arg).show();
						$('[name="'+key+'"]',arg).focus(clearFun);
					}
				}
			}
			break;
		default:
			alert('Error: Unknown response');
			break;
	}
}

function KBPopupDialog(opts) {
	$('.popupDialog').remove();
	var popup = '<div class="popupDialog" style="visibility:hidden; position:fixed; top:0px; left:0px; width:100%; height:100%; z-index:2000;">';
	popup += '<div class="bg" style="position:absolute; width:100%; height:100%; background:#000; opacity:0.7; filter:alpha(opacity=70); z-index:2000;">&nbsp;</div>';
	popup += '<div class="content" style="position:absolute; z-index:2010; background:#fff;">';
	popup += opts['msg'];
	popup += '</div></div>';
	var dialog = $(document.body).append(popup);
	$('.popupDialog .content',dialog).css({
		top:			$(window).height()/2-$('.popupDialog .content',dialog).height()/2,
		left:			$(window).width()/2-$('.popupDialog .content',dialog).width()/2
	});
	$('.popupDialog',dialog).hide().css('visibility','visible');
	for(var button in opts['buttons']) $('.popupDialog .content .'+button,dialog).click(opts['buttons'][button]);
	var oldFocus = $(':focus');
	$(document).keydown(function (e) {
		if(e.which == 27) {
			$('.popupDialog').fadeOut();
			if(typeof(oldFocus)!='undefined') oldFocus.focus();
			return false;
		}
	});
	$('.popupDialog',dialog).fadeIn();
	if(typeof(opts['focus'])!='undefined') $(opts['focus']).focus();
	else $('.popupDialog .content a:last',dialog).focus();
	return false;
}
function KBAlert(opts) {
	if(typeof(opts)=='string') opts = {'msg': opts};
	var popup = "<div style='background:#fff; color:#888; font-size:14px; padding:30px 23px 23px 23px;'>"+opts['msg']+"</div>";
	popup += "<div style='text-align:right;'>";
	popup += "<a href='#' class='close' style='display:inline-block; margin: 0 36px 20px 0;'>Close</a>";
	popup += "</div>";
	var oldFocus = $(':focus');
	return KBPopupDialog({ 'msg': popup, 'buttons': { 'close': function() { $('.popupDialog').fadeOut(); if(typeof(oldFocus)!='undefined') oldFocus.focus(); return false; } }, 'focus': opts['focus'] });
}
function KBConfirm(msg,ok,cancel) {
	var popup = '<div style="padding:10px; font-weight:bold;">Message</div>';
	popup += "<div style='background:#fff; color:#888; font-size:14px; padding:23px;'>"+msg+"</div>";
	popup += "<div style='text-align:right;'>";
	popup += "<a href='#' class='cancel' style='display:inline-block; margin: 0 20px 20px 0;'>Cancel</a>";
	popup += "<a href='#' class='ok' style='display:inline-block; margin: 0 36px 20px 0;'>OK</a>";
	popup += "</div>";
	var oldFocus = $(':focus');
	return KBPopupDialog({ "msg": popup, "buttons": {
		"ok": function() { $('.popupDialog').fadeOut(); if(typeof(oldFocus)!='undefined') oldFocus.focus(); if(ok) ok(); return false; },
		"cancel": function() { $('.popupDialog').fadeOut(); if(typeof(oldFocus)!='undefined') oldFocus.focus(); if(cancel) cancel(); return false; }
	} });
}

function formHandler(form) {
	if($(form).attr('enctype')=='multipart/form-data') { // Use iframe style submit
		var id = 'KBFormIO' + (new Date().getTime());
		var iframe = '<iframe id="' + id + '" name="' + id + '" style="position:absolute;top:-1000px;left:-1000px;" src="about:blank" type="text/plain"/>';
		
		$(document.body).append(iframe);
		
		$('#'+id).bind('load',function() {
			var rc=new RegExp('^("(\\\\.|[^"\\\\\\n\\r])*?"|[,:{}\\[\\]0-9.\\-+Eaeflnr-u \\n\\r\\t])+?$');
			var msg = $(this).contents().text();
			if(rc.test(msg)) msg = eval(msg);
			ajaxCallback(msg,form);
		});
		
		$(form).attr({
			action:	(typeof(ajaxServer)!='undefined'?ajaxServer:'/'),
			method:	'post',
			target:	id
		});
		form.submit();
		
		return false;
	}else{
		var vars = {};
		for(var i=0; i<form.elements.length; i++) {
			if(form.elements[i].multiple) {
				$('option:selected',form.elements[i]).each(function() {
					if(typeof(vars[form.elements[i].name])=='undefined') vars[form.elements[i].name] = [$(this).val()];
					else vars[form.elements[i].name].push($(this).val());
				});
			}else{
				if(form.elements[i].type!='radio' || (form.elements[i].type=='radio' && form.elements[i].checked)) {
					switch(typeof(vars[form.elements[i].name])) {
					case 'undefined':
						vars[form.elements[i].name] = form.elements[i].value;
						break;
					case 'object':
						vars[form.elements[i].name].push(form.elements[i].value);
						break;
					default:
						vars[form.elements[i].name] = [vars[form.elements[i].name],form.elements[i].value];
						break;
					}
				}
			}
		}
		return ajax(vars, form);
	}
}
 
function logout() {
	return ajax({a:'logout'});
}
 
function unsupported() {
	alert('Error: This feature isn\'t supported, yet.');
	return false;
}
function msgConfirm(question) {
	return confirm(question);
}












