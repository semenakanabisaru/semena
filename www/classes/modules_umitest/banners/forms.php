<?php

$FORMS = Array();

$FORMS['banners_list'] = <<<BANSLIST

	<imgButton>
		<title><![CDATA[Добавить баннер]]></title>
		<src>/images/cms/admin/%skin_path%/ico_add.%ico_ext%</src>
		<link>%pre_lang%/admin/banners/banner_add/</link>
	</imgButton>
	<br /><br />

	%pages%

	<tablegroup>
		<header>
			<hcol style="text-align: left">Имя баннера</hcol>
			<hcol style="width: 100px">Активность</hcol>
			<hcol style="width: 100px">Изменить</hcol>
			<hcol style="width: 100px">Удалить</hcol>
		</header>
	%rows%
	</tablegroup>

BANSLIST;

$FORMS['banners_list_row'] = <<<BANROW
	<row>
		<col>
			<a href="%pre_lang%/admin/banners/banner_edit/%banner_id%/"><b><![CDATA[%banner_name%]]></b></a> %core getTypeEditLink(%object_type_id%)%
			<br/><br />
			<table border="0">
			<tr>
				<td style="width: 250px;">
					<![CDATA[Расположение на странице:]]>
				</td>
				<td>
					%banner_places%
				</td>
			</tr>
			<tr>
				<td>
					<![CDATA[Максимальное количество показов:]]>
				</td>
				<td>
					%banner_max_views%
				</td>
			</tr>
			<tr>
				<td>
					<![CDATA[Дата начала показа:]]>
				</td>
				<td>
					<![CDATA[%banner_show_start%]]>
				</td>
			</tr>
			<tr>
				<td>
					<![CDATA[Дата окончания показа:]]>
				</td>
				<td>
					%banner_show_till%
				</td>
			</tr>
			<tr>
				<td>
					<![CDATA[Количество показов:]]>
				</td>
				<td>
					<![CDATA[%banner_views%]]>
				</td>
			</tr>
			<tr>
				<td>
					<![CDATA[Количество переходов:]]>
				</td>
				<td>
					<![CDATA[%banner_clicks%]]>
				</td>
			</tr>
			<tr>
				<td>
					<![CDATA[Комментарий:]]>
				</td>
				<td>
					<![CDATA[%banner_desc%]]>
				</td>
			</tr>
			</table>
		</col>
	
		<col style="text-align: center;">
			%blocking%
		</col>

		<col style="text-align: center;">
			<a href="%pre_lang%/admin/banners/banner_edit/%banner_id%/"><img src="/images/cms/admin/%skin_path%/ico_edit.%ico_ext%" alt="Редактировать" title="Редактировать" /></a>
		</col>


		<col style="text-align: center;">
			<a href="%pre_lang%/admin/banners/banner_del/%banner_id%/" commit_unrestorable="Вы уверены?"><img src="/images/cms/admin/%skin_path%/ico_del.%ico_ext%" alt="Удалить" title="Удалить" /></a>
		</col>
	</row>
BANROW;

