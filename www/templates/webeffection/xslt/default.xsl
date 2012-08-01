<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:exsl="http://exslt.org/common"
    xmlns:dyn="http://exslt.org/dynamic"
    extension-element-prefixes="dyn"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">
<xsl:output
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	encoding="utf-8" method="html" indent="yes"/>



<xsl:include href="includes/users.xsl" />

<xsl:param name="param0" />
<xsl:param name="_http_referer" />
<xsl:param name="mistaken" />
<xsl:param name="per_page" select="'6'" />

<xsl:variable select="document('upage://2')//udata" name="constants" />
<xsl:variable name="per_page_options" >
    <select>
        <option>6</option>
        <option>12</option>
        <option>18</option>
        <option>24</option>
    </select>
</xsl:variable>

<xsl:template match="/">
<html>
<head>

	<title><xsl:value-of select="result/@title"/></title>
	<meta name="description" content="{result/meta/description}" />
	<meta name="keywords" content="{result/meta/keywords}"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link href="/templates/webeffection/css/common.css" type="text/css" rel="stylesheet" />
	<link href="/templates/webeffection/css/fancy/jquery.fancybox-1.3.4.css" type="text/css" rel="stylesheet" />

    <!-- edit-in-place -->
    <xsl:value-of select="document('udata://system/includeQuickEditJs')/udata" disable-output-escaping="yes" />
    <xsl:value-of select="document('udata://system/includeEditInPlaceJs')/udata" disable-output-escaping="yes" />

    <script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js' ></script>
    <script type='text/javascript' src='/templates/webeffection/js/jquery.jloupe.js' ></script>
    <script type='text/javascript' src='/templates/webeffection/js/jquery.fancybox-1.3.4.pack.js' ></script>
    <script type='text/javascript' src='/templates/webeffection/js/common.js' ></script>

    <!-- RedHelper -->
    <script id="rhlpscrtg" type="text/javascript" charset="utf-8" async="async"
        src="http://web.redhelper.ru/service/main.js?c=kazanworkout"></script>
    <!--/Redhelper -->
    

</head>
<body id="pageid-{result//group[@name='my_params']//property[@name='page_id']/value}">
    	
    <div id='wrapper'>
        
        <!-- основное меню -->
        <div id="top_line">
            <div class='site_width'>
                <ul id='main_menu'>

                    <li><a href='/shipping/' umi:element-id="3" umi:field-name="name" >Доставка и оплата</a></li>
                    <li><a href='/help/' umi:element-id="4" umi:field-name="name" >Помощь</a></li>

                    <li class="delimiter"></li>
                    <li><a class='email' umi:element-id="2" umi:field-name="email">
	                    <xsl:value-of select="$constants//property[@name='email']/value" />
	                </a></li>
                    <li><a class='skype' umi:element-id="2" umi:field-name="skype">
                    	<xsl:value-of select="$constants//property[@name='skype']/value" />
                    </a></li>
                    <li><a class='icq' umi:element-id="2" umi:field-name="icq">
                    	<xsl:value-of select="$constants//property[@name='icq']/value" />
                    </a></li>

                </ul>
                <ul id='auth'>
                    <li class="delimiter left"></li>

                    <xsl:apply-templates select="result/user" mode='user_auth' />

                    <li class="delimiter right"></li>
                    <li class="social">
                        <a target='_blank' rel="nofollow" href="{$constants//property[@name='vkontakte']/value}" class='vk' title='В контакте'></a>
                        <a target='_blank' rel="nofollow" href="{$constants//property[@name='facebook']/value}" class='fb' title='Facebook'></a>
                        <a target='_blank' rel="nofollow" href="{$constants//property[@name='twitter']/value}" class='tw' title='Twitter'></a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- лого, поиск, корзина, меню юзера -->
        <div id='next_line' class='site_width'>
            <a href='/'><img id='logo' src='/templates/webeffection/images/logo_color.png' alt='Семяныч' title='Семена конопли' /></a>
            <ul id="user_menu">

            	<xsl:apply-templates select="result/user" mode='user_menu' />
            	
            </ul>
            <form id="search" action="/search/search_do/">
                <input name='search_string' class='text' clean='clean' type='text' value='Поиск' />
                <input class='button' type='submit' value='' />
            </form>

            <div id="cart">
                <xsl:if test="result/@pageId != 268">
                    <xsl:apply-templates select="document('udata://emarket/cart/')//summary" mode="cart_state" />
                </xsl:if>    
            </div>

            <xsl:apply-templates select="document('udata://core/navibar')/udata" />

        </div>

        <!-- весь основной контент -->
        <div class="site_width">
            <div id="content">
                
                <xsl:apply-templates select='result' />

            </div>
            <div id="sidebar">
                
                <div id='sidebar_menu'>
                    <a href='/producers/' class='button'><div umi:element-id="28" umi:field-name="name">Каталог</div></a>
                        <div class="catalog-menu">
                        <xsl:apply-templates select="document('udata://content/menu/0/2/28')/udata" mode='left-catalog-menu' />
                        </div>

                        <div class='header last'><div>Расширенный поиск</div></div>
                        <div class='body last'> 
                            <xsl:apply-templates select="document('utype://116/')//group[@name = 'cats']" mode='fcats' />
                            <xsl:apply-templates select="document('utype://116/')//group[@name = 'types']" mode='ftypes' />
                            <div class='scope'>
                                <div class="title"><div><span><em>По содержанию ТГК</em></span></div></div>
                                <ul id='tgk_list'>
                                    <li>
                                        <input id='tgk_params' type="hidden" step='10' unit='%' min='0' max='100' />
                                        от
                                        <span class='more_less'>
                                            <input id='tgk_from' type='text' value='10%' onchange="checkHandEditedValues('tgk', 'from');" />
                                            <span class='more'></span>
                                            <span class='less'></span>
                                        </span>
                                        до 
                                        <span class='more_less'>
                                            <input id='tgk_to' type='text' value='100%' onchange="checkHandEditedValues('tgk', 'to');" />
                                            <span class='more'></span>
                                            <span class='less'></span>
                                        </span>
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('tgk', false, 10);">
                                            До 10%
                                        </span>
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('tgk', 10, 15);">
                                            От 10% до 15%
                                        </span>
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('tgk', 15, 20);">
                                            От 15% до 20%
                                        </span>
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('tgk', 20, false);">
                                            Более 20%
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            <div class='scope'>
                                <div class="title"><div><span><em>По стоимости</em></span></div></div>
                                <ul id='price_list'>
                                    <li>
                                        <input id='price_params' type="hidden" step='100' min='0' max='4000' />
                                        от
                                        <span class='more_less'>
                                            <input id='price_from' type='text' value='100' onchange="checkHandEditedValues('price', 'from');" />
                                            <span class='more'></span>
                                            <span class='less'></span>
                                        </span>
                                        до 
                                        <span class='more_less'>
                                            <input id='price_to' type='text' value='4000' onchange="checkHandEditedValues('price', 'to');" />
                                            <span class='more'></span>
                                            <span class='less'></span>
                                        </span>
                                        руб
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('price', false, 100);">
                                            До 100 руб.
                                        </span>
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('price', 100, 200);">
                                             От 100 до 200 руб.
                                        </span>
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('price', 200, 300);">
                                            От 200 до 300 руб.
                                        </span>
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('price', 300, 400);">
                                            От 300 до 400 руб.
                                        </span>
                                    </li>
                                    <li >
                                        <span class='clickable' onclick="setRangeValues('price', 400, 500);">
                                            От 400 до 500 руб.
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            <xsl:apply-templates select="document('udata://catalog/getCategoryList//(producers)/')/udata" mode='fproducers' />
                        </div>
                        <a class='counter'></a> 

                </div>


                <xsl:if test="$constants//property[@name='izoborazhenie']">
                    <a href="{$constants//property[@name='ssylka']/value}"><img class='banner' src="{$constants//property[@name='izoborazhenie']/value}" alt="баннер" /></a>    
                </xsl:if>
                <!-- 
                <a href='#'><img class='banner' src="/templates/webeffection/images/temp/5.jpg" alt="баннер" /></a>
                -->

            </div>
            <div class="clear"></div>
        </div>

        <!-- буфер, чтобы не было скроллбара -->
        <div id="buffer"></div>

    </div>

    <!-- футер -->
    <div id='footer'>
        <div class="site_width">
            <a href='/' class='logo'>
                <img src='/templates/webeffection/images/logo_gray.png' alt='Семяныч' title='Семена конопли'/>
                <address>© 2012 Семяныч-семена конопли</address>
            </a>
            <ul class="menu">
                <li>
                    <a href='/shipping/' umi:element-id="3" umi:field-name="name" >Доставка и оплата</a>
                    <a href='/help/' umi:element-id="4" umi:field-name="name" >Помощь</a>
                    <a href='/privacy_policy/' umi:element-id="5" umi:field-name="name" >Конфиденциальность</a>
                    <a href='/feedback/' umi:element-id="6" umi:field-name="name" >Обратная связь</a>
                </li>
                <li class='social'>
                    <span>Семяныч в блогах:</span> 
                    <a target='_blank' rel="nofollow" href='{$constants//property[@name="vkontakte"]/value}' class='vk'>В контакте</a>
                    <a target='_blank' rel="nofollow" href='{$constants//property[@name="facebook"]/value}' class='fb'>Facebook</a>
                    <a target='_blank' rel="nofollow" href='{$constants//property[@name="livejournal"]/value}' class='lj'>LiveJournal</a>
                    <a target='_blank' rel="nofollow" href='{$constants//property[@name="twitter"]/value}' class='tw'>Twitter</a>
                </li>
                <li>
                    <a href='/company/' umi:element-id="7" umi:field-name="name" >О компании</a>
                    <a href='/partnership/' umi:element-id="8" umi:field-name="name" >Партнерство</a>
                    <a href='/contacts/' umi:element-id="9" umi:field-name="name" >Контакты</a>
                </li>
            </ul>
        </div>
    </div>
<!-- Yandex.Metrika counter -->

<script type="text/javascript">

var yaParams = {/*Здесь параметры визита*/};

</script>



<div style="display:none;"><script type="text/javascript">

(function(w, c) {

(w[c] = w[c] || []).push(function() {

try {

w.yaCounter12623245 = new Ya.Metrika({id:12623245, enableAll: true, trackHash:true, webvisor:true,params:window.yaParams||{ }});

}

catch(e) { }

});

})(window, "yandex_metrika_callbacks");

</script></div>

<script src="//mc.yandex.ru/metrika/watch.js" type="text/javascript" defer="defer"></script>

<noscript><div><img src="//mc.yandex.ru/watch/12623245" style="position:absolute; left:-9999px;" alt="" /></div></noscript>

<!-- /Yandex.Metrika counter -->

