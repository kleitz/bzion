function notifsy(a,b){b="undefined"!=typeof b?b:"success";var c=$(".notification");switch(c.show(),c.css("top","-"+c.outerHeight(!0)+"px"),c.attr("class","notification "+b),c.css("left",Math.max(0,($(window).width()-c.outerWidth())/2+$(window).scrollLeft())+"px"),b){case"success":icon="check";break;case"error":icon="exclamation";break;default:icon="question"}$(".notification").find("i").attr("class","fa fa-"+icon),$(".notification").find("span").html(a),c.animate({top:"0"},500),c.delay(2e3).fadeOut(1e3)}