$FORMS['banner_edit'] = <<<EDTBAN
	<script type="text/javascript">
	<![CDATA[
		cifi_upload_text = '%banners_cifi_upload_text%';
	]]>
	</script>

	<form method="post" name="banner_edt_frm" enctype="multipart/form-data" action="%pre_lang%/admin/banners/%method%/">
		<setgroup name="Основные свойства" id="banner_edit_common" form="no">
			<table border="0" width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td width="50%">
						<input br="yes" quant="no" size="58" style="width:355px">
							<id><![CDATA[pname]]></id>
							<name><![CDATA[banner_name]]></name>
							<title><![CDATA[Название]]></title>
							<value><![CDATA[%banner_name%]]></value>
						</input>
					</td>

					<td width="50%">
						<input br="yes" quant="no" size="58" style="width:355px">
							<name><![CDATA[banner_tags]]></name>
							<title><![CDATA[Показывать на страницах с тэгами]]></title>
							<value><![CDATA[%banner_tags%]]></value>
						</input>
					</td>
				</tr>
				<tr>
					<td width="50%">
						<select quant="no" br="yes" style="width: 375px;">
							<name><![CDATA[banner_type]]></name>
							<title><![CDATA[Тип]]></title>
							%banner_types%
						</select>
					</td>
					<td>
						<input br="yes" quant="no" size="15" style="width:355px">
							<name><![CDATA[banner_user_tags]]></name>
							<title><![CDATA[Показывать пользователям с тэгами]]></title>
							<value><![CDATA[%banner_user_tags%]]></value>
						</input>
					</td>

				</tr>
				<tr>
					<td colspan="2">
						<input br="yes" quant="no" size="58" style="width:355px">
							<name><![CDATA[banner_url]]></name>
							<title><![CDATA[URL ссылки]]></title>
							<value><![CDATA[%banner_url%]]></value>
						</input>
					</td>
				</tr>
				<tr>
					<td width="50%">
						<p />
						<checkbox selected="%banner_active%">
							<name><![CDATA[banner_active]]></name>
							<title><![CDATA[Активен]]></title>
							<value><![CDATA[1]]></value>
						</checkbox>

					</td>

					<td>
						<p />
						<checkbox selected="%is_new_window%">
							<name><![CDATA[is_new_window]]></name>
							<title><![CDATA[Открывать ссылку в новом окне]]></title>
							<value><![CDATA[1]]></value>
						</checkbox>
					</td>
				</tr>
				<tr>
					<td width="50%">
					</td>
					<td />
				</tr>
				<tr>
					<td colspan="2">
						<p />
						Комментарии<br />
						<textarea name="banner_desc" style="width: 99%; height: 56px;"><![CDATA[%banner_desc%]]></textarea>
					</td>
				</tr>
			</table>
			<p align="right">%control_bar%</p>
		</setgroup>

		<setgroup name="Параметры показа" id="banner_edit_vparams" form="no">
			<table border="0" width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td width="50%">
						<input br="yes" quant="no" size="15" style="width:355px">
							<name><![CDATA[banner_views]]></name>
							<title><![CDATA[Количество показов]]></title>
							<value><![CDATA[%banner_views%]]></value>
						</input>
					</td>
					<td>
						<input br="yes" quant="no" size="15" style="width:355px">
							<name><![CDATA[banner_show_start]]></name>
							<title><![CDATA[Дата начала показа]]></title>
							<value><![CDATA[%banner_show_start%]]></value>
						</input>
					</td>
				</tr>
				<tr>
					<td>
						<input br="yes" quant="no" size="15" style="width:355px">
							<id><![CDATA[banner_maxviews]]></id>
							<name><![CDATA[banner_maxviews]]></name>
							<title><![CDATA[Максимальное количество показов]]></title>
							<value><![CDATA[%banner_maxviews%]]></value>
						</input>
					</td>
					<td>
						<input br="yes" quant="no" size="15" style="width:355px">
							<name><![CDATA[banner_show_till]]></name>
							<title><![CDATA[Дата окончания показа]]></title>
							<value><![CDATA[%banner_show_till%]]></value>
						</input>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<input br="yes" quant="no" size="15" style="width:355px">
							<name><![CDATA[banner_clicks]]></name>
							<title><![CDATA[Количество переходов]]></title>
							<value><![CDATA[%banner_clicks%]]></value>
						</input>
					</td>
				</tr>
			</table>
			<p align="right">%control_bar%</p>
		</setgroup>

		%data_field_groups%

		<passthru name="after_save_act"></passthru>

	</form>

%backup_panel%

	<script type="text/javascript">
		<![CDATA[
			function edtWithExit() {
				document.forms['banner_edt_frm'].after_save_act.value = "exit";
				return acf_check(1);
			}

			function edtWithEdit() {
				document.forms['banner_edt_frm'].after_save_act.value = "edit";
				return acf_check(1);
			}
			function edtCancel() {
				if(confirm("Вы уверены, что хотите выйти? Все изменения будут потеряны")) {
					redirect_str = "%pre_lang%/admin/banners/";
					if(redirect_str) {
						window.location = redirect_str;
					}
				}
				return false;
			}
			]]>
			
			function h () {
				oEdtFrm = document.forms['banner_edt_frm'];
				var hh = (oEdtFrm.onsubmit) ? oEdtFrm.onsubmit : function () {};
				oEdtFrm.onsubmit = function () { hh(); acf_check(); };
			}
			addOnLoadEvent(h);
			
			acf_inputs_test[acf_inputs_test.length] = Array('pname', 'Enter banner name');
	</script>