</body>
</html>
</xsl:template>


<xsl:template match="udata" mode="left-catalog-menu">
    <xsl:apply-templates select="items/item" mode="left-catalog-menu-item" />
    <!--                         <div class="scope"><div class="title"><div><a href="/producers/dutch_passion/"><span>Dutch Passion</span></a></div></div></div>
<div class="scope"><div class="title"><div><a href="/producers/grassomatic/"><span>Grass-O-Matic</span></a></div></div></div>
<div class="scope"><div class="title"><div><a href="/producers/green_house_seeds/"><span>Green House Seeds</span></a></div></div></div>
<div class="scope"><div class="title"><div><a href="/producers/joint_doctor/"><span>Joint Doctor</span></a></div></div></div>
<div class="scope"><div class="title"><div><a href="/producers/paradise_seeds/"><span>Paradise Seeds</span></a></div></div></div>
<div class="scope"><div class="title"><div><a href="/producers/pyramid_seeds/"><span>Pyramid Seeds</span></a></div></div></div>
<div class="scope"><div class="title"><div><a href="/producers/serious_seeds/"><span>Serious Seeds</span></a></div></div></div> -->
</xsl:template>
<xsl:template match="item" mode="left-catalog-menu-item">
    <div class="scope"><div class="title"><div><a href="{@link}"><span><xsl:value-of select="." /></span></a></div></div></div>
</xsl:template>

<xsl:template match="item[@status = 'active']" mode="left-catalog-menu-item">
    <div class="scope active-scope"><div class="title"><div><a href="{@link}"><span><xsl:value-of select="." /></span></a></div></div></div>
</xsl:template>


<!-- меню пользователя -->
<xsl:template match="user" mode="user_menu">
	<li><a href='/personal/points/' class='points'>Мои скидки</a></li> <!-- /users/registrate/ -->
	<li><a href='/personal/orders/' class='orders'>Мои заказы</a></li> <!-- /users/registrate/ -->
	<li><a href='/cart_content/' class='cart'>Моя корзина</a></li>
</xsl:template>

<xsl:template match="user[@status='auth']" mode="user_menu">
	<li><a href='/personal/points/' class='points'>Мои скидки</a></li>
	<li><a href='/personal/orders/' class='orders'>Мои заказы</a></li>
	<li><a href='/cart_content/' class='cart'>Моя корзина</a></li>
</xsl:template>

<!-- кнопки войти/регистрация -->
<xsl:template match="user" mode="user_auth">
	<li class='enter'><a href="/users/auth/" class='item'>Войти</a></li> <!-- /users/auth/ -->
	<li class="delimiter small"></li>
	<li><a href="/users/registrate/" class='item'>Регистрация</a></li> <!-- /users/registrate/ -->
</xsl:template>

<xsl:template match="user[@status='auth']" mode="user_auth">
    <xsl:variable select="document(concat('uobject://', @id))//group[@name = 'short_info']" name="short_info" />

	<li class='name'>
        <a class='item' href='/personal/'>
            <xsl:value-of select="document(concat('uobject://', @id))//property[@name = 'login']/value" />
            <!-- <xsl:text> </xsl:text>
            <xsl:value-of select="$short_info/property[@name = 'fname']/value" /> -->
        </a>
    </li>
	<li class="delimiter small"></li>
	<li><a href="/users/logout/" class='item'>Выйти</a></li>
</xsl:template>

<!-- состояние корзины -->
<xsl:template match="summary" mode='cart_state'>
    <xsl:if test="./amount != 0">
        <div class='details'>
            <span>
                <xsl:value-of select="./amount" />
            </span>
            <xsl:text> товаров на сумму </xsl:text>
            <span>
                <xsl:value-of select="./price/actual" />
                <xsl:text> руб.</xsl:text>
            </span>
        </div>
        <a href='/cart_content/'>Оформить заказ</a>
    </xsl:if>    
</xsl:template>

<!-- фильтры -->
<xsl:template match="udata[@module = 'catalog'][@method = 'getCategoryList']" mode='fproducers'>
    <div class='scope last'>
        <div class="title"><div><span><em>По производителю</em></span></div></div>
        <ul id='producers_list'>
            <xsl:apply-templates select=".//item" mode="fproducers" />
        </ul>
    </div>
</xsl:template>

<xsl:template match="item" mode="fproducers" >
    <li >
        <span class='clickable' id='producer_{@id}' onclick="saveSelectableValues('fproducers', {@id});">
            <xsl:value-of select="." />
        </span>
    </li>
</xsl:template>

<xsl:template match="group" mode='fcats' >
    <div class='scope'>
        <div class='title'><div><span><em>По категории</em></span></div></div>
        <ul id='cats_list'>
            <xsl:apply-templates select="./field" mode="fcats" />
        </ul>
    </div>
</xsl:template>

<xsl:template match="field" mode="fcats" >
    <li >
        <span class='clickable' id='cat_{@name}' onclick="saveSelectableValues('fcats', '{@name}');">
            <xsl:value-of select="@title" />
        </span>
    </li>
</xsl:template>

<xsl:template match="group" mode='ftypes'>
    <div class='scope'>
        <div class="title"><div><span><em>По типу</em></span></div></div>
        <ul id='types_list'>
            <xsl:apply-templates select="./field" mode="ftypes" />
        </ul>
    </div>
</xsl:template>

<xsl:template match="field" mode="ftypes" >
    <li >
        <span class='clickable' id='type_{@name}' onclick="saveSelectableValues('ftypes', '{@name}');" >
            <xsl:value-of select="@title" />
        </span>
    </li>
</xsl:template>

<!-- контент -->
<xsl:template match="result[@module = 'content'][@method = 'content']">
	<h1 umi:element-id="{@pageId}" umi:field-name="h1">
		<xsl:value-of select=".//property[@name='h1']/value" />
	</h1>
	<div umi:element-id="{@pageId}" umi:field-name="content">
		<xsl:value-of select=".//property[@name='content']/value" disable-output-escaping="yes" />
	</div>
</xsl:template>

<!-- лисный кабинет -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '12']">
    <div umi:element-id="{@pageId}" umi:field-name="content">
        <div id="personal">

            <xsl:choose>
                <xsl:when test="user/@status = 'auth'">
                    
                    <h1 umi:element-id="{@pageId}" umi:field-name="h1">
                        <xsl:value-of select=".//property[@name='h1']/value" />
                    </h1>
                    <xsl:value-of select=".//property[@name='content']/value" disable-output-escaping="yes" />

                    <ul class='personal_menu change'>
                        <li class='reg'><a href="/personal/change_personal/">Изменить личные данные</a></li>
                        <li class='password'><a href="/personal/change_password/">Изменить пароль</a></li>
                        <li class='address'><a href="/personal/change_address/">Адресные данные</a></li>
                    </ul>

                    <ul class='personal_menu'>
                        <li class='orders'><a href="/personal/orders/">Мои заказы</a></li>
                        <li class='discounts'><a href="/personal/points/">Мои скидки</a></li>
                        <li class='cart'><a href="/cart_content/">Моя корзина</a></li>
                    </ul>
                    
                    <a href="javascript:sendRequestForAccDeletion();" class="personal_delete">
                        Запрос на удаление акаунта
                        <div class="preloader"></div>
                    </a>
                    
                </xsl:when>
                <xsl:otherwise>
                    
                    <h1>Нет доступа</h1>
                    <p>Страница доступна только для авторизованных пользователей.</p>
            
                </xsl:otherwise>
            </xsl:choose>

        </div>
    </div>
</xsl:template>

<!-- изменение пароля -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '294']">
    <div umi:element-id="{@pageId}" umi:field-name="content">
        <div id="personal">

            <xsl:choose>
                <xsl:when test="user/@status = 'auth'">
                    
                    <h1 umi:element-id="{@pageId}" umi:field-name="h1">
                        <xsl:value-of select=".//property[@name='h1']/value" />
                    </h1>

                    <form method="post" action="/users/settings_do/" id='passform' class='form_common' >
                        <ul>
                            <li>
                                <label>Старый пароль:</label>
                                <input class='text' type="password" name="oldpass" />  
                            </li>
                            <li>
                                <label>Новый пароль:</label>
                                <input class='text' type="password" name="password" />  
                            </li>
                            <li>
                                <label>Новый пароль еще раз:</label>
                                <input class='text' type="password" name="password_confirm" />
                            </li>
                            <li>
                                <span class='button' onclick='changePass(document.getElementById("passform"));return false;'>Сохранить изменения</span>
                                <spam class="report">
                                    <xsl:value-of select="document('udata://system/listErrorMessages')//item" />
                                </spam>
                            </li>
                        </ul>   
                    </form>
                    
                </xsl:when>
                <xsl:otherwise>
                    
                    <h1>Нет доступа</h1>
                    <p>Страница доступна только для авторизованных пользователей.</p>
            
                </xsl:otherwise>
            </xsl:choose>

        </div>
    </div>
</xsl:template>

<!-- изменение личных данных -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '295']">
    <div umi:element-id="{@pageId}" umi:field-name="content">
        <div id="personal">

            <xsl:choose>
                <xsl:when test="user/@status = 'auth'">
                    
                    <h1 umi:element-id="{@pageId}" umi:field-name="h1">
                        <xsl:value-of select=".//property[@name='h1']/value" />
                    </h1>
                    <xsl:value-of select=".//property[@name='content']/value" disable-output-escaping="yes" />

                    <xsl:variable select="./user/@id" name="user_id" />
                    <xsl:variable select="document(concat('uobject://', $user_id))/udata" name="user_data" />
                    <xsl:variable select="$user_data//property[@name = 'fname']/value" name="user_fname" />
                    <xsl:variable select="$user_data//property[@name = 'login']/value" name="login" />
                    <xsl:variable select="$user_data//property[@name = 'lname']/value" name="user_lname" />
                    <xsl:variable select="$user_data//property[@name = 'father_name']/value" name="user_father_name" />
                    <xsl:variable select="$user_data//property[@name = 'e-mail']/value" name="email" />

                    <form method="post" action="/users/settings_do/" id='enterform' class='form_common' >
                        <ul>
                          <!--   <li>
                                <label>Фамилия</label>
                                <input class='text' type="text" name="data[{$user_id}][lname]" value="{$user_lname}" />  
                            </li>
                            <li>
                                <label>Имя</label>
                                <input class='text' type="text" name="data[{$user_id}][fname]" value="{$user_fname}" />
                            </li> 
                            <li>
                                <label>Отчество</label>
                                <input class='text' type="text" name="data[{$user_id}][father_name]" value="{$user_father_name}" />
                            </li> -->
                             <li>
                                <label>Пароль:</label>
                                <input class='text' type="password" name="oldpass" />  
                            </li>
                            <li>
                                <label>Логин</label>
                                <input class='text' type="text" name="data[{$user_id}][login]" value="{$login}" />
                            </li>
                            <li>
                                <label>Email</label>
                                <input class='text' type="text" name="email" value="{$email}" />
                            </li>
                            <li>
                                <span class='button' onclick="changePass(document.getElementById('enterform'));return false;">Сохранить изменения</span>
                                <spam class="report">
                                    <xsl:value-of select="document('udata://system/listErrorMessages')//item" />
                                </spam>
                            </li>
                        </ul>   
                    </form>
                    
                </xsl:when>
                <xsl:otherwise>
                    
                    <h1>Нет доступа</h1>
                    <p>Страница доступна только для авторизованных пользователей.</p>
            
                </xsl:otherwise>
            </xsl:choose>

        </div>
    </div>
