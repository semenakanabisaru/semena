<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/autoupdate">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="group" mode="settings-view">
		<xsl:variable name="patches-disabled" select="option[@type = 'boolean' and @name = 'patches-disabled']" />

		<xsl:text disable-output-escaping="yes"><![CDATA[
		<script language="javascript">
			jQuery(document).ready(function() {
				jQuery.ajaxSetup({
					error: function() {
						return error();
					}
				});
			});

			var stepHeaders = ['Проверка прав пользователя', 'Проверка обновлений', 'Загрузка пакета тестирования', 'Распаковка архива с тестами', 'Запись начальной конфигурации', 'Выполняется тестирование', 'Подготовка сохранения данных', 'Резервное копирование файлов', 'Резервное копирование базы данных', 'Скачивание компонентов', 'Распаковка компонентов', 'Проверка компонентов', 'Обновление подсистемы', 'Обновление базы данных', 'Установка компонентов', 'Обновление конфигурации', 'Очистка кеша', 'Очистка системного кеша'];
			var stepNames = ['check-user', 'check-update', 'download-service-package', 'extract-service-package', 'write-initial-configuration', 'run-tests', 'check-installed', 'backup-files', 'backup-mysql', 'download-components', 'extract-components', 'check-components', 'update-installer', 'update-database', 'install-components', 'configure', 'cleanup', 'clear-cache'];
			 var step;
			 var for_backup='';
			 var rStep = 0;
			 var rStepHeaders = ['Восстановление файлов', 'Восстановление базы данных'];
			 var rStepNames = ['restore-files', 'restore-mysql'];

			 function error() {
				var text = "Произошла ошибка во время выполнения запроса к серверу.<br/>" +
					"<a href=\"http://errors.umi-cms.ru/15000/\" target=\"_blank\" >" +
					"Подробнее об ошибке 15000</a>";
				h='<p style="text-align:center;">' + text + '</p>';
				h+='<p style="text-align:center;">';
				h+='<button onclick="install(); return false;">Повторить попытку</button></p>';
				showMess(h);
				return false;
			 }
			function changeUpdateButton(input) {
				if (input.checked) {
					jQuery("#update_button").removeAttr("disabled");
				}
				else {
					jQuery("#update_button").attr("disabled", "disabled");
				}
			}

			function callBack(r) {
				if (!r) {
					return error();
				}

				if (jQuery('html', r).length>0) {
					return error();
				}

				state = jQuery('install',r).attr('state');
				if (state=='inprogress') {
					install();
					return false;
				}
				errors = jQuery('error',r);

				// Ошибки на шаге 0, 1 обрабатываются в свитче, для остальных - обработка здесь.
				if (step>1) {
					if (errors.length>0) {
						h='<p style="text-align:center; font-weight:bold;">В процессе обновления произошла ошибка.</p>';
						var mess = errors.attr('message');
						if (mess.length >= 305) {
							h+='<p style="text-align:center;"><div style="height: 80px; overflow-y: scroll;">' + mess + '</div></p>';
						}
						else {
							h+='<p style="text-align:center;">' + mess + '</p>';
						}

						h+='<p style="text-align:center;">';
						if ((step>=12)&&(for_backup=='all')) {
							h+='<button onclick="rollback(); return false;">Восстановить</button>';
						}
						h+='<button onclick="install(); return false;">Повторить попытку</button></p>';
						showMess(h);
						return false;
					}
				}
				switch(step) {
					case 0: {
						if (errors.length>0) {
							h='<p style="text-align:center; font-weight:bold;">Ваших прав недостаточно для обновления.</p>';
							h+='<p style="text-align:center;">Для дальнейшего обновления системы, пожалуйста, выйдите из авторизованного режима и повторно зайдите как супервайзер.</p>';
							h+='<p style="text-align:center;"><button onclick="jQuery(\'div.popupClose\').click();">Закрыть</button></p>';
							showMess(h);
							return false;
						}
					}
					break;
					case 1: {
						if (errors.length>0) {
							if (errors.attr('message')=='Updates not avaiable.') {
								h='<p style="text-align:center; font-weight:bold;">Доступных обновлений нет.</p>';
								h+='<p style="text-align:center;"><button onclick="jQuery(\'div.popupClose\').click();">Закрыть</button>&nbsp;<button onclick="step++; install(); return false;">Обновить принудительно.</button></p>';
										showMess(h);
									 }
							else if (errors.attr('message')=='Updates avaiable.') {
								h='<div style="text-align:center; font-weight:bold;">Доступны обновления.</div>';
								h+='<div style="padding-top:7px; padding-left:5px;">Посмотрите, что изменилось <a href="http://www.umi-cms.ru/product/changelog/" target="_blank">в этой версии</a>&nbsp;<span style="font-size:1.25em">→</span></div>';
								h+='<div style="padding-top:5px;"><label><input type="checkbox" onchange="changeUpdateButton(this);"> Да, я хочу выполнить обновление.</label></div>';
								h+='<div style="text-align:center;padding-top:10px; padding-bottom:15px;"><button onclick="jQuery(\'div.popupClose\').click();">Не обновлять.</button>&nbsp;<button onclick="step++; install(); return false;" id="update_button" disabled="disabled">Обновить систему.</button></div>';
								showMess(h);
							}
							else { // Ожидаемое сообщение - сервер отклонил запрос.
								h='<p style="text-align:left;">' + errors.attr('message') + '</p>';
								h+='<p style="text-align:center; font-weight:bold;">Продолжение обновления невозможно.</p>';
								h+='<p style="text-align:center;"><button onclick="jQuery(\'div.popupClose\').click();">Закрыть</button></p>';
										showMess(h);
							}
									 return false;
						}
					}
					break;
					case 5: {
						h='<p style="text-align: center; font-weight:bold;">Сохранение перед установкой:</p>';
						h+='<p style="text-align: left; font-weight:normal;">';
						h+='<label><input type="radio" name="for_backup" value="all" checked="true" />Основных файлов и базы данных</label><br/>';
						//h+='<label><input type="radio" name="for_backup" value="files" />Только основных файлов</label><br/>';
						//h+='<label><input type="radio" name="for_backup" value="base" />Только базу данных</label><br/>';
						h+='<label><input type="radio" name="for_backup" value="none" />Ничего (не рекомендуется)</label><br/>';
						h+='</p>';
						h+='<p style="text-align: center;"><button onclick="prepareBackup(); return false;">Продолжить</button></p>';
						showMess(h);
						return false;
					}
					break;
					case 6: { // Бекапирование подготовлено
						if (for_backup=='base') { // Пропускаем бекапирование файлов
							step = 7;
						}
					}
					break;
					case 7: { // Файлы забекапированы
						 if (for_backup!='all') { // Пропускаем бекапирование базы
							step = 8;
						 }
					}
					break;
					case 17: {
						h='<p style="text-align:center; font-weight:bold;">Обновление завершено.</p>';
						h+='<p style="text-align:center;">Узнайте, что нового <a href="http://www.umi-cms.ru/support/changelog/" target="_blank">в этой версии</a>.</p>';
						h+='<p style="text-align:center;"><button onclick="window.location.href=\'/\'; return false;">Перейти на сайт</button></p>';
									 showMess(h);
									 return false;
					}
				}

				step++;
				install();
				return false;
			}

			function startPing() {
				jQuery.get('/smu/installer.php', {step:'ping', guiUpdate:'true'});
				setTimeout('startPing()', (3*60*1000));
			}

			function install() {
				if (step > stepNames.length-1) {
					return false;
				}
				h='<p style="text-align: center;">' + stepHeaders[step] + '. Пожалуйста, подождите.</p>';
				h+='<p style="text-align: center;"><img src="/images/cms/loading.gif" /></p>';
				showMess(h);

				jQuery.get('/smu/installer.php', {step:stepNames[step], guiUpdate:'true'}, function(r) { callBack(r); } );
				return false;
			}

			function showMess(h, t) {
				if (jQuery("div.eip_win").length==0) {
					openDialog({
						'title': (typeof(t)=='string')?t:'Обновление системы',
						'text': h,
						'stdButtons': false
					});
					jQuery('div.popupClose').css('display','none');
				}
				else {
					jQuery("div.eip_win div.eip_win_title").html((typeof(t)=='string')?t:'Обновление системы');
					jQuery("div.eip_win div.popupText").html(h);
				}
			}

			function prepareBackup() {
				for_backup = jQuery("input[name='for_backup']:checked").val();
				if (for_backup=='none') {
					step = 9;
				}
				else {
					step = 6;
				}
				if(window.session) {
					window.session.destroy();
				}
				startPing(); // Запускаем постоянное обращение к серверу во избежание потери сессии
				install();
			}

			function rollback() {
				t = 'Отмена установки';
				h ='<p style="text-align: center;">' + rStepHeaders[rStep] + '. Пожалуйста, подождите.</p>';
				h+='<p style="text-align: center;"><img src="/images/cms/loading.gif" /></p>';
				showMess(h, t);
				jQuery.get('/smu/installer.php', {'step':rStepNames[rStep], 'guiUpdate':'true'}, rollbackBackTrace);
			}

			function rollbackBackTrace(r) {
				errors = jQuery('error', r);
				if (errors.length>0) {
					alert('Ошибка');
				}

				state = jQuery('install', r).attr('state');
				if (state=='done') {
					rStep++;
				}

				if (rStep>rStepHeaders.length-1) {
					t = 'Отмена установки';
					h ='<p style="text-align: center;">Система была восстановлена на сохраненное состояние.</p>';
					h+='<p style="text-align: center;"><input type="button" onclick="window.location.href=\'/admin/autoupdate/\'; return false;" value="Закрыть" /></p>';
					showMess(h, t);
					return false;
				}

				rollback();
			}

		</script>
		]]>
		</xsl:text>

		<xsl:choose>
			<xsl:when test="/result/@demo">
			<script>
				jQuery(document).ready(function() {
					jQuery('div.content div.buttons input:button').click(function() {
						jQuery.jGrowl('<p>В демонстрационном режиме эта функция недоступна</p>', {
							'header': 'UMI.CMS',
							'life': 10000
						});
						return false;
					});
				});
			</script>
			</xsl:when>
			<xsl:otherwise>
			<xsl:text disable-output-escaping="yes"><![CDATA[<script>
				jQuery(document).ready(function() {
					jQuery('div.content div.buttons input:button').click(function() {
						step = 0;
						install();
						return false;
					});
				});
			</script>
			]]></xsl:text>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="$patches-disabled = '1'">
		<div class="panel">
					<div class="header" style="cursor:default">
				<span>
					<xsl:value-of select="@label" />
				</span>
				<div class="l" /><div class="r" />
			</div>
			<div class="content">
				<table class="tableContent">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.view" />
					</tbody>
				</table>
				<div class="buttons">
					<div>
						<input type="button" value="&label-check-updates;" />
						<span class="l" /><span class="r" />
					</div>
				</div>
			</div>
		</div>
			</xsl:when>
			<xsl:otherwise>
				<table class="tableContent">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.view" />
					</tbody>
				</table>
				<div class="buttons">
					<div>
						<input type="button" value="&label-check-updates;" />
						<span class="l" /><span class="r" />
					</div>
				</div>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="option[@type = 'boolean' and @name = 'patches-disabled']" mode="settings.view" />

	<xsl:template match="option[@type = 'boolean' and @name = 'disabled']" mode="settings.view">
		<!-- <tr>
			<td class="eq-col">
				<label>
					<xsl:text>&label-manual-update;</xsl:text>
				</label>
			</td>
			<td>
				<div class="buttons">
					<div>
						<input type="button" value="&label-check-updates;" onclick="window.location = '/smu/index.php';" />
						<span class="l" /><span class="r" />
					</div>
				</div>
			</td>
		</tr> -->
	</xsl:template>

	<xsl:template match="result[@module = 'autoupdate' and @method = 'patches']/data">
		<script>
			function get(type, repository, id, link) {
				jQuery.ajax({
					type:		"GET",
					url:		"/admin/autoupdate/getDiff/.xml",
					data:		{type: type, repository: repository, id: id, link: link},
					dataType:	"xml",
					success:	function(data) {
									var response = data.getElementsByTagName('response')[0];
									jQuery.jGrowl('<p>'+response.getAttribute('message')+'</p>', {
						'header': 'UMI.CMS',
						'life': 10000
					});
									if (response.getAttribute('code') == "ok") {
				switch(type) {
					case "apply": {
						jQuery('#'+ id +'.apply').switchClass("apply", "revert");
						var action = "get('revert', '" + repository +"', '"+ id + "', '"+ link + "')";
						var onclick = document.getElementsByClassName(id)[0];
						jQuery('.' + id).attr("value", "&label-diff-revert;");
						onclick.setAttribute("onclick", action);
					}
					break;
					case "revert": {
						jQuery('#'+ id +'.revert').switchClass("revert", "apply");
						var action = "get('apply', '" + repository +"', '"+ id + "', '"+ link + "')";
						var onclick = document.getElementsByClassName(id)[0];
						jQuery('.' + id).attr("value", "&label-diff-apply;");
						onclick.setAttribute("onclick", action);
					}
					break;
				};
									}
								}
				});
				return false;
			}
		</script>
		<xsl:apply-templates select="items" mode="patches"/>
	</xsl:template>

	<xsl:template match="items" mode="patches">
		<p><xsl:apply-templates select="../caution" mode="patches"/></p>
		<table class="tablePatches">
			<tbody>
				<tr class="glow">
					<td style="width:9%;"><strong>&label-diff-id;</strong></td>
					<td style="width:11%;"><strong>&label-diff-version;</strong></td>
					<td style="width:5%;"><strong>&label-diff-revision;</strong></td>
					<td style="width:5%;"><strong>&label-diff-repository;</strong></td>
					<td style="width:60%;"><strong>&label-diff-description;</strong></td>
					<td style="width:10%;"><strong></strong></td>
				</tr>
				<xsl:apply-templates select="item" mode="patches"/>
			</tbody>
		</table>
		<p>&label-diff-hub; <a href="http://hub.umi-cms.ru" target="_blank">UMI.Hub</a></p>
	</xsl:template>

	<xsl:template match="items[not(item)]" mode="patches">
		<table class="tablePatches">
			<tbody>
				<tr>
					<td>&label-diff-noitems;</td>
				</tr>
			</tbody>
		</table>
	</xsl:template>

	<xsl:template match="item" mode="patches">
		<xsl:variable name="applied" select="contains(../../applied, id)" />

		<tr>
			<xsl:if test="(position() mod 2 != 1)">
				<xsl:attribute name="class">glow</xsl:attribute>
			</xsl:if>
			<td>#<strong><xsl:value-of select="id" /></strong></td>
			<td><xsl:value-of select="version" /></td>
			<td><xsl:value-of select="revision" /></td>
			<td><xsl:apply-templates select="repository" mode="patches" /></td>
			<td><xsl:value-of select="description" /></td>
			<td>
				<div class="buttons">
					<div>
						<xsl:choose>
							<xsl:when test="$applied">
								<xsl:attribute name="id"><xsl:value-of select="id" /></xsl:attribute>
								<xsl:attribute name="class">revert</xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:attribute name="id"><xsl:value-of select="id" /></xsl:attribute>
								<xsl:attribute name="class">apply</xsl:attribute>
							</xsl:otherwise>
						</xsl:choose>
						<input type="button">
							<xsl:choose>
								<xsl:when test="$applied">
									<xsl:attribute name="class"><xsl:value-of select="id" /></xsl:attribute>
									<xsl:attribute name="value">&label-diff-revert;</xsl:attribute>
									<xsl:attribute name="onclick">get('revert', '<xsl:value-of select="repository" />', '<xsl:value-of select="id" />', '<xsl:value-of select="link" />'); return false;</xsl:attribute>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="class"><xsl:value-of select="id" /></xsl:attribute>
									<xsl:attribute name="value">&label-diff-apply;</xsl:attribute>
									<xsl:attribute name="onclick">get('apply', '<xsl:value-of select="repository" />', '<xsl:value-of select="id" />', '<xsl:value-of select="link" />'); return false;</xsl:attribute>
								</xsl:otherwise>
							</xsl:choose>
						</input>
						<span class="l" />
						<span class="r" />
					</div>
				</div>
			</td>
		</tr>

	</xsl:template>

	<xsl:template match="repository[.='native']" mode="patches">
		&label-diff-from-native;
	</xsl:template>

	<xsl:template match="repository[.='community']" mode="patches">
		&label-diff-from-community;
	</xsl:template>

	<xsl:template match="caution[.='all']" mode="patches">
		&label-diff-caution-all;
	</xsl:template>

	<xsl:template match="caution" mode="patches">
		&label-diff-caution-normal;
	</xsl:template>

</xsl:stylesheet>
