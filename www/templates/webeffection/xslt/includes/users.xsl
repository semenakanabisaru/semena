<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:umi="http://www.umi-cms.ru/TR/umi">

    
<xsl:template match="result[@module = 'users'][@method = 'login']">
    <h1><xsl:value-of select="@header" /></h1>
    <xsl:apply-templates select="document('udata://users/auth/')/udata" />
</xsl:template>

<xsl:template match="result[@module = 'users'][@method = 'auth']">
    <h1><xsl:value-of select="@header" /></h1>
    <xsl:apply-templates select="document('udata://users/auth/')/udata" />
</xsl:template>

<xsl:template match="result[@module = 'users'][@method = 'login_do']">
    <xsl:choose>
        <xsl:when test=".//user/@status = 'auth'">
        
            <xsl:apply-templates select="document('udata://content/redirect/(/personal/)/')/udata" />
            
        </xsl:when>
        <xsl:otherwise>
            
            <h1><xsl:value-of select="@header" /></h1>
            <p>Пользователь с такими данными не найден. Убедитесь в правильности введенных данных и попробуйте снова.</p>
            <xsl:apply-templates select="document('udata://users/auth/')/udata" />
    
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<!-- форма авторизации -->
<xsl:template match="udata[@module = 'users'][@method = 'auth']">
    <form method="post" action="/users/login_do/" id='enterform' class='form_common' >
        <input type="hidden" name="from_page" value="{$_http_referer}" />
        <ul>
            <li>
                <label>Введите логин:</label>
                <input class='text' type="text" name="login" />  
            </li>
            <li>
                <label>Введите пароль:</label>
                <input class='text' type="password" name="password" />
            </li>
            <li>
                <span class='button' onclick="document.getElementById('enterform').submit();">Войти в кабинет</span>
                <a class='forget' href="/users/forget/">Забыли пароль?</a>
            </li>
        </ul>	
    </form>
 </xsl:template>
 

 
<xsl:template match="result[@method = 'registrate']">
    <h1><xsl:value-of select="@header" /></h1>
    <xsl:apply-templates select="document('udata://users/registrate')/udata" />
</xsl:template>

<xsl:template match="udata[@method = 'registrate']">
   
       <p> Уважаемые посетители, сообщаем вам, что вы можете делать заказы у «Семяныча» без регистрации!</p>
        Зарегистрированные пользователи получают дополнительные преимущества – такие, как:<br/><br />
        <ul>
            <li>- «Персональная накопительная скидка» - действует при любом заказе, увеличивается вместе с общей суммой заказов клиента, от 1% до 15%.</li><br/>
            <li>- Удобный способ отслеживать текущие заказы и статус их доставки в разделе «Мои Заказы»</li><br/>
            <li>- Приоритет в оформлении и отправке заказов</li><br/>
        </ul>
        <br />
        <p>После заполнения формы регистрации и нажатия кнопки «зарегистрироваться», на указанную электронную почту (e-mail) автоматически будет отправлено письмо содержащее ссылку для активации аккаунта.</p>
   
    <form  action="/users/registrate_do/" class='form_common' id='regform' method="post" enctype="multipart/form-data" >
        <ul>
            <li>
                <label>Логин:*</label>
                <input class='text' type="text" name="login" />
            </li>
            <li>
                <label>Пароль:*</label>
                <input class='text' type="password" name="password" />
            </li>
            <li>
                <label>Повторите пароль:*</label>
                <input class='text' type="password" name="password_confirm" />
            </li>
            <li>
                <label>Е-mail:*</label>
                <input class='text' type="text" name="email" />
            </li>
            <xsl:apply-templates select="document('udata://system/captcha')/udata[url]" />
            <li>
                <span class='button' onclick="submitRegform();">Регистрация</span>
                <span class="report">
                    <xsl:value-of select="document('udata://system/listErrorMessages')//item" />
                </span>
            </li>
        </ul>
    </form>
    <script type="text/javascript">
        myRestoreFormData(document.getElementById('regform'));
        function submitRegform(){
            var form = document.getElementById('regform');
            mySaveFormData(form);
            form.submit();
        }
        truncateFormData('regform');
    </script>
