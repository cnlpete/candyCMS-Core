window.addEvent("domready",function(){$each($$(".js-image_overlay"),function(a){a.set("text",a.get("title"));var b=a.getSize();a.setStyle("margin-top","-"+b.y+"px");a.fade("hide")})});function hideDiv(a){new Fx.Slide(a).slideOut()}function fadeDiv(a){document.id(a).fade("toggle")}function imageOverlay(a,b){fadeDiv(a);document.id(a).setStyle("margin-top",b)}function showDiv(a){document.id(a).setStyle("display","inline");if(document.id("js-flash_success")||document.id("js-flash_error")){(function(){hideDiv(a)}).delay(5000)}}if(document.id("js-flash_success")||document.id("js-flash_error")){showDiv("js-flash_message")}function quoteMessage(d,b){var a=document.id(b).get("html");var c="[quote="+d+"]"+a+"[/quote]\n";var e=$("js-create_commment_text").get("value");document.id("js-create_commment_text").set("html",e+c);return false}function resetContent(a){document.id(a).set("html","")}function confirmDelete(a){if(confirm(LANG_DELETE_FILE_OR_CONTENT)){parent.location.href=a}}if($$(".js-tooltip")){$$(".js-tooltip").each(function(b,a){var c=b.get("title").split("::");b.store("tip:title",c[0]);b.store("tip:text",c[1])});var oTips=new Tips(".js-tooltip",{fixed:true,hideDelay:100,showDelay:100});oTips.addEvents({show:function(a){a.fade("in")},hide:function(a){a.fade("out")}})}function stripNoAlphaChars(a){a=a.replace(/ /g,"_");a=a.replace(/Ä/g,"Ae");a=a.replace(/ä/g,"ae");a=a.replace(/Ö/g,"Oe");a=a.replace(/ö/g,"oe");a=a.replace(/Ü/g,"Ue");a=a.replace(/ü/g,"ue");a=a.replace(/ß/g,"ss");a=a.replace(/\W/g,"_");return a}function stripSlash(a){a=a.replace(/\//g,"&frasl;");return a}function reloadPage(c,b){var a="js-ajax_reload";document.id(a).setStyle("display","block").addClass("center");document.id(a).set("html","<img class='js-loading' src='"+b+"/loading.gif' alt='"+LANG_LOADING+"' />");document.id(a).load(c)}function checkPasswords(){if(document.id("password")&&document.id("password2")){if(document.id("password").value==document.id("password2").value){document.id("icon").set("class","icon-success")}else{document.id("icon").set("class","icon-close")}}};