</xsl:template>

<!-- адресные данные -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '296']">
    <div umi:element-id="{@pageId}" umi:field-name="content">
        <div id="personal">

            <xsl:choose>
                <xsl:when test="user/@status = 'auth'">
                    
                    <h1 umi:element-id="{@pageId}" umi:field-name="h1">
                        <xsl:value-of select=".//property[@name='h1']/value" />
                    </h1>
                    <xsl:value-of select=".//property[@name='content']/value" disable-output-escaping="yes" />

                    <xsl:variable select="./user/@id" name="user_id" />
                    <xsl:variable select="document(concat('uobject://', $user_id))/udata" name="user_data" />
                    <div class="form_common" id='order_forming'>
                        <form method="post" action="/users/settings_do/" id='user_adres_form'>
                        <ul>
                            <li class="report_top"></li>
                            <xsl:apply-templates select="document(concat('udata://data/getEditForm/',$user_id,'/(adres_dostavki)'))//field" mode="adres_edit_form" >
                                <xsl:with-param name="user_data" value="$user_data"/>
                            </xsl:apply-templates>

                        </ul>
                        </form>
                    </div>
                    <div class="order_margin_both">
                        <!-- submitForming(); -->
                        <spam class="report">
                                    <xsl:value-of select="document('udata://system/listErrorMessages')//item" />
                                </spam>
                    <a href="javascript:;" onclick="submitFormingUser()" id="order_button" style='color:white;text-decoration:none;'>Сохранить</a>
                    </div>
                    <script type="text/javascript">                
                        myRestoreFormData(document.getElementById('user_adres_form'));
                        function submitFormingUser(){ 
                            var form = document.getElementById('user_adres_form');
                            mySaveFormData(form);                    // проверяет все обязательные поля
                            $("#user_adres_form input").each(function() {
                                      checkTextfieldValueUser(this); 
                            });
                            // если есть поля, помеченные как mistaken                    // выводим сообщение, что есть поля, заполненные с ошибками                    
                            var found_mistakes = false;
                            $("#user_adres_form input.mistaken").each(function() {
                                     found_mistakes = true;
                            });
                            if ( found_mistakes ) { 
                                  $('.report_top').html("Есть поля, заполненные с ошибками");
                                  return;
                            }
                             form.submit(); 
                            }
                      </script>
                    <!-- <xsl:choose>
                        <xsl:when test="$user_data//property[@name = 'delivery_addresses']">
                            <xsl:apply-templates select="$user_data//property[@name = 'delivery_addresses']//item" mode="delivery_addresses_delete" />
                        </xsl:when>
                        <xsl:otherwise>
                            <p>Нет сохраненных адресов. Адреса добавляются при оформлении заказа.</p>
                        </xsl:otherwise>
                    </xsl:choose> -->

                </xsl:when>
                <xsl:otherwise>
                    
                    <h1>Нет доступа</h1>
                    <p>Страница доступна только для авторизованных пользователей.</p>
            
                </xsl:otherwise>
            </xsl:choose>

        </div>
    </div>
</xsl:template>

<xsl:template match="field" mode="adres_edit_form">
    <li>
        <label>
            <xsl:value-of select="@title" /> 
        </label>
        <input name='{@input_name}' class='text' type="text" required="{@required}" value='{.}'  it='{@name}'/>
        <!-- <xsl:variable select="concat('$user_data//property[@name='',@input_name,'']')/value" name="obj_value" />
        <xsl:choose>
              <xsl:when test="$obj_value">
                <input name='{@input_name}' class='text' type="text" required="{@required}" value='{$obj_value}' its='{@name}' />
              </xsl:when>
              <xsl:otherwise>
                <input name='{@input_name}' class='text' type="text" required="{@required}" its='{@name}' />
              </xsl:otherwise>
         </xsl:choose> -->
    </li>
</xsl:template>


<xsl:template match="field[@type = 'relation']" mode="adres_edit_form">
    <xsl:param name='user_data' />
    <li>
        <label>
            <xsl:value-of select="@title" />
        </label>
        <select name='{@input_name}'>
            <xsl:if test="@name = 'region'">
                <option value=''>выберите регион</option>
            </xsl:if>    
            <xsl:apply-templates select=".//item" mode="adres_edit_form_countries" />
        </select>
    </li>
</xsl:template>

<xsl:template match="item" mode="adres_edit_form_countries" >
    <xsl:choose>
          <xsl:when test="@selected = 'selected'">
                <option selected='selected' value="{@id}">
                    <xsl:value-of select="." />
                </option>        
          </xsl:when>
          <xsl:otherwise>
                <option value="{@id}">
                    <xsl:value-of select="." />
                </option>        
          </xsl:otherwise>
     </xsl:choose>
    
</xsl:template>

<xsl:template match="item" mode="delivery_addresses_delete" >
    <xsl:variable select="document(concat('uobject://', @id))" name="address" />
    <div class='delivery_delete'>
        <a href="/emarket/delivery_del/{@id}/" title='Удалить'></a>
        <span>
            <xsl:value-of select="$address//property[@name = 'country']/value/item/@name" />, 
            <xsl:value-of select="$address//property[@name = 'region']/value/item/@name" />, 
            <xsl:value-of select="$address//property[@name = 'city']/value" />, 
            <xsl:value-of select="$address//property[@name = 'index']/value" />, ул. 
            <xsl:value-of select="$address//property[@name = 'street']/value" /> 
            <xsl:value-of select="$address//property[@name = 'house']/value" />,
            <xsl:value-of select="$address//property[@name = 'flat']/value" />
        </span>
    </div>
</xsl:template>

<!-- мои скидки  -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '13']">
    <div umi:element-id="{@pageId}" umi:field-name="content">

        <xsl:choose>
            <xsl:when test="user/@status = 'auth'">
                
                <h1 umi:element-id="{@pageId}" umi:field-name="h1">
                    <xsl:value-of select=".//property[@name='h1']/value" />
                </h1>


                <xsl:variable select="document(concat('udata://custom/getPersonalDiscount/', ./user/@id))/udata" name="personal_discount" />
                <xsl:if test="$personal_discount > 0">
                    <p class="own_discount">Ваша персональная скидка <xsl:value-of select="$personal_discount" />%</p>
                </xsl:if>  
                  
                <xsl:value-of select=".//property[@name='content']/value" disable-output-escaping="yes" />
                
            </xsl:when>
            <xsl:otherwise>
                <h1><xsl:value-of select="document('upage://2')//property[@name='skidki_net_h1']/value" /></h1>
                <xsl:value-of select="document('upage://2')//property[@name='skidki_not']/value" disable-output-escaping="yes" />
            </xsl:otherwise>
        </xsl:choose>

    </div>
</xsl:template>

<!-- мои заказы -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '14']">
    <div umi:element-id="{@pageId}" umi:field-name="content">
        <xsl:choose>
            <xsl:when test="user/@status = 'auth'">
                
                <h1 umi:element-id="{@pageId}" umi:field-name="h1">
                    <xsl:value-of select=".//property[@name='h1']/value" />
                </h1>
                <xsl:value-of select=".//property[@name='content']/value" disable-output-escaping="yes" />
                <xsl:apply-templates select="document('udata://emarket/ordersListMy')/udata" />

            </xsl:when>
            <xsl:otherwise>
                
                <h1><xsl:value-of select="document('upage://2')//property[@name='zakazi_net_h1']/value" /></h1>
                <xsl:value-of select="document('upage://2')//property[@name='zakazi_not']/value" disable-output-escaping="yes" />
        
            </xsl:otherwise>
        </xsl:choose>
    </div>
</xsl:template>


<xsl:template match="udata[@module = 'emarket'][@method = 'ordersListMy']">
    <xsl:choose>
        <xsl:when test="//item">

            
            <xsl:apply-templates select="//item" mode="my_orders_item" />

        </xsl:when>
        <xsl:otherwise>

            <p>Вы еще ничего не заказывали.</p>

        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="item" mode="my_orders_item" >
    <xsl:apply-templates select="document(concat('udata://emarket/orderMy/', @id))/udata" />
</xsl:template>

<xsl:template match="udata[@module = 'emarket'][@method = 'orderMy']">
    <div class="page_nav no_margin"></div> 
    <table class="my_order_table">

        <tr class='header'>
            <td>№ заказа <span></span></td>
            <td>Дата заказа <span></span></td>
            <td>Статус заказа <span></span></td>
            <td>Почтовый идентификатор</td>
        </tr>

        <tr class='header_inner'>
            <td class='border_r white_box' >
                <div class="white top"></div>
                <div class="white bottom"></div>
                <xsl:value-of select="./number" />
            </td>
            <td class='border_r'>
                <xsl:value-of select="document(concat('udata://custom/makeReadableDate/(', ./order_date, ')/'))/udata" />
            </td>
            <td class='border_r'>
                <xsl:value-of select="./status/@name" />
            </td>
            <td>
                <xsl:value-of select="./post_id" />
            </td>
        </tr>

        <xsl:apply-templates select=".//items/item" mode="my_order_item" />

        
        <tr class='total total_min'>
            <td colspan='4' style='border-top: 1px solid #E3E3E3;'>
                Общая сумма: <span><xsl:value-of select=".//summary//original" /> руб.</span>
            </td>
        </tr>
        <xsl:if test=".//discount">
            <tr class='total total_min'>
                <td colspan='4'>
                    C учетом скидки <xsl:value-of select=".//discount/description" />: <span><xsl:value-of select="round(.//summary//actual)" /> руб.</span>
                </td>
            </tr>
        </xsl:if>
        

        <xsl:variable select="document(concat('uobject://', @id))/udata//property[@name = 'payment_id']/value/item/@id" name="obj_id_" />
        <xsl:if test="$obj_id_ = '882'">
                <tr class='total total_min'>
                    <td colspan='4'>
                        Стоимость почтовой страховки наложенного платежа +10%: <span><xsl:value-of select="round(round(.//summary//actual) * 0.1)" /> руб.</span>
                    </td>
                </tr>
        </xsl:if>

        <tr class='total total_min'>
            <td colspan='4'>
                Стоимость доставки: <span>250 руб.</span>
            </td>
        </tr>

        <xsl:choose>
              <xsl:when test="$obj_id_ = '882'">
                     <tr class='total'>
                        <td colspan='4' style='border:none'>
                            Итого: <span><xsl:value-of select="round(round(.//summary//actual) * 0.1) + round(.//summary//actual) + 250" /> руб.</span>
                        </td>
                     </tr>
              </xsl:when>
              <xsl:otherwise>
                    <tr class='total'>
                        <td colspan='4' style='border:none'>
                            Итого: <span><xsl:value-of select="round(.//summary//actual) + 250" /> руб.</span>
                        </td>
                     </tr>
              </xsl:otherwise>
         </xsl:choose>

    </table>
