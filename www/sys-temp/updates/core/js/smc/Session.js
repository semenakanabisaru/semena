var SessionControl = function(lifetime_min, show_settings_link) 
{
	 lifetime_min = parseInt(lifetime_min);
     if(!lifetime_min) lifetime_min = 10;

     this.lifetime_min  = lifetime_min;

     this.setLastAction();
     this.setCurrentPeriod();

     this.last_pinged_success  = true;

     this.window_warning_closed  = false;
     this.window_session_closed  = false;
     this.show_settings_link  = parseInt(show_settings_link)>0 ? 1:0;

     var self = this;
     jQuery(function() {self.init();});

}  

SessionControl.prototype.showWarningMessage = function(clear_interval) {
     
     if(clear_interval)
     {
          clearInterval(this.timer);
          this.timer = null;
     }

     if(this.timer)
     {
          return;
     }
     
     var self = this;

     self.session_close_time = new Date();
     self.session_close_time.setMinutes ( self.session_close_time.getMinutes() + 1 ); 
          
     this.timer = setInterval(function() {
        
          var time = new Date();
          
          var totalRemains=(self.session_close_time.getTime()-time.getTime()); 
          
          // отображается таймер 
          if (totalRemains>1){ 
               var RemainsSec = (parseInt(totalRemains/1000));
               
               var RemainsMinutes = (parseInt(RemainsSec/60));

               var lastSec = RemainsSec-RemainsMinutes*60;//осталось секунд 
               if (lastSec<10){lastSec="0"+lastSec}; 

               var msg = RemainsMinutes+":"+lastSec;
               
               var timeNoUserHereMin = parseInt( ( (new Date).getTime() - self.last_action_time.getTime()) / 60 / 1000);
     
               
               self.message("Вы отсутствуете " + timeNoUserHereMin + " мин. " +
                            "Сессия скоро закончится <br>" + 
                            msg);
          } 
               
          else {
               clearInterval(self.timer);
               self.timer = null;
               
               jQuery.get("/session.php", function(data) {
                    if(data=='closed') {
                         self.sessionCloseMessage(true);
                    }
                    if(data=='warning') {
                         
                         self.showWarningMessage(true)
                    }
                    if(data=='ok') {
						self.closeMessage();
						self.setCurrentPeriod();
                    }
               })
               
          }
     }, 1000)
     
     
}

SessionControl.prototype.sessionCloseMessage = function(clear_interval) {
     var self = this;
     clear_interval = clear_interval || false;
     
     if(clear_interval)
     {
          clearInterval(this.timer);
          this.timer = null;
     }
     
     if(this.timer)
     {
          return;
     }
     
     this.last_pinged_success  = false;
     
     var timeNoUserHereMin = parseInt( ( (new Date).getTime() - self.last_action_time.getTime()) / 60 / 1000);
     
     var msg = jQuery("<div>");
     msg.html( "<br/>Вы отсутствовали более "+this.lifetime_min+" мин, поэтому Ваша сессия была закончена.<br/><br/> ");
     
     var str = "<form><table cellspacing='5' width='100%' style='font-size:12px;'>\
							<tr><td>Логин: </td><td> <input type='text' id='session_contorl_login' /></td></tr>\
							<tr><td>Пароль:</td><td> <input type='password' id='session_contorl_passsword' /></td></tr>\
							</table><br/>\
							<input type='submit' value='Хочу продлить сессию'>  ";
							
							
     if(this.show_settings_link) {
		str += "<br/> <br/><a href='/admin/config/main/' target='_blank'>Настроить таймаут неактивности</a>";
     }    
     
     str += "</form>";
     
     var form = jQuery(str);
     
     // сабмит
     form.submit(function() {
          var login = jQuery("#session_contorl_login").val();
          var pwd = jQuery("#session_contorl_passsword").val();
          
          if(jQuery.trim(login) != '' && jQuery.trim(pwd) != '')
          {
               // занова авторизуемся
               // если получилось - прячем форму
               // ставим новый период сессии
               self.ping(login, pwd, function(d) {
                    if(d == 'ok')
                    {
                         self.closeMessage();
                         clearInterval(self.timer);
                         self.timer = null;
                         alert("Сессия успешно восстановлена!");
                    }
                    else 
                    {
                         alert('Ошибка! Неправильный логин или пароль');
                    }
                    
                    self.setCurrentPeriod();
               });
               
          } 
          else {
               alert('Укажите логин и пароль для восстановления сессии!');
          }
          
          
          return false;
     }
     );
     
     msg.append(form);
     
     self.message(msg);
     
     this.timer = 1;
               
     this.setCurrentPeriod();

}

