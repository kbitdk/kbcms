/*
KB CMS
Licensed under GPLv2 or later.
*/

// Init
$(function() {
	if(typeof(ajaxFetcher)!='undefined' && ajaxFetcher) {
		$(window).bind('hashchange',function(){
			var msgWait = '';
			if(KBTB.hashchange.beforeunload!==undefined && (msgWait=KBTB.hashchange.beforeunload()) && !confirm(msgWait)) window.location.hash = window.hashPrev;
			else{
				delete KBTB.hashchange.beforeunload;
				var hash = location.hash;
				if(hash.indexOf('#')==0) hash = hash.substr(1);
				ajax({a:'page',p:hash});
				window.hashPrev = hash;
			}
		});
		window.hashPrev = location.hash;
		if(location.hash!='') $(window).trigger('hashchange');
	}
	
	try { $(':focus'); } catch(e) { // Detect :focus support and repair (IE<=7 && FF<=3.0)
		$.expr[':'].focus = function(a){ return (a == document.activeElement); }
	}
	if($.browser.msie && $.browser.version.split('.')[0]<=7) { // Fix IE <=7 issues
		var hashChecker = function(){ lastHash = location.hash; return setInterval(function() { if(lastHash !== location.hash) { $.event.trigger('hashchange'); lastHash = location.hash; } }, 50); }()
	}
});
KBTB = {
	hashchange: {
		beforeunload: undefined
	}
};


// Functions
function ajax(vars, arg) {
	$.ajax({
		type: 'POST', url: (typeof(ajaxServer)!='undefined'?ajaxServer:'/'), dataType: 'json', data: vars,
		success: function(msg) {
			ajaxCallback(msg, arg);
		},
		error: function (xhr, desc, exceptionobj) {
			loadingStop();
			KBAlert(xhr.responseText ? 'Parser error: <pre>'+html_encode(xhr.responseText)+'</pre>' : 'Unknown error');
			$('input[type=submit][class~=KBLoading][disabled]').removeClass('KBLoading').removeAttr('disabled');
		}
	});
	return false;
}
function ajaxCallback(msg, arg) {
	loadingStop();
	if(!$.isArray(msg)) KBAlert(msg ? ('Parser error: <pre>'+html_encode(msg).replace(/\n/g, '<br/>')+'</pre>') : 'Unknown error');
	else switch(msg[0]) {
		case 'nothing':
			break;
		case 'page':
			$('.popupDialog').hide();
			$('#content').html(msg[2]);
			$('#menu').html(msg[3]);
			window.location.hash=msg[1];
			break;
		case 'errLogin':
			KBAlert('You need to be logged in to use this function.');
			break;
		case 'reload':
			window.location.reload();
			break;
		case 'redirect':
			window.location = msg[1];
			break;
		case 'msg':
		case 'err':
			var opts = {msg:msg[1]};
			if(typeof(msg[2])=='string') opts['lang'] = msg[2];
			KBAlert(opts);
			break;
		case 'callback':
			if(typeof(arg)!='function') alert('Error: Invalid internal call.');
			else arg(msg[1]);
			break;
		case 'callbackCustom':
			if(new RegExp('^[a-zA-Z]+$').test(msg[1])) eval(msg[1]+'(msg);');
			else alert('Error: Invalid internal call.');
			break;
		case 'callbackDirect':
			msg.shift();
			var funName = msg.shift();
			if(new RegExp('^[a-zA-Z]+(\\.[a-zA-Z]+)*$').test(funName)) eval(funName+'.apply(this,msg);');
			else alert('Error: Invalid internal call.');
			break;
		case 'consoleLog':
			console.log(msg[1]);
			break;
		case 'content':
		case 'contentTop':
			$(typeof(arg['contentBox'])=='undefined'?'#content':arg['contentBox']).html(msg[1]);
			if(msg[0]=='contentTop') $('html,body').scrollTop(0);
			break;
		case 'selector':
			$(msg[1]).html(msg[2]);
			break;
		case 'selectorOuter':
			$(msg[1]).before(msg[2]).remove();
			break;
		case 'unsupported':
			unsupported();
			break;
		case 'selectFill':
			var opts = [];
			$.map(msg[2],function(result){
				opts.push($('<div>').append($('<option/>').val(result['key']).text(result['value'])).html());
			});
			$(msg[1]).html(opts.join(''));
			if(msg[3]) $(msg[1]).val(msg[3]).trigger('change');
			$('.popupDialog').fadeOut();
			break;
		case 'form':
			var $form = $('<form>').attr({method:'post',action:(msg[1]['action']?msg[1]['action']:(typeof(ajaxServer)!='undefined'?ajaxServer:'/'))});
			
			$.each(msg[1]['fields'], function(name, value) {
				$('<input>').attr({type:'hidden',name:name,value:value}).appendTo($form);
			});
			
			$form.appendTo("body");
			$form.submit();
			break;
		case 'fieldErrs':
			if(typeof(arg)=='undefined' || typeof(arg)=='function') arg=document.body;
			if(typeof(Recaptcha)!='undefined') Recaptcha.reload();
			var clearCaptchaFun = function() {
				$(this).css('background','').removeClass('fieldErr');
			};
			var clearFun = function() {
				$(this).css('background','').removeClass('fieldErr');
				$('+ span.validationResponse',this).hide();
			};
			var errorTop = $(arg).parents('.popupDialog').length ? false : $(document).height();
			for(var key in msg[1]) {
				if(key == 'captcha') {
					var field = $('#recaptcha_response_field',arg);
					if(field.length) field.addClass('fieldErr').css('background','#f66').focus(clearCaptchaFun);
					else alert('Error finding captcha field.');
				}else{
					var el = $('[name="'+key+'"]:not([type=hidden])',arg);
					if(!el.length) alert('Error finding field with key: '+key);
					else {
						el.addClass('fieldErr').css('background','#f66');
						var ckedit = $('[name="'+key+'"] + .cke_skin_kama .cke_editor iframe',arg);
						if(ckedit.length) {
							ckedit.contents().find('body').addClass('fieldErr').css('background','#f66');
							el = ckedit;
						}
						$('[name="'+key+'"][type=radio],[name="'+key+'"][type=checkbox]',arg).parent().addClass('fieldErr').css('background','#f66').focus(clearFun).change(clearFun);
						$('[name="'+key+'"] + span.validationResponse',arg).html(msg[1][key]);
						$('[name="'+key+'"] + span.validationResponse',arg).show();
						el.focus(clearFun).change(clearFun);
						if(errorTop!==false && (currTop=el.offset().top) < errorTop) errorTop = currTop;
					}
				}
			}
			if(errorTop!==false) $('html,body').animate({scrollTop: errorTop-100}, 'slow');
			break;
		default:
			KBAlert('Error: Unknown response');
			break;
	}
	$('input[type=submit][class~=KBLoading][disabled]').removeClass('KBLoading').removeAttr('disabled');
}

