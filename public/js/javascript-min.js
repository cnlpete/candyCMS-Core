function showDiv(a){$(a).setStyle("display","block")}function hideDiv(a){new Fx.Slide(a).toggle().hide()}if($("success")){showDiv("flashMessage")}if($("error")){showDiv("flashMessage")}function quoteMessage(d,b){var a=$(b).get("html");var c="[quote="+d+"]"+a+"[/quote]\n";var e=$("createCommentText").get("value");$("createCommentText").set("html",e+c);return false}function destroyContent(a){$(a).set("html","")}function confirmDelete(a,b){if(confirm("Are you sure to delete "+a+"?")){parent.location.href=b}}if($$(".tooltip")){$$(".tooltip").each(function(b,a){var c=b.get("title").split("::");b.store("tip:title",c[0]);b.store("tip:text",c[1])});var myTips=new Tips(".tooltip");myTips.addEvent("show",function(a){a.fade("in")});myTips.addEvent("hide",function(a){a.fade("out")})}function stripNoAlphaChars(a){a=a.replace(/ /g,"_");a=a.replace(/Ä/g,"Ae");a=a.replace(/ä/g,"ae");a=a.replace(/Ö/g,"Oe");a=a.replace(/ö/g,"oe");a=a.replace(/Ü/g,"Ue");a=a.replace(/ü/g,"ue");a=a.replace(/ß/g,"ss");a=a.replace(/\W/g,"_");return a}function stripSlash(a){a=a.replace(/\//g,"&frasl;");return a}function reloadPage(c,b){var a="js-ajax_reload";$(a).set("html","<img src='"+b+"/loading.gif' alt='loading...' />");$(a).load(c)}function checkPasswords(a){if($("password")&&$("password2")){if($("password").value==$("password2").value){$("icon").set("class","icon-success")}else{$("icon").set("class","icon-close")}}};