</xsl:template>

<xsl:template match="item" mode="my_order_item" >
    <tr class='item'>
        <td class='white_box'>
            <div class="white top"></div>
        </td>
        <td class='border_r'>
            <xsl:variable select="document(concat('udata://emarket/getInactiveIdUser/',@id ))/udata" name="inactivePageId" />
            <xsl:variable select="document(concat('upage://', $inactivePageId))/udata" name='pageIdIn' />
            <xsl:value-of select="document(concat('upage://', $pageIdIn/page/@parentId))/udata/page/name" />
        </td>
        <td class='border_r'><xsl:value-of select="@name" /></td>
        <td><xsl:value-of select="total-price/actual" /> руб.</td>
    </tr>    
</xsl:template>

<xsl:template match="item[position() = last()]" mode="my_order_item" >
    <tr class='item last'>
        <td class='white_box'>
            <div class="white top"></div>
        </td>
        <td class='border_r'>
             <xsl:variable select="document(concat('udata://emarket/getInactiveIdUser/',@id ))/udata" name="inactivePageId" />
             <xsl:variable select="document(concat('upage://', $inactivePageId))/udata" name='pageIdIn' />
             <xsl:value-of select="document(concat('upage://', $pageIdIn/page/@parentId))/udata/page/name" />
         </td>
        <td class='border_r'><xsl:value-of select="@name" /></td>
        <td><xsl:value-of select="total-price/actual" /> руб.</td>
    </tr>    
</xsl:template>

<!-- 404 page -->
<xsl:template match="result[@module = 'content'][@method = 'notfound']" >
    <h1>Нет такой страницы</h1>
    <p>Страница была удалена, перемещена или вовсе не существовала.</p>
</xsl:template>

<!-- результаты поиска -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '30']" >
    <h1 umi:element-id="{@pageId}" umi:field-name="h1">
        <xsl:value-of select=".//property[@name='h1']/value" />
    </h1>
    <xsl:apply-templates select="document(concat('udata://custom/myAdvancedSearch/', $per_page))/udata" >
        <xsl:with-param name="no_content_above" select="'no_content_above'" />
    </xsl:apply-templates>
</xsl:template>

<!-- результаты полнотекстового поиска -->
<xsl:template match="result[@module = 'search'][@method = 'search_do']">
    <xsl:apply-templates select="document('udata://search/search_do')/udata" mode="search" />
</xsl:template>

<xsl:template match="udata" mode='search'>
    <xsl:choose>
          <xsl:when test="./total = 0">
                <h1>Результаты поиска (0)</h1>
                <p>Ничего не найдено.</p>
          </xsl:when>
          <xsl:otherwise>
            <div id='search_results'>
                <h1>
                    <xsl:text>Результаты поиска (</xsl:text>
                    <xsl:value-of select="./total" />
                    <xsl:text>)</xsl:text>
                </h1>
                <xsl:apply-templates select=".//item" mode="search" />
            </div>
            <xsl:apply-templates select="document(concat('udata://system/numpages/', ./total, '/', ./per_page, '/0/(p)/6'))/udata" />
          </xsl:otherwise>
    </xsl:choose>  
</xsl:template>

<xsl:template match="item" mode='search'>
    <a href="{@link}" umi:element-id="{@id}" umi:field-name="name">
        <xsl:value-of select="@name" />
    </a>
    <div>
        <xsl:value-of select="." disable-output-escaping="yes" />
    </div>
</xsl:template>

<!-- моя корзина -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '268']">
    <div id='order_steps'>
        <ul>
            <li class='cart active'><a>Моя корзина</a></li>
            <li class='forming'><a>Оформление заказа</a></li>
            <li class='payment'><a>Способ оплаты</a></li>
        </ul>
        <div class="line"></div>
    </div>
    <div class="clear"></div>
    <xsl:apply-templates select="document('udata://emarket/cart/')/udata" />
</xsl:template>

<xsl:template match="udata[@module = 'emarket'][@method = 'cart']">
    <xsl:choose>
        <xsl:when test=".//summary/amount != 0">

            <xsl:variable select="document('upage://268')//page" name="this_page" />

            <p class='tip' umi:element-id="{$this_page/@id}" umi:field-name="text_above">
                <xsl:value-of select="$this_page//property[@name = 'text_above']/value" />
            </p>

            <div id='order_table'>
                <input id='cart_count' type="hidden" value='{.//summary/amount}' />
                <a class='truncate' href="javascript:;" onclick='truncateCart();'>очистить корзину</a>
                <span id='cart_thinking'></span>
                <ul class='header'>
                    <li class='name_col'>Название<span></span></li>
                    <li class='count_col'>Количество<span></span></li>
                    <li class='price_col'>Цена, руб.<span></span></li>
                    <li>Сумма, руб.</li>
                </ul>

                <xsl:apply-templates select=".//page" mode="cart_sorts" />

                <div class='total_no_discount'>
                    <div class='fr'>
                        <xsl:text>Общая сумма:</xsl:text>
                        <span class='sum'>
                            <!--
                                если без скидки, то actual 
                                если со скидкой, то original
                            -->
                            <xsl:choose>
                                <xsl:when test="./summary/price/original">
                                    <xsl:number value='./summary/price/original' grouping-separator=" " grouping-size="3" />
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:number value='./summary/price/actual' grouping-separator=" " grouping-size="3" />
                                </xsl:otherwise>
                            </xsl:choose>
                        </span>
                        <span class='cy'>руб.</span>
                    </div>
                    <!-- 
                    <div class='fl'>
                        <span class="text">Купон на скидку:</span> 
                        <input type="text" value='№ купона' clean='clean' /> 
                        <a class='text' href="javascript:;" onclick='useCoupon();'>Использовать</a>
                    </div> 
                    -->
                </div>
                <xsl:choose>
                    <xsl:when test="./discount">
                    
                        <div class='total'>
                            <xsl:text>Итоговая сумма с учетом </xsl:text>
                            <span class='size'> 
                                <xsl:value-of select="./discount/@name" />
                            </span>
                            <xsl:text> скидки</xsl:text>
                            <span class='sum'>
                                <xsl:number value='//summary/price/actual' grouping-separator=" " grouping-size="3" />
                            </span>
                            <xsl:text>руб.</xsl:text> 
                        </div> 
                        
                    </xsl:when>
                    <xsl:otherwise>
                        
                        <div class='total' style='display: none;'>
                            Итоговая сумма с учетом <span class='size'></span> скидки <span class='sum'></span> руб.
                        </div> 
                
                    </xsl:otherwise>
                </xsl:choose>
            </div>

            <a href="/order_forming/" id='order_button'>Оформить заказ</a>

            <p class='tip' umi:element-id="{$this_page/@id}" umi:field-name="text_near_button">
                <xsl:value-of select="$this_page//property[@name = 'text_near_button']/value" />
            </p>

            <div class="clear"></div>

            <ul id='order_info'>
               <li class='shipping_cost'>
                   <span>Стоимость доставки</span>
                   <div umi:element-id="{$this_page/@id}" umi:field-name="text_shipping_cost">
                        <xsl:value-of select="$this_page//property[@name = 'text_shipping_cost']/value" />
                   </div>
               </li>
               <li class='discounts'>
                   <span>Скидки</span>
                   <div umi:element-id="{$this_page/@id}" umi:field-name="text_discounts">
                        <xsl:value-of select="$this_page//property[@name = 'text_discounts']/value" />
                   </div>
               </li>
               <li class='shipping last'>
                   <span>Доставка</span>
                   <div umi:element-id="{$this_page/@id}" umi:field-name="text_shipping">
                        <xsl:value-of select="$this_page//property[@name = 'text_shipping']/value" />
                   </div>
               </li>
            </ul>
            
        </xsl:when>
        <xsl:otherwise>
            <p>Корзина пуста.</p>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="page" mode='cart_sorts'>
    <xsl:variable select="document(concat('upage://', @parentId))/udata" name="sort" />
    <ul class="good" sort_id='{@parentId}'>
        <li class='name_col'>
            <div>
                <xsl:value-of select="$sort//name" />
            </div>
            <a href="{$sort//@link}">
                <img src="{$sort//property[@name = 'main_image']/value}" width='125' alt="{$sort//name}" />
            </a>
        </li>
        <li class='count_col table'>
            <table>
                <colgroup>
                    <col class='counter' />
                    <col class="price" />
                    <col class="total_sum" />
                </colgroup>
            <xsl:apply-templates select="document('udata://emarket/cart/')//items/item" mode='cart_package' >
                <xsl:with-param name='parent' select='@parentId' />
            </xsl:apply-templates>  
            </table>
        </li>
        <li class='price_col'></li>
        <li class='last'></li>
    </ul>
</xsl:template>

<xsl:template match="item" mode='cart_package'>
    <xsl:param name="parent" />
    <xsl:variable select="document(concat('upage://', ./page/@id))//property[@name = 'common_quantity']/value" name="quantity" />
    <xsl:if test="./page/@parentId = $parent ">
        <tr id='cart_tr_{@id}' price='{./price/actual}'>
            <td>
                <span class="more_less">
                    <input id='cart_count_{./page/@id}' type="text" value="{./amount}" limit="{$quantity}" />
                    <span class='more' onclick='changePackageCount({./page/@id}, 1);'></span>
                    <span class='less' onclick='changePackageCount({./page/@id}, -1);'></span>
                    <span class='disable'></span>
                </span>
                <span class='fl'>
                    <xsl:value-of select="./@name" />
                </span>
            </td>
            <td>
                <xsl:number value='./price/actual' grouping-separator=" " grouping-size="3" />
                <xsl:text> руб.</xsl:text>
            </td>
            <td class='marked'>
                <span class='trash' title='Удалить' onclick='deletePackageFromCart({@id})'></span>
                <span class='price'><xsl:number value='./total-price/actual' grouping-separator=" " grouping-size="3" /></span>
                <xsl:text> руб.</xsl:text>
            </td>
        </tr>
    </xsl:if>