</xsl:template>



<xsl:template match="udata[@module = 'system'][@method = 'captcha']">
	<li>
        <label>
            Код с картинки*
        </label>
        <img src="{url}{@random_string}" srcfirst="{url}{@random_string}" class='captcha_image' id='captcha' />
        <span class='captcha_update' onclick="updateCaptcha();"></span>
		<input type="text" name="captcha" class='captcha_text' />
	</li>
</xsl:template>



<xsl:template match="result[@module = 'users'][@method = 'registrate_done']">
    <h1><xsl:value-of select="@header" /></h1>
Cпасибо за регистрацию! <br /><br />
<p>На указанную вами электронную почту (e-mail) было отправлено письмо содержащее ссылку для активации. Пожалуйста, проверьте свою почту и пройдите по ссылке для успешной активации вашего аккаунта. </p>

<p>Обратите внимание! Письмо с подтверждением генерируется автоматически, поэтому может попасть к вам на почту в папку «Спам». Проверьте ее в случае необходимости.</p>
</xsl:template>



<xsl:template match="result[@module = 'users'][@method = 'activate']">
    <h1><xsl:value-of select="@header" /></h1>
    <!-- подключение обработчика активации пользователя -->
    <xsl:apply-templates select="document(concat('udata://users/activate/',$param0,'/'))/udata" />
</xsl:template>

<xsl:template match="udata[@module = 'users'][@method = 'activate']">
    <!-- если активация успешна, то происходит перенаправление на стартовую страницу -->
    <xsl:apply-templates select="document('udata://content/redirect/(/personal/)/')/udata" />
</xsl:template>
    
<xsl:template match="udata[@module = 'users'][@method = 'activate'][error]">
	<!-- выведение ошибок, если они произошли -->
    <p><xsl:value-of select="error" /></p>
</xsl:template>


<xsl:template match="result[@method = 'forget']">
    <h1><xsl:value-of select="@header" /></h1>
    <form id="forget" method="post" action="/users/forget_do/" class='form_common'>
        <ul>
            <li>
                <label>Введите email</label>
                <input class='text' type="text" name="forget_email" />
            </li>
            <li>
                <span class='button' onclick="document.getElementById('forget').submit();">Восстановить</span>
                <span class="report">
                    <xsl:value-of select="document('udata://system/listErrorMessages')//item" />
                </span>
            </li>
        </ul>
    </form>
</xsl:template>


<xsl:template match="result[@method = 'forget_do']">
    <h1><xsl:value-of select="@header" /></h1>
    <p>На ваш почтовый ящик выслана ссылка для получения нового пароля.</p>
</xsl:template>



<xsl:template match="result[@module = 'users'][@method = 'restore']">
    <h1><xsl:value-of select="@header" /></h1>
    <xsl:apply-templates select="document(concat('udata://users/restore/default/',$param0,'/'))/udata"  />
</xsl:template>

<xsl:template match="udata[@module = 'users'][@method = 'restore'][@status = 'success']">
        <div>Пароль успешно изменен, на e-mail адрес, указанный при регистрации выслано уведомление.</div>
        <div>
            <p>Логин:   <xsl:value-of select="login" /></p>
            <p>Пароль: <xsl:value-of select="password" /></p>
        </div>
</xsl:template>

<xsl:template match="udata[@module = 'users'][@method = 'restore'][@status = 'fail']">
        <div>Невозможно восстановить пароль: неверный код активации.</div>
</xsl:template>


<!-- <xsl:template match="udata[@module = 'users'][@method = 'restore'][@status = 'success']">
    <p>Пароль изменен, на e-mail выслано уведомление.</p>
    <p>
        <span>Логин: <xsl:value-of select="login" /></span><br />
        <span>Пароль: <xsl:value-of select="password" /></span>
    </p>
</xsl:template>

<xsl:template match="udata[@module = 'users'][@method = 'restore'][@status = 'fail']">
    <p>Невозможно восстановить пароль: неверный код активации.</p>
</xsl:template> -->



</xsl:stylesheet>