SessionControl.prototype.message = function(msg) {
     
     var self = this;
     
     if(typeof msg == 'string') 
     {
          msg = '<br/><p> ' + msg + ' </p>';
     }
     
     if(!this.jgrowl)
     {
          this.jgrowl = jQuery('<div id="SessionjGrowl"></div>').addClass( 'top-right' ).appendTo('body');
          
          this.jgrowl.jGrowl(msg, {
               'header': 'UMI.CMS',
               'dont_close': true, 
               'close': function() {self.jgrowl = 0;}
          });
          
          return;
     }
     
     var o = this.jgrowl.find('.jGrowl-notification .jGrowl-message');
     
     if(typeof msg == 'string') 
     {
          o.html(msg);
     }
     else
     {
          o.html("");
          o.append(msg)
          
     }
     
     

}
SessionControl.prototype.closeMessage = function() {
     var o = this.jgrowl;
     
     if(this.jgrowl && o.length)
     {
          o.data('jGrowl.instance').shutdown();
          o.remove();
          
     }
     this.jgrowl = 0;
     
     
}

SessionControl.prototype.init = function() {
     var self = this;



     jQuery(document).bind('click keydown mousedown', function() {
          self.setLastAction()
     });
     
     
     setTimeout(function() { 
          jQuery( 'iframe' ).each( function() {
               var d = this.contentWindow || this.contentDocument;
               
               if (d.document)
               {
                    d = d.document;
                    
                    jQuery(d).bind('click keydown mousedown', function() {
                         self.setLastAction()
                    });
               }
	     });
     },30000);	
     
     this.timerAutoPing = setInterval(function() { 
          self.checkAutoPing();
     }, 60 * 1000);	

}

SessionControl.prototype.destroy = function() {
     if(this.timerAutoPing)
     {
		clearInterval( this.timerAutoPing);
     }
     
     if(this.timer)
     {
		clearInterval( this.timer);
		this.timer = 0;
     }
     
   
	 return true;
}


SessionControl.prototype.ping = function(login, password, handler) {
     
     var params = {};//
     
     if(login)
     {
          params = {'u-login':login, 'u-password':password};
     }
     
     var self = this;
     jQuery.post('/users/ping/', params, function (d) {
          if( d == 'ok') 
          {
               self.last_pinged_success = true;
          }
          else 
          {
               self.last_pinged_success = false;
          }
          
          if(self.timerAutoPing)
          {
               clearInterval(self.timerAutoPing);
          }
          
          self.timerAutoPing = setInterval(function() { 
               self.checkAutoPing();
          }, 60 * 1000);	

          if(handler) {
               handler(d);
          }
     });
     
}

SessionControl.prototype.setCurrentPeriod = function(is_here) {
     this.current_period_start_time = new Date();

     this.current_period_end_time = this.current_period_start_time;
     
     this.current_period_end_time.setMinutes ( this.current_period_end_time.getMinutes() + this.lifetime_min ); 
     
}



SessionControl.prototype.setLastAction = function() {
     this.last_action_time  = new Date;
     
     if(this.last_pinged_success) 
     {
          this.closeMessage();
          clearInterval(this.timer);
          this.timer = null;
     }
     
}

SessionControl.prototype.startAutoActions = function() {
	var self = this;
	this.timerAutoAction = setInterval(function() {
		self.setLastAction();
	}, 60000)
}

SessionControl.prototype.stopAutoActions = function() {
	if(this.timerAutoAction) clearInterval(this.timerAutoAction);
}


SessionControl.prototype.getLastAction = function() {
     return this.last_action_time;
}

SessionControl.prototype.isUserhere = function() {
     var time_left_min = (((new Date).getTime() - this.last_action_time.getTime()) ) / 60000;

     var f = false;
     if(time_left_min < this.lifetime_min - 0.2)
     {
          f = true;
     }
     
     return f;

}

SessionControl.prototype.checkAutoPing = function() {
     // сессия была закрыта
     // окно восстановления
     if(!this.last_pinged_success)
     {
          this.sessionCloseMessage();
          return false;
     }
     
     // отображается окно
     if(this.timer)
     {
          return false;
     }
     
     var it_is_time = false;

     var time_left_min = ((this.current_period_end_time.getTime() - (new Date).getTime() ) ) / 60000;


     //if(time_left_min < 2) { it_is_time = "pre_check";} 

     if(time_left_min < 1.2) { it_is_time = 1;}


     if(!it_is_time) {
          return;
     }

     var self = this;
     
     var is_user_here = this.isUserhere();
     
     // прошлые сессии были продлены
     // осталось примерно минута, пользователя нет
        if(is_user_here === false) {
          // возможно пользователь открывал другими вкладками
          // узнаем это на сервере
          jQuery.get("/session.php", function(data) {
				this.settings_link = false;
               if(data=='ok') {
                    return;
               }
               if(data=='closed') {
                    self.sessionCloseMessage();
               }
               if(data=='warning') {
                    self.showWarningMessage();
               }
               if( data=='warning_settings') {
					this.settings_link = true;
                    self.showWarningMessage();
               }
          
          })
  
     }    
          
     // пользователь нажимал кнопки, последний период был продлен, пингуем снова
     else  {
          self.closeMessage();
          self.ping();
          self.setCurrentPeriod();
     }
     
     return;
     
}