</xsl:template>

<!-- оформление заказа -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '269']">
    
    <xsl:variable select="document('udata://emarket/fast_purchasing/')/udata" name="fast_purchasing" />
    <xsl:variable select="document('udata://emarket/cart/')/udata/summary/amount" name="cart_amount" />

    <div id='order_steps'>
        <ul>
            <li class='cart'><a>Моя корзина</a></li>
            <li class='forming active'><a>Оформление заказа</a></li>
            <li class='payment'><a>Способ оплаты</a></li>
        </ul>
        <div class="line"></div>
    </div>
    <div class="clear"></div>

    <xsl:choose>
        <xsl:when test="$cart_amount != 0 ">
        
            <p class="tip order_margin_left" umi:element-id="{@pageId}" umi:field-name="text_above">
                <xsl:value-of select=".//property[@name = 'text_above']/value" />
            </p>

            <form action="/emarket/save_forming_stage/" method='post' id='forming_form'>

                <xsl:apply-templates select="$fast_purchasing//delivery_choose/items/item" mode="delivery_items" />

                <p class="tip order_margin_left" umi:element-id="{@pageId}" umi:field-name="text_about_posting">
                    <xsl:value-of select=".//property[@name = 'text_about_posting']/value" />
                </p>

                <div id="order_forming" class='form_common'>
                
                <div class="title">Адрес получателя:</div>
                    
                    <!-- <xsl:choose>
                        <xsl:when test="./user/@status = 'auth'">
                            <xsl:apply-templates select="$fast_purchasing/delivery//item" mode="delivery_addresses_select" />
                            <div class='delivery_new'>
                            <xsl:choose>
                                <xsl:when test="$fast_purchasing/delivery//item[@active = 'active']">
                                    <input type="radio" name="delivery-address" value="new" id='new_address' />
                                </xsl:when>
                                <xsl:otherwise>
                                    <input type="radio" name="delivery-address" value="new" id='new_address' checked='checked' />
                                </xsl:otherwise>
                            </xsl:choose>
                                <label for='new_address'>
                                    Новый адрес доставки
                                </label>
                            </div>
                            
                        </xsl:when>
                        <xsl:otherwise>
                        </xsl:otherwise>
                    </xsl:choose> -->
                            <input type="hidden" name="delivery-address" value="new" />

                    <ul>

                        <li class='report_top'>
                            <xsl:value-of select="document('udata://system/listErrorMessages')//item" />
                        </li>
                         
                        <xsl:choose>
                              <xsl:when test="./user/@status = 'auth'">
                                <xsl:apply-templates select="document(concat('udata://data/getCreateForm/', $fast_purchasing/delivery/@type_id))//field" mode="order_forming" >
                                    <xsl:with-param name="auth_bool" select="'true'" />
                                    <xsl:with-param name="auth_id" select="./user/@id" />
                                 </xsl:apply-templates>
                              </xsl:when>
                              <xsl:otherwise>
                                <xsl:apply-templates select="document(concat('udata://data/getCreateForm/', $fast_purchasing/delivery/@type_id))//field" mode="order_forming" >
                                     <xsl:with-param name="auth_bool" select="'false'" />
                                     <xsl:with-param name="auth_id" />
                                </xsl:apply-templates>
                              </xsl:otherwise>
                         </xsl:choose>
                        

                   </ul>
                </div>

                <div class="order_margin_both">
                    <a href="javascript:;" onclick="submitForming();" id='order_button'>Вперед к оплате</a>
                    <p class="tip" umi:element-id="{@pageId}" umi:field-name="text_near_button" >
                        <xsl:value-of select=".//property[@name = 'text_near_button']/value" />
                    </p>
                    <a id='order_back' href="/cart_content/">Назад в корзину</a>
                </div>
            </form>
            <script type="text/javascript">
                myRestoreFormData(document.getElementById('forming_form'));
                function submitForming(){

                    var form = document.getElementById('forming_form');
                    mySaveFormData(form);

                    // проверяет все обязательные поля
                    $("input[required='required'], textarea[required='required']").each(function() {
                        checkTextfieldValue(this);
                    });

                    // если есть поля, помеченные как mistaken
                    // выводим сообщение, что есть поля, заполненные с ошибками
                    var found_mistakes = false;
                    $("#order_forming input.mistaken").each(function() {
                        found_mistakes = true;
                    });

                    if ( found_mistakes ) {
                        $('.report_top').html("Есть поля, заполненные с ошибками");
                        return; 
                    }

                    form.submit(); 

                }
            </script>
            
        </xsl:when>
        <xsl:otherwise>
            <p>Корзина пуста.</p>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="item" mode="delivery_addresses_select" >
    <xsl:variable select="document(concat('uobject://', @id))" name="address" />
    <div class='delivery_item'>
        <xsl:choose>
            <xsl:when test="@active = 'active'">
                <input type="radio" name='delivery-address' value='{@id}' id='address_{@id}' checked='checked' />
            </xsl:when>
            <xsl:otherwise>
                <input type="radio" name='delivery-address' value='{@id}' id='address_{@id}' />
            </xsl:otherwise>
        </xsl:choose>
        <label for='address_{@id}' >
            <xsl:text> </xsl:text>
            <xsl:value-of select="$address//property[@name = 'country']/value/item/@name" />, 
            <xsl:value-of select="$address//property[@name = 'region']/value/item/@name" />, 
            <xsl:value-of select="$address//property[@name = 'city']/value" />, 
            <xsl:value-of select="$address//property[@name = 'index']/value" />, ул. 
            <xsl:value-of select="$address//property[@name = 'street']/value" /> 
            <xsl:value-of select="$address//property[@name = 'house']/value" />,
            <xsl:value-of select="$address//property[@name = 'flat']/value" />
        </label>
    </div>
</xsl:template>

<xsl:template match="item" mode="delivery_items" >
    <xsl:variable select="document(concat('uobject://', @id))/udata" name="details" />
    <div class="post_method">
        <a target='_blank' href="{$details//property[@name = 'about_page']/value}">подробнее</a>
        <div class="nice_checkbox">
            <xsl:choose>
                <xsl:when test="@active = 'active'">
                    <input type='radio' name='delivery-id' value='{@id}' checked='checked' />
                </xsl:when>
                <xsl:otherwise>
                    <input type='radio' name='delivery-id' value='{@id}' />
                </xsl:otherwise>
            </xsl:choose>
            <span type='excepting' parent='content'></span>
        </div>
        <xsl:value-of select="@name" />
    </div>
    <p class="tip dark order_margin_left" umi:object-id="{@id}" umi:field-name="about_method" >
        <xsl:value-of select="$details//property[@name = 'about_method']/value" />
    </p>
</xsl:template>

<xsl:template match="field" mode="order_forming">
    <xsl:param name="auth_bool"/>
    <xsl:param name="auth_id"/>
    <li>
        <label>
            <xsl:value-of select="@title" />
            <xsl:if test="@required">
                <xsl:text> *</xsl:text>
            </xsl:if>    
        </label>
        <xsl:choose>
              <xsl:when test="$auth_bool = 'true'">
                 <xsl:choose>
                       <xsl:when test="@name='email'">                 
                        <xsl:variable select="document(concat('uobject://',$auth_id))//property[@name='e-mail']/value" name="mail" />
                         <input name='{@input_name}' class='text' type="text" value='{$mail}' required="{@required}" its='{@name}' /> 
                       </xsl:when>
                       <xsl:otherwise>
                            <xsl:variable select="document(concat('uobject://',$auth_id))//group[@name='adres_dostavki']" name="id_group" />
                            <xsl:variable select="@name" name="pole" />
                            <xsl:variable select="concat('$id_group/property[@name=&quot;',$pole,'&quot;]')" name="str" />
                            <xsl:choose>
                                  <xsl:when test="dyn:evaluate($str)">
                                     <input name='{@input_name}' class='text' type="text" value='{dyn:evaluate($str)/value}' required="{@required}" its='{@name}' /> 
                                  </xsl:when>
                                  <xsl:otherwise>
                                   <input name='{@input_name}' class='text' type="text" required="{@required}" its='{@name}' /> 
                                  </xsl:otherwise>
                             </xsl:choose>     
                       </xsl:otherwise>
                  </xsl:choose>
                    
              </xsl:when>
              <xsl:otherwise>
                    <input name='{@input_name}' class='text' type="text" required="{@required}" its='{@name}' />        
              </xsl:otherwise>
         </xsl:choose>
    </li>
</xsl:template>

<xsl:template match="field[@type = 'text']" mode="order_forming">
    <li>
        <label>
            <xsl:value-of select="@title" />
            <xsl:if test="@required">
                <xsl:text> *</xsl:text>
            </xsl:if>    
        </label>

        <textarea name='{@input_name}' required="{@required}" its='{@name}' ></textarea>
    </li>
</xsl:template>

<xsl:template match="field[@type = 'relation']" mode="order_forming">
    <xsl:param name="auth_bool"/>
    <xsl:param name="auth_id"/>
    <li>
        <label>
            <xsl:value-of select="@title" />
            <xsl:if test="@required">
                <xsl:text> *</xsl:text>
            </xsl:if>    
        </label>
        <select name='{@input_name}'>
            <xsl:choose>
              <xsl:when test="$auth_bool = 'true'">
                    <xsl:variable select="document(concat('uobject://',$auth_id))//group[@name='adres_dostavki']" name="id_group" />
                    <xsl:variable select="@name" name="pole" />
                    <xsl:variable select="concat('$id_group/property[@name=&quot;',$pole,'&quot;]')" name="str" />
                    <xsl:choose>
                          <xsl:when test="dyn:evaluate($str)">
                             <!-- <input name='{@input_name}' class='text' type="text" value='{dyn:evaluate($str)/value}' required="{@required}" its='{@name}' />  -->
                             
                                      <xsl:apply-templates select=".//item" mode="countries_with_param" >
                                        <xsl:with-param name='option_param' select='dyn:evaluate($str)' />
                                      </xsl:apply-templates>
                                
                                
                          </xsl:when>
                          <xsl:otherwise>
                        
                                <xsl:if test="@name = 'region'">
                                      <option value=''>выберите регион</option>
                                </xsl:if>
                                <xsl:apply-templates select=".//item" mode="countries" />
                          </xsl:otherwise>
                     </xsl:choose>
              </xsl:when>
              <xsl:otherwise>
                    <xsl:if test="@name = 'region'">
                        <option value=''>выберите регион</option>
                    </xsl:if>
                    <xsl:apply-templates select=".//item" mode="countries" />
              </xsl:otherwise>
         </xsl:choose>
           
        </select>
    </li>
</xsl:template>

<xsl:template match="item" mode="countries" >
    <option value="{@id}">
        <xsl:value-of select="." />
    </option>