function KBPopupDialogReposition() {
	$('.popupDialog .content').css({
		top:			$(window).height()/2-$('.popupDialog .content').height()/2,
		left:			$(window).width()/2-$('.popupDialog .content').width()/2
	});
}

function KBPopupDialog(opts) {
	$('.popupDialog').stop().remove();
	var popup = '<div class="popupDialog" style="visibility:hidden; position:fixed; top:0px; left:0px; width:100%; height:100%; z-index:2000;">';
	popup += '<div class="bg" style="position:absolute; width:100%; height:100%; background:#000; opacity:0.7; filter:alpha(opacity=70); z-index:2000;">&nbsp;</div>';
	popup += '<div class="content" style="position:absolute; z-index:2010; background:#fff; overflow:auto; max-width:95%; max-height:95%;">';
	popup += opts['msg'];
	popup += '</div></div>';
	var dialog = $(document.body).append(popup);
	KBPopupDialogReposition();
	$('.popupDialog',dialog).hide().css('visibility','visible');
	for(var button in opts['buttons']) $('.popupDialog .content .'+button,dialog).click(opts['buttons'][button]);
	for(var i in opts['events']) $('.popupDialog .content '+opts['events'][i][0],dialog).bind(opts['events'][i][1],opts['events'][i][2]);
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
	$('.popupDialog .content').scrollTop(0);
	return false;
}
function KBPopupDialogClose() {
	$('.popupDialog').fadeOut();
}
function KBAlert(opts) {
	if(typeof(opts)=='string') opts = {'msg': opts};
	if(typeof(opts['buttons'])=='object' && opts['buttons'] instanceof Array) {
		var buttons = '';
		var tmpButtons = {};
		for(var i in opts['buttons']) {
			buttons += '<a href="#" class="'+opts['buttons'][i][1]+'" style="display:inline-block; margin: 0 36px 20px 0;">'+opts['buttons'][i][0]+'</a>';
			tmpButtons[opts['buttons'][i][1]] = opts['buttons'][i][2];
		}
		opts['buttons'] = tmpButtons;
	} else {
		var text = {
			da:	{
				close:	'Luk'
			},
			en:	{
				close:	'Close'
			},
			fr:	{
				close:	'Fermer'
			}
		};
		var lang = (typeof(opts['lang'])!='undefined' && typeof(text[opts['lang']])!='undefined' ? opts['lang'] : 'en');
		text = typeof(opts['text'])!='undefined' ? opts['text'] : text[lang]; 
		var buttons = '<a href="#" class="close" style="display:inline-block; margin: 0 36px 20px 36px;">'+text['close']+'</a>';
		opts['buttons'] = { 'close': function() { $('.popupDialog').fadeOut(); if(typeof(oldFocus)!='undefined') oldFocus.focus(); return false; } };
	}
	var popup = "<div style='background:#fff; font-size:14px; padding:30px 23px 23px 23px;'>"+opts['msg']+"</div>";
	popup += "<div style='text-align:right;'>";
	popup += buttons;
	popup += "</div>";
	var oldFocus = $(':focus');
	return KBPopupDialog({ 'msg': popup, 'buttons': opts['buttons'], 'focus': opts['focus'], events: opts['events'] });
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
	if(typeof(form)=='undefined') return KBAlert('Internal error (formHandler): Invalid input.');
	
	$('input[type=submit]:not([disabled])',form).addClass('KBLoading').attr('disabled','disabled');
	loadingStart();
	$('input,select,textarea',form).css('background','').removeClass('fieldErr');
	$('input[type=radio]',form).parent().css('background','').removeClass('fieldErr');
	$('input + span.validationResponse, select + span.validationResponse',form).hide();
	
	if(typeof(CKEDITOR)!='undefined') for(var instance in CKEDITOR.instances) CKEDITOR.instances[instance].updateElement();
	if($(form).attr('enctype')=='multipart/form-data') { // Use iframe style submit for file uploads
		var id = 'KBFormIO' + (new Date().getTime());
		var iframe = '<iframe id="'+id+'" name="'+id+'" style="position:absolute;top:-1000px;left:-1000px; height:10px;width:10px;" src="about:blank" type="text/plain"/>';
		
		$(form).attr({
			action:	(typeof(ajaxServer)!='undefined'?ajaxServer:'/'),
			method:	'post',
			target:	id
		});
		
		$(document.body).append(iframe);
		
		$('#'+id).bind('load',function() {
			var retval = $(this).contents().text();
			var msg = null;
			try { msg = $.parseJSON(retval); } catch(e) {}
			ajaxCallback(msg===null?retval:msg,form);
		});
		
		return true;
	}else{
		return ajax($(form).serialize(), form);
	}
}