EDTBAN;

$FORMS['lists_place'] = <<<END

	<imgButton>
		<title><![CDATA[Добавить расположение]]></title>
		<src>/images/cms/admin/%skin_path%/ico_add.%ico_ext%</src>
		<link>%pre_lang%/admin/banners/place_add/</link>
	</imgButton>

	<br /><br />

	<tablegroup>
		<header>
			<hcol style="text-align: left">Место</hcol>
			<hcol style="width: 100px">Изменить</hcol>
			<hcol style="width: 100px">Удалить</hcol>
		</header>
		%rows%
	</tablegroup>
END;

$FORMS['banners_place_row'] = <<<BANROW
	<row>
		<col>
			<a href="%pre_lang%/admin/banners/place_edit/%place_id%/"><b><![CDATA[%place_name%]]></b></a>
			<br /><br />
			Описание	: <![CDATA[%place_dsc%]]><br />
		</col>

		<col style="text-align: center;">
			<a href="%pre_lang%/admin/banners/place_edit/%place_id%/"><img src="/images/cms/admin/%skin_path%/ico_edit.%ico_ext%" alt="Редактировать" title="Редактировать" /></a>
		</col>


		<col style="text-align: center;">
			<a href="%pre_lang%/admin/banners/place_del/%place_id%/" commit_unrestorable="Вы уверены?"><img src="/images/cms/admin/%skin_path%/ico_del.%ico_ext%" alt="Удалить" title="Удалить" /></a>
		</col>
	</row>
BANROW;

$FORMS['place_edit'] = <<<END

	<form method="post" name="place_edt_frm" enctype="multipart/form-data" action="%pre_lang%/admin/banners/%method%/">
		<setgroup name="Основные свойства" id="banner_edit_vparams" form="no">
			<table border="0" width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td width="50%">
						<input br="yes" quant="no" size="58" style="width:355px">
							<id><![CDATA[pname]]></id>
							<name><![CDATA[place_name]]></name>
							<title><![CDATA[Идентификатор в шаблоне]]></title>
							<value><![CDATA[%place_name%]]></value>
						</input>
					</td>

					<td width="50%">
						<input br="yes" quant="no" size="58" style="width:355px">
							<id><![CDATA[place_desc]]></id>
							<name><![CDATA[place_desc]]></name>
							<title><![CDATA[Описание]]></title>
							<value><![CDATA[%place_desc%]]></value>
						</input>
					</td>
				</tr>
				<tr>
					<td width="50%" colspan="2">
						<br />
						<checkbox selected="%place_show_rand%">
								<name><![CDATA[place_show_rand]]></name>
								<title><![CDATA[Показ случайного баннера]]></title>
								<value><![CDATA[1]]></value>
						</checkbox>
					</td>
				</tr>
			</table>
			<p align="right">%control_bar%</p>
		</setgroup>

		<passthru name="after_save_act"></passthru>
	</form>

	<script type="text/javascript">
		<![CDATA[
			function edtWithExit() {
				document.forms['place_edt_frm'].after_save_act.value = "exit";
				return acf_check(1);
			}

			function edtWithEdit() {
				document.forms['place_edt_frm'].after_save_act.value = "edit";
				return acf_check(1);
			}
			function edtCancel() {
				if(confirm("Вы уверены, что хотите выйти? Все изменения будут потеряны")) {
					redirect_str = "%pre_lang%/admin/banners/places_list";
					if(redirect_str) {
						window.location = redirect_str;
					}
				}
				return false;
			}
			]]>
			
			var h = function () {
				oEdtFrm = document.forms['place_edt_frm'];
				var hh = (oEdtFrm.onsubmit) ? oEdtFrm.onsubmit : function () {};
				oEdtFrm.onsubmit = function () { hh(); acf_check(); };
			}
			addOnLoadEvent(h);
			acf_inputs_test[acf_inputs_test.length] = Array('pname', 'Enter place name');
	</script>
END;


?>