</xsl:template>

<xsl:template match="item" mode="countries_with_param" >
    <xsl:param name="option_param" />
    
    <xsl:choose>
          <xsl:when test="$option_param/value/item/@id = @id">
            <option value="{@id}" selected='selected'>
                <xsl:value-of select="." />
            </option>        
          </xsl:when>
          <xsl:otherwise>
            <option value="{@id}">
                <xsl:value-of select="." />
            </option>        
          </xsl:otherwise>
     </xsl:choose>
    
</xsl:template>


<!-- страница выбора способа оплаты -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '272']">

    <xsl:variable select="document('udata://emarket/cart/')/udata/summary/amount" name="cart_amount" />

    <div id='order_steps'>
        <ul>
            <li class='cart'><a>Моя корзина</a></li>
            <li class='forming'><a>Оформление заказа</a></li>
            <li class='payment active'><a>Способ оплаты</a></li>
        </ul>
        <div class="line"></div>
    </div>
    <div class="clear"></div>


    <xsl:choose>
        <xsl:when test="$cart_amount != 0">
        
            <p class="tip">Для завершения оформления заказа осталось выбрать удобный Вам способ оплаты. </p>

            <div id="order_payment">
                <form action="/emarket/save_payment_stage/" id='payment_form' method='post' >
                    <ul id='pay_methods'>

                        <xsl:apply-templates select="document('udata://emarket/fast_purchasing/')//payment//item" mode="payment_item" />

                    </ul>
                </form>
                
                <xsl:apply-templates select="document('udata://emarket/cart/')//summary/price/actual" mode="cart_state_end" />

            </div>

            <div class="order_margin_both">
                <a href="javascript:;" onclick="document.getElementById('payment_form').submit();" id='order_button'>Подтвердить</a>
                <a id='order_back' href="/order_forming/">Назад к оформлению заказа</a>
            </div>
            
        </xsl:when>
        <xsl:otherwise>
            <p>Корзина пуста.</p>
        </xsl:otherwise>
    </xsl:choose>

</xsl:template>

<xsl:template match="actual" mode='cart_state_end' >

    <xsl:variable name="total" >
        <xsl:number value='. + 250' grouping-separator=" " grouping-size="3" />
    </xsl:variable> 

    <xsl:variable name="total_insurance" >
        <xsl:number value='. + .*0.1 + 250' grouping-separator=" " grouping-size="3" />
    </xsl:variable> 

    <div class="cost_of_smth">
        <span class="fr">
            <xsl:number value='.' grouping-separator=" " grouping-size="3" />
            <xsl:text> руб.</xsl:text>
        </span>Стоимость выбранного вами товара:
    </div>
    <div class="cost_of_smth"><span class="fr">250 руб.</span>Стоимость доставки:</div>
    <div class="cost_of_smth" id='payment_insurance'>
        <span class="fr">
            <xsl:number value='.*0.1' grouping-separator=" " grouping-size="3" />
            <xsl:text> руб.</xsl:text>
        </span>Стоимость страховки наложенного платежа +10%:
    </div>
    <div class="total">
        <span class="fr">
            <span id='payment_total'>
                <xsl:value-of select="$total_insurance" />
            </span>
            <xsl:text> руб.</xsl:text>
        </span>Итого:
    </div>

    <input type="hidden" value='{$total}' id='sum_total' />
    <input type="hidden" value='{$total_insurance}' id='sum_total_insurance' />

    <div id='payment_russian_post' >
        <p class='order_margin_both'>Вы выбрали способ оплаты наложенным платежом. Заказ будет отправлен вам без предоплаты. Оплатить заказ в размере <xsl:value-of select="$total_insurance" /> руб. вы сможете при получении в почтовом отделении. </p>
        <p class='order_margin_both'>Если Вас все устраивает – нажмите кнопку «Подтвердить» и в течение 24 часов заказ будет обработан нашими специалистами. После обработки на вашу электронную почту придет письмо с подтверждением заказа и передачи его в службу отправки. По факту передачи заказа на почту отправлению будет присвоен «почтовый идентификатор». Все последующие изменения статуса заказа (а так же «почтовый идентификатор») вы будете получать письмами на вашу электронную почту.</p>
    </div>

    <div id='payment_other' style='display: none;'>
        <p class='order_margin_both'>Вы выбрали предоплату заказа через платежную систему. Заказ будет отправлен вам после получения оплаты в размере <xsl:value-of select="$total" /> рублей. </p>
        <p class='order_margin_both'>Если вас все устраивает – нажмите кнопку «Подтвердить» и течении 24 часов заказ будет обработан нашими специалистами, выбранные товары будут зарезервированы за вами. После обработки на вашу электронную почту придет письмо с подтверждением заказа, номером счета для оплаты. По факту поступления средств на наш счет – заказ принимает статус «Оплачено» и передается на почту для отправки. По факту передачи заказа на почту отправлению будет присвоен «почтовый идентификатор». Все последующие изменения статуса заказа (а так же «почтовый идентификатор») вы будете получать письмами на вашу электронную почту.</p>
    </div>

     <xsl:variable select="document('udata://emarket/fast_purchasing/')//delivery_choose//item[@active='active']/@id" name='delivery_id' />
     <xsl:if test="$delivery_id = '873'">
            <script type="text/javascript">
                setPayMethod('other');
            </script>
     </xsl:if>
</xsl:template>

<xsl:template match="item" mode="payment_item" >
    <xsl:variable select="document(concat('uobject://', @id))//property[@name = 'key']/value" name="key" />
    <xsl:variable select="document('udata://emarket/fast_purchasing/')//delivery_choose//item[@active='active']/@id" name='delivery_id' />

    <xsl:choose>
        <xsl:when test="$delivery_id = '873' and $key = 'russian_post' ">
        
            <!-- пусто -->
            
        </xsl:when>
        <xsl:otherwise>
            
            <li>
                <div class="nice_checkbox">
                    <input name='payment-id' type='radio' value='{@id}' />
                    <xsl:choose>
                        <xsl:when test="$key = 'russian_post'">
                            <span type='excepting' parent='pay_methods' onclick="setPayMethod('russian_post')"></span>
                        </xsl:when>
                        <xsl:otherwise>
                            <span type='excepting' parent='pay_methods' onclick="setPayMethod('other')" ></span>
                        </xsl:otherwise>
                    </xsl:choose>
                </div>
                <div class='method_name {$key}'>
                    <xsl:value-of select="@name" />
                </div>
            </li>
    
        </xsl:otherwise>
    </xsl:choose>

    
</xsl:template>


<!-- успешный заказ -->
<xsl:template match="result[@method = 'purchase']">
  <xsl:apply-templates select="document('udata://emarket/purchase')/udata" />
</xsl:template>

<xsl:template match="purchasing[@stage = 'result'][@step = 'successful']">
  <h1 umi:element-id="2" umi:field-name="go_title" >
    <xsl:value-of select="$constants//property[@name = 'go_title']/value" />
  </h1>
  <div umi:element-id="2" umi:field-name="go_message"  >
      <xsl:value-of select="$constants//property[@name = 'go_message']/value" disable-output-escaping="yes" />
  </div>
</xsl:template>

<!-- главная -->
<xsl:template match="result[@module = 'content'][@method = 'content'][page/@is-default = '1']">
	
    <xsl:apply-templates select="document('utype://116')//group[@name = 'carousel']/field" mode="carousel" />

	<h2 umi:element-id="{@pageId}" umi:field-name="h1">
		<xsl:value-of select=".//property[@name='h1']/value" />
	</h2>
	<div umi:element-id="{@pageId}" umi:field-name="content">
		<xsl:value-of select=".//property[@name='content']/value" disable-output-escaping="yes" />
	</div>
</xsl:template>

<xsl:template match="field" mode='carousel'>
    <h2>
        <xsl:value-of select="@title" />
    </h2>
    <xsl:apply-templates select="document(concat('usel://usel_carousel/?property=', @name))/udata" />
    <div class="fence_delimiter">
        <div class="right"></div>
        <div class="left"></div>
    </div>
</xsl:template>

<xsl:template match="field[position() = 1]" mode='carousel'>
    <h1>
        <xsl:value-of select="@title" />
    </h1>
    <xsl:apply-templates select="document(concat('usel://usel_carousel/?property=', @name))/udata" />
    <div class="fence_delimiter">
        <div class="right"></div>
        <div class="left"></div>
    </div>
</xsl:template>

<xsl:template match="field[position() = last()]" mode='carousel'>
    <h1>
        <xsl:value-of select="@title" />
    </h1>
    <xsl:apply-templates select="document(concat('usel://usel_carousel/?property=', @name))/udata" />
    <div class="fence_delimiter nomargin">
        <div class="right"></div>
        <div class="left"></div>
    </div>
</xsl:template>

<xsl:template match="udata[@module = 'usel'][@method = 'usel_carousel']">
    <div class='carousel'>
        <div class='roll left disabled'></div>
        <div class='cut_box'>
            <ul>
               <xsl:apply-templates select="./page" mode="carousel" />
            </ul>
        </div>
        <div class='roll right'></div>
    </div>
</xsl:template>

<xsl:template match="page" mode='carousel'>
    <xsl:if test="document(concat('udata://catalog/check_object_qty/?id=', @id))/udata/qty &gt; 0">
    <li>
        <xsl:apply-templates select="." mode="carousel_details" />
    </li>
    </xsl:if> 
</xsl:template>

<xsl:template match="page[position() = last()]" mode='carousel'>
    <li class='last'>
        <xsl:apply-templates select="." mode="carousel_details" />
    </li>
</xsl:template>

<xsl:template match="page" mode="carousel_details">
    <xsl:variable select="document(concat('upage://', @id))/udata" name="good" />
    <xsl:variable select="$good//property[@name = 'price']/value" name="good_price" />
    <xsl:variable select="$good//property[@name = 'percent_tgk_range']/value" name="good_tgk" />
    <xsl:variable select="$good//property[@name = 'main_image']/value" name="good_image" />
    <xsl:variable select="$good//name" name="good_name" />
    <div class='good_box'>
        <a class='image' href='{@link}'><img src='{$good_image}' alt='{$good_name}' width='125' /></a>
        <a class='title' href='{@link}' umi:element-id="{@id}" umi:field-name="name">
            <xsl:value-of select="$good_name" />
        </a>
        <span class='tgk'>
            <xsl:text>ТГК </xsl:text>
            <span umi:element-id="{@id}" umi:field-name="percent_tgk_range">
                <xsl:value-of select="$good_tgk"  />
            </span>
        </span>
        <span class='price' umi:element-id="{@id}" umi:field-name="price">
            <xsl:value-of select="$good_price" />
        </span>
    </div>
</xsl:template>