function html_encode(input) {
	return input===null ? '' : input.replace(/&/g, '&amp;').replace(/'/g, '&#039;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); //'
}

function loadingStart() {
	if(!$('.popupDialog:visible').length) {
		$('.popupDialog').remove();
		var popup = '<div class="popupDialog" style="visibility:hidden; position:fixed; top:0px; left:0px; width:100%; height:100%; z-index:2000;">';
		popup += '<div class="bg" style="position:absolute; width:100%; height:100%; background:#000; opacity:0.7; filter:alpha(opacity=70); z-index:2000;">&nbsp;</div>';
		popup += '<div class="content" style="position:absolute; z-index:2010; background:#fff; padding:15px; font-size:25px; width:115px; color: #424C54;">';
		popup += 'Loading<span id="loadingDots"></span>';
		popup += '</div></div>';
		var dialog = $(document.body).append(popup);
		KBPopupDialogReposition();
		$('.popupDialog',dialog).hide().css('visibility','visible');
		window.loadingUpdater = setTimeout('loadingUpdate()',400);
	}
}
function loadingUpdate() {
	$('.popupDialog:has(#loadingDots)').fadeIn();
	var dots = $('.popupDialog #loadingDots:visible');
	if(dots.length) {
		dots.html(dots.html()=='...' ? '' : dots.html()+'.');
		window.loadingUpdater = setTimeout('loadingUpdate()',600);
	}
}
function loadingStop() {
	if($('.popupDialog #loadingDots').length) {
		clearTimeout(window.loadingUpdater);
		$('.popupDialog').stop();
		$('.popupDialog').fadeOut();
	}
}

function logout() {
	return ajax({a:'logout'});
}
 
function unsupported() {
	KBAlert("Error: This feature isn't supported, yet.");
	return false;
}
function msgConfirm(question) {
	return confirm(question);
}

function html_escape(input) {
	if(!input) return '';
	inStr = input+''; 
	if(input!=inStr) return '';
	return inStr.replace(/&/g,'&amp;').replace(/'/g,'&#039;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); //'
}
function attr_escape(content) {
	return html_escape(content);
}
function in_array(needle,haystack) {
	if(!haystack instanceof Array) throw "Error: Haystack isn't an array.";
	for(var i in haystack) if(haystack[i]==needle) return true;
	return false;
}