<!-- все производители = каталог -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '28']" >
    <h1 umi:element-id="{@pageId}" umi:field-name="h1">
        <xsl:value-of select=".//property[@name='h1']/value" />
    </h1>
    <xsl:apply-templates select="document('udata://catalog/getCategoryList//(producers)/')/udata" mode='producers' />
</xsl:template>

<xsl:template match="udata[@module = 'catalog'][@method = 'getCategoryList']" mode='producers' >
    <ul id="producers">
        <xsl:apply-templates select=".//item" mode="catalog" />
    </ul>
</xsl:template>

<xsl:template match="item" mode='catalog'>
    <xsl:variable select="document(concat('upage://', @id))/udata" name="producer" />
    <xsl:variable select="$producer//property[@name = 'header_pic']/value" name="producer_image" />
    <xsl:variable select="$producer//property[@name = 'short_desc']/value" name="producer_desc" />
    <li>
        <a href="{@link}?&amp;order_filter[name]=1"><img class='image' src='{$producer_image}' alt='{.}' /></a>
        <h2><a href="{@link}?&amp;order_filter[name]=1" umi:element-id="{@id}" umi:field-name="name">
            <xsl:value-of select="." />
        </a></h2>
        <div class='desc' umi:element-id="{@id}" umi:field-name="short_desc">
            <xsl:value-of select="$producer_desc" />
        </div>
    </li>
</xsl:template>


<!-- просмотр 1 товара = объект каталога -->
<xsl:template match="result[@module = 'catalog'][@method = 'object']">
    <xsl:variable select=".//group[@name = 'images']/property[2]/value" name='original_image' />
    <!-- <xsl:variable select="document(concat('udata://system/makeThumbnail/(.',$original_image,')/315/360/'))/udata/src" name="thumb" /> -->
    <div id="view_good">
        <div class="photos">
            <!-- <div class='zoom'>+</div> -->
            <div class="image">
                <!-- src="{$thumb}" -->
                <!-- original="{$original_image} -->
                <a class='zoom_img test' href="{$original_image}">
                    <img  src="{$original_image}"  alt="{.//property[@name = 'h1']/value}"  id='loup'/>                    
                </a>
            </div>
            <div id='photos_carousel'>
                <div class='roll left disabled'></div>
                <div class='cut_box'>
                    <ul>
                        <xsl:apply-templates select=".//group[@name = 'images']/property[position() != 1]" mode="gallery" />
                    </ul>
                </div>
                <div class='roll right'></div>
            </div>
            <div id="invisible_fancy" style="display: none;">
                <ul>
                    <xsl:apply-templates select=".//group[@name = 'images']/property[position() != 1]" mode="fancy_gallery" />
                </ul>
            </div>
        </div>
        <div class="about">
            <h1 umi:element-id="{@pageId}" umi:field-name="h1">
                <xsl:value-of select=".//property[@name = 'h1']/value" />
            </h1>
            <div class='to_cart'>
                <xsl:apply-templates select="document(concat('udata://catalog/getObjectsList/0/', @pageId))/udata" mode='packages' />
                <!-- 
                <div class="points">
                    <span>+19,69</span> Б
                </div> 
                -->
            </div>
            <ul class='icons'>
                <li title="Селекционер/Сид-Банк">
                    <img src='/templates/webeffection/images/view_icons/producer.png' alt='Производитель'/>
                    <span>
                        <xsl:value-of select=".//parents/page[2]/name" />
                    </span>
                </li>
                <xsl:apply-templates select=".//group[@name = 'cats']/property" mode="icons" />
                <xsl:apply-templates select=".//group[@name = 'types']/property" mode="icons" />
                <xsl:apply-templates select=".//group[@name = 'flags']/property" mode="icons" />
                <xsl:apply-templates select=".//group[@name = 'properties']/property[@type != 'wysiwyg' and @name != 'percent_tgk']" mode="icons_have_value" /> 
            </ul>
        </div>
    </div>
    <div class="clear"></div>

    <h2>Информация о продукте</h2>

    <div umi:element-id="{@pageId}" umi:field-name="desc">
        <xsl:value-of select=".//property[@name = 'desc']/value" disable-output-escaping="yes" />
    </div>

</xsl:template>


<xsl:template match="udata[@module = 'catalog'][@method = 'getObjectsList']" mode='packages'>
    <xsl:choose>
        <xsl:when test="./total != 0">
            <label class="package_type">Тип упаковки:</label>
            <label class="package_amount">Кол-во:</label>
            <select id='package_selector' onchange='fillPackageOptions();' class='type'>
                <xsl:apply-templates select=".//item" mode='packages' />
            </select>
            <a href="javascript:;" onclick="addPackageToCart(this);" class="add">В корзину</a>
            <div class="price">
                <em>Цена: <br/><small>(за упаковку)</small></em>
                <span id='package_price'>
                    ...
                </span>
                <xsl:text>руб.</xsl:text>
            </div>
            <select id='package_count' class='count'>
                <option>
                    ...
                </option>
            </select> 
        </xsl:when>
        <xsl:otherwise>
            <strong><xsl:text>Нет на складе.</xsl:text></strong>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match='item' mode='packages'>
    <xsl:variable select="document(concat('upage://', @id))/udata" name='good_props' />
    <option value="{@id}" count="{$good_props//property[@name ='common_quantity']/value}" price="{$good_props//property[@name ='price']/value}" >
        <xsl:value-of select="." />
    </option>
</xsl:template>


<xsl:template match="property" mode='gallery'>
    <xsl:variable select="substring(./value,2)" name="path" />
    <xsl:variable select="document(concat('udata://system/makeThumbnail/(', $path, ')/97'))//src" name="thumbnail" />
    <li><img src="{$thumbnail}" big="{./value}" alt="{//property[@name = 'h1']/value}" /></li>
</xsl:template>


<xsl:template match="property" mode='fancy_gallery'>
    <xsl:variable select="substring(./value,2)" name="path" />
    <li><a href="{./value}" class="fancy" rel="catalog_item_gallery"><xsl:value-of select="//property[@name = 'h1']/value" /></a></li>
</xsl:template>


<!-- просмотр товаров 1 производителя = раздел каталога -->
<xsl:template match="result[@module = 'catalog'][@method = 'category']">
    <img src="{.//property[@name = 'header_pic']/value}" class='include_left' alt="{.//property[@name = 'h1']/value}" />
    <h1 umi:element-id="{@pageId}" umi:field-name="h1">
        <xsl:value-of select=".//property[@name = 'h1']/value" />
    </h1>
    <div umi:element-id="{@pageId}" umi:field-name="descr">
        <xsl:value-of select=".//property[@name = 'descr']/value" disable-output-escaping="yes" />
    </div>
    <xsl:apply-templates select="document(concat('udata://catalog/getObjectsList/0/', @pageId , '/', $per_page , '/0/0/'))/udata" />
</xsl:template>

<xsl:template match="udata[@module = 'catalog'][@method = 'getObjectsList']" >
    <xsl:apply-templates select="." mode="list_of_objects" />
</xsl:template>

<xsl:template match="udata[@module = 'custom'][@method = 'myAdvancedSearch']" >
    <xsl:apply-templates select="." mode="list_of_objects" />
</xsl:template>

<xsl:template match="udata" mode="list_of_objects" >
    <xsl:param name="no_content_above" />
    <xsl:if test="./total != 0">
        <ul class="page_nav search {$no_content_above}">
            <li class='text order_a fl'>
                <span class='sort_word'>Сортировать по </span>
                <xsl:apply-templates select="document(concat('udata://custom/order_by_new/(price)/', ./type_id))/udata" />
                <span>|</span>
                <xsl:apply-templates select="document(concat('udata://custom/order_by_new/(percent_tgk)/', ./type_id))/udata" />
                <span>|</span>
                <xsl:apply-templates select="document(concat('udata://custom/order_by_new/(name)/', ./type_id))/udata" />
            </li>
            <li class="delimiter fl"></li>
            <li class='text fl'>Отображать по</li>
            <li class='per_page fl'>
                <xsl:apply-templates select="exsl:node-set($per_page_options)/select" mode="per_page" />
            </li>
            <xsl:if test="./total > ./per_page">
                <li class='nums fr'>
                    <xsl:apply-templates select="document(concat('udata://system/numpages/', ./total, '/', $per_page, '/0/(p)/3'))/udata" mode='catalog_nav' />
                </li>
                <li class="delimiter fr"></li>
            </xsl:if> 
        </ul>
        <div id='find_count'>
            <xsl:text>товаров найдено: </xsl:text>
            <xsl:value-of select="./total" />
        </div>
        <div class='catalog'>
            <xsl:apply-templates select="./lines/item" mode="good" />
        </div>
        <ul class='page_nav search'>
            <li class='text order_a fl'>
                <xsl:text>Сортировать по </xsl:text>
                <xsl:apply-templates select="document(concat('udata://custom/order_by_new/(price)/', ./type_id))/udata" />
                <span>|</span>
                <xsl:apply-templates select="document(concat('udata://custom/order_by_new/(percent_tgk)/', ./type_id))/udata" />
                <span>|</span>
                <xsl:apply-templates select="document(concat('udata://custom/order_by_new/(name)/', ./type_id))/udata" />
            </li>
            <li class="delimiter fl"></li>
            <li class='text fl'>Отображать по</li>
            <li class='per_page fl'>
                <xsl:apply-templates select="exsl:node-set($per_page_options)//select" mode="per_page" />
            </li>
            <xsl:if test="./total > ./per_page">
                <li class='nums fr'>
                    <xsl:apply-templates select="document(concat('udata://system/numpages/', ./total, '/', $per_page, '/0/(p)/3'))/udata" mode='catalog_nav' />
                </li>
                <li class="delimiter fr"></li>
            </xsl:if>    
        </ul>
    </xsl:if>    
</xsl:template>


<xsl:template match="select" mode="per_page">
    <select onchange="perPageChanged(this);">
        <xsl:apply-templates select="./option" mode="per_page" />
    </select>
</xsl:template>

<xsl:template match="option" mode='per_page'>
    <xsl:choose>
        <xsl:when test=". = $per_page">
            <option selected="selected">
                <xsl:value-of select="." />
            </option>
        </xsl:when>
        <xsl:otherwise>
            <option>
                <xsl:value-of select="." />
            </option>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="udata[@module = 'custom'][@method = 'order_by_new']">
    <a href="{./link}" class='{./dir}'>
        <xsl:value-of select="./title" />
    </a>
</xsl:template>

<xsl:template match="item" mode='good' >
    <div class="item">
        <xsl:apply-templates select="." mode="good_details" />
    </div>
</xsl:template>

<xsl:template match="item[position() = 1 or position() mod 4 = 0]" mode='good' >
    <div class="item first">
        <xsl:apply-templates select="." mode="good_details" />
    </div>
</xsl:template>

<xsl:template match="item[position() = last()]" mode='good' >
    <div class="item last">
        <xsl:apply-templates select="." mode="good_details" />
    </div>
    <div class="clear"></div>
</xsl:template>

<xsl:template match="item[position() mod 3 = 0 and position() != last()]" mode='good' >
    <div class="item last">
        <xsl:apply-templates select="." mode="good_details" />
    </div>
    <div class="clear"></div>
    <div class="fence_delimiter">
        <div class="right"></div>
        <div class="left"></div>
    </div>
</xsl:template>

<xsl:template match="item" mode="good_details" >
    <xsl:variable select="document(concat('upage://', @id))" name="good" />
    <xsl:variable select="$good//property[@name = 'price']/value" name="good_price" />
    <xsl:variable select="$good//property[@name = 'percent_tgk_range']/value" name="good_tgk" />
    <xsl:variable select="$good//property[@name = 'main_image']/value" name="good_image" />
    <a href='{@link}'><img class='image' src='{$good_image}' alt='{.}' /></a>
    <div class="title"><a href='{@link}' umi:element-id="{@id}" umi:field-name="name">
        <xsl:value-of select="." />
    </a></div>
    <span class='tgk'>
        <xsl:text>ТГК </xsl:text>
        <span umi:element-id="{@id}" umi:field-name="percent_tgk_range">
            <xsl:value-of select="$good_tgk" />
        </span>
    </span>
    <span class='price' umi:element-id="{@id}" umi:field-name="price">
        <xsl:value-of select="$good_price" />
    </span>
    <ul class='icons'>
        <xsl:apply-templates select="$good//group[@name = 'cats' or @name = 'types' or @name = 'flags']/property" mode="icons_limited" />
    </ul>
</xsl:template>

<xsl:template match="property" mode="icons_limited" >
    <xsl:if test="position() &lt;= 5">
        <li>
            <img src='/templates/webeffection/images/view_icons/{@name}.png' alt='{./title}'/>
            <xsl:value-of select="./title" />
        </li>
    </xsl:if>   
</xsl:template>

<xsl:template match="property" mode="icons" >
    <xsl:variable name="field_name" select="@name" />
    <li title="{document('utype://116')/udata/type/fieldgroups/group/field[@name = $field_name]/tip}">
        <img src='/templates/webeffection/images/view_icons/{@name}.png' alt='{./title}'/>
        <xsl:value-of select="./title" />
    </li>
</xsl:template>

<xsl:template match="property" mode="icons_have_value" >
    <xsl:variable name="field_name" select="@name" />
    <li title="{document('utype://116')/udata/type/fieldgroups/group/field[@name = $field_name]/tip}">
        <img src='/templates/webeffection/images/view_icons/{@name}.png' alt='{./title}'/>
        <span umi:element-id="{/result/@pageId}" umi:field-name="{@name}">
            <xsl:value-of select="./value" />
        </span>
    </li>
</xsl:template>

<xsl:template match="property[@type ='int' or @type = 'float']" mode="icons_have_value" >
    <xsl:variable name="field_name" select="@name" />
    <li title="{document('utype://116')/udata/type/fieldgroups/group/field[@name = $field_name]/tip}">
        <img src='/templates/webeffection/images/view_icons/{@name}.png' alt='{./title}'/>
        <span umi:element-id="{/result/@pageId}" umi:field-name="{@name}">
            <xsl:value-of select="./value" />
        </span>
        <xsl:text>%</xsl:text>
    </li>
</xsl:template>

<!-- обратная связь -->
<xsl:template match="result[@module = 'content'][@method = 'content'][@pageId = '6']">
	<h1 umi:element-id="{@pageId}" umi:field-name="h1">
		<xsl:value-of select=".//property[@name='h1']/value" />
	</h1>
	<div umi:element-id="{@pageId}" umi:field-name="content">
		<xsl:value-of select=".//property[@name='content']/value" disable-output-escaping="yes" />
	</div>
    <xsl:apply-templates select="document('udata://webforms/add/110')/udata" mode="feedback"/>
</xsl:template>

<xsl:template match="udata[@module = 'webforms'][@method = 'add']" mode="feedback">
    <form method="post" action="/webforms/mysend/" id="feedback" class='form_common' >
        <input type="hidden" name="system_form_id" value="{@form_id}" />
        <input type="hidden" name="ref_onsuccess" value="/webforms/posted/" />
        <input type="hidden" name="system_email_to" value="{.//item[@selected='selected']/@id}" />
        <ul>
            <xsl:apply-templates select=".//field" mode="feedback"/>
            <xsl:apply-templates select="document('udata://system/captcha')/udata[url]" mode='feedback' />
            <li>
                <span class='button' onclick="submitFeedback();">Отправить сообщение</span>
                <xsl:if test="$mistaken">
                    <span class="report">
                        Проверьте правильность полей
                    </span>
                </xsl:if>    
            </li>
        </ul>
    </form>
    <script type="text/javascript">
        myRestoreFormData(document.getElementById('feedback'));
        function submitFeedback(){
            var form = document.getElementById('feedback');
            mySaveFormData(form);
            form.submit();
        }
        truncateFormData('feedback');
    </script>
</xsl:template>

<xsl:template match="field[@type = 'string']" mode="feedback">
    <li>
        <label>
            <xsl:value-of select="@title"/>
            <xsl:if test="@required">
                <xsl:text>*</xsl:text>
            </xsl:if>    
        </label>
        <xsl:choose>
            <xsl:when test="contains($mistaken, concat('|',@id,'|'))">
                <input class='text mistaken' type="text" name="{@input_name}" />
            </xsl:when>
            <xsl:otherwise>
                <input class='text' type="text" name="{@input_name}" />
            </xsl:otherwise>
        </xsl:choose>
    </li>
</xsl:template>

<xsl:template match="field[@type = 'text']" mode="feedback">
    <li>
        <label>
            <xsl:value-of select="@title"/>
            <xsl:if test="@required">
                <xsl:text>*</xsl:text>
            </xsl:if>    
        </label>
        <xsl:choose>
            <xsl:when test="contains($mistaken, concat('|',@id,'|'))">
                <textarea class='mistaken' name="{@input_name}"></textarea>
            </xsl:when>
            <xsl:otherwise>
                <textarea name="{@input_name}"></textarea>
            </xsl:otherwise>
        </xsl:choose>
    </li>
</xsl:template>

<xsl:template match="field[@type = 'relation']" mode="feedback">
    <li>
        <label>
            <xsl:value-of select="@title" />
            <xsl:if test="@required">
                <xsl:text>*</xsl:text>
            </xsl:if>    
        </label>
        <select name="{@input_name}">
            <xsl:apply-templates select="document(concat('utype://', @type-id))//field" mode='option' />
        </select>
    </li>
</xsl:template>

<xsl:template match="field" mode="option">
    <option value='{@id}'>
        <xsl:value-of select="@title" />
    </option>
</xsl:template>

<xsl:template match="result[@module = 'webforms'][@method = 'posted']">
    <h1><xsl:value-of select="@header" /></h1>
    <p>Письмо успешно отправлено.</p>
</xsl:template>

<xsl:template match="udata[@module = 'system'][@method = 'captcha']" mode='feedback'>
    <li>
        <label>
            Код с картинки*
        </label>
        <img src="{url}{@random_string}" srcfirst="{url}{@random_string}" class='captcha_image' id='captcha' />
        <span class='captcha_update' onclick="updateCaptcha();"></span>
        <xsl:choose>
            <xsl:when test="contains($mistaken, '|captcha|')">
                <input type="text" name="captcha" class='captcha_text mistaken' />
            </xsl:when>
            <xsl:otherwise>
                <input type="text" name="captcha" class='captcha_text' />
            </xsl:otherwise>
        </xsl:choose>
    </li>
</xsl:template>

<!-- хлебные крошки -->
<xsl:template match="udata[@method = 'navibar']">
    <xsl:if test="items/item[1]/@id != 1">
        <ul id="bread_crumbs">
            <li><a href="/">Семена канабиса</a></li>
            <xsl:apply-templates select="items/item" mode="breadcrumbs"/>
        </ul>
    </xsl:if>    
</xsl:template>

<xsl:template match="item" mode="breadcrumbs">
    <xsl:variable select="document(concat('upage://', @id))/udata/page/basetype" name="basetype" />
    <xsl:choose>
        <xsl:when test="$basetype/@module = 'catalog' and $basetype/@method = 'category'">
            <li>/</li>
            <li>
                <a href="{@link}?&amp;order_filter[name]=1"><xsl:value-of select="."/></a>
            </li>
        </xsl:when>
        <xsl:otherwise>
            <li>/</li>
            <li>
                <a href="{@link}"><xsl:value-of select="."/></a>
            </li>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="item[position() = last()]" mode="breadcrumbs">
    <li>/</li>
    <li><xsl:value-of select="."/></li>
</xsl:template>


<!-- постраничная навигация -->
<xsl:template match="udata[@module = 'system'][@method = 'numpages']">
    <div id='general_page_nav'>
        <xsl:if test="./tobegin_link">
            <a href="{./tobegin_link}">&lt;&lt;</a>
            <a href="{./toprev_link}">&lt;</a>
        </xsl:if>    
        <xsl:apply-templates select=".//item" mode="numpages" />
        <xsl:if test="./toend_link">
            <a href="{./tonext_link}">&gt;</a>
            <a href="{./toend_link}">&gt;&gt;</a>
        </xsl:if> 
    </div>
</xsl:template>

<xsl:template match="udata[@module = 'system'][@method = 'numpages']" mode='catalog_nav' >
    <xsl:choose>
        <xsl:when test="./tobegin_link">
            <a class='step first' href="{./tobegin_link}">&#160;</a>
            <a class='step prev' href="{./toprev_link}">&#160;</a>
        </xsl:when>
        <xsl:otherwise>
            <a class='step first disabled'>&#160;</a>
            <a class='step prev disabled'>&#160;</a>
        </xsl:otherwise>
    </xsl:choose>
    <xsl:apply-templates select=".//item" mode="numpages" />
    <xsl:choose>
        <xsl:when test="./toend_link">
            <a class='step next' href="{./tonext_link}">&#160;</a>
            <a class='step last' href="{./toend_link}">&#160;</a>
        </xsl:when>
        <xsl:otherwise>
            <a class='step next disabled'>&#160;</a>
            <a class='step last disabled'>&#160;</a>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="item" mode='numpages'>
    <a href="{@link}">
        <xsl:value-of select="." />
    </a>
</xsl:template>

<xsl:template match="item[@is-active = '1']" mode='numpages'>
    <a class='active'>
        <xsl:value-of select="." />
    </a>
</xsl:template>





</xsl:stylesheet>
