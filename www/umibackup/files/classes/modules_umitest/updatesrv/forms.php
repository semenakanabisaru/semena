<?php

/*
$FORMS['config'] = <<<END

<form method="post" action="%pre_lang%/admin/updatesrv/config_do/">

<tablegroup>

	<row>
		<col style="width: 50%">Сервер обновлений включен:</col>
		<col>
			<checkbox name="is_enabled" value="1">%is_enabled%</checkbox>
		</col>
	</row>


	<row>
		<col>Самостоятельно опрашивать сайты:</col>
		<col>
			<checkbox name="allow_autowalk" value="1">%allow_autowalk%</checkbox>
		</col>
	</row>

	<row>
		<col>Разрешить входящие запросы:</col>
		<col>
			<checkbox name="allow_incoming" value="1">%allow_incoming%</checkbox>
		</col>
	</row>

</tablegroup>

<p><submit title="Сохранить" /></p>

</form>


END;
*/

$FORMS['licenses'] = <<<END
<form method="post" action="%pre_lang%/admin/updatesrv/licenses/">
<tinytable>
	<col width="200">
		<imgButton>
			<link><![CDATA[%pre_lang%/admin/updatesrv/license_add/]]></link>
			<title><![CDATA[Создать лицензию]]></title>
			<src><![CDATA[/images/cms/admin/%skin_path%/ico_add.%ico_ext%]]></src>
		</imgButton>
	</col>

	<col>
		<middeled>
			<mcol width="150">
				<input   br="yes">
					<name><![CDATA[filter_domain]]></name>
					<title><![CDATA[По домену]]></title>
					<value><![CDATA[%filter_domain%]]></value>
				</input>
				
				<input   br="yes">
					<name><![CDATA[filter_domain_key]]></name>
					<title><![CDATA[По доменному ключу]]></title>
					<value><![CDATA[%filter_domain_key%]]></value>
				</input>

				<select   br="yes" style="width: 150px;">
					<name><![CDATA[filter_license_owner]]></name>
					<title><![CDATA[Кому выдана лицензия]]></title>
					%license_owners%
				</select>
			</mcol>

			<mcol width="150">
				<input   br="yes">
					<name><![CDATA[filter_ip]]></name>
					<title><![CDATA[По IP]]></title>
					<value><![CDATA[%filter_ip%]]></value>
				</input>


				<input   br="yes">
					<name><![CDATA[filter_email]]></name>
					<title><![CDATA[По email'у]]></title>
					<value><![CDATA[%filter_email%]]></value>
				</input>

				<select   br="yes" style="width: 150px;">
					<name><![CDATA[filter_site_subject]]></name>
					<title><![CDATA[По тематике]]></title>
					%site_subjects%
				</select>
			</mcol>

			<mcol width="150">
				<input   br="yes">
					<name><![CDATA[filter_keycode]]></name>
					<title><![CDATA[По ключу]]></title>
					<value><![CDATA[%filter_keycode%]]></value>
				</input>
				

				<input   br="yes">
					<name><![CDATA[filter_fname]]></name>
					<title><![CDATA[По имени]]></title>
					<value><![CDATA[%filter_fname%]]></value>
				</input>
				
				<br /><br /><br /><br />
			</mcol>

			<mcol width="150">
				<select   br="yes">
					<name><![CDATA[filter_license_type]]></name>
					<title><![CDATA[По типу лицензии]]></title>
					%licenses%
				</select>

				<input   br="yes">
					<name><![CDATA[filter_lname]]></name>
					<title><![CDATA[По фамилии]]></title>
					<value><![CDATA[%filter_lname%]]></value>
				</input>
				
				<br /><br /><br /><br />
			</mcol>

			<mcol width="60">
				<br />
				<submit title="Отфильтровать" />
			</mcol>
		</middeled>
	</col>
</tinytable>
</form>

<p />

%pages%
<tablegroup>
	<hrow>
		<hcol style="width: 30px;">
			#
		</hcol>

		<hcol>
			Информация о лицензии
		</hcol>

		<hcol style="width: 90px;">
			Скриншот
		</hcol>

		<hcol style="width: 100px;">
			Изменить
		</hcol>


		<hcol style="width: 100px;">
			Удалить
		</hcol>

	</hrow>

	%rows%
</tablegroup>

END;

$FORMS['license_add'] = <<<END

<script type="text/javascript">
<![CDATA[
	function init_me() {
		df = document.forms['adding_new_page'];
		def_value = df.name.value;
		def_alt = df.alt_name.value;
	}

	function save_with_exit() {
		document.forms['adding_new_page'].exit_after_save.value = "1";
		return acf_check(1);
	}

	function save_without_exit() {
		document.forms['adding_new_page'].exit_after_save.value = "0";
		return acf_check(1);
	}

	frm = document.forms['adding_new_page'];
	frm.onsubmit = acf_check;

	cifi_upload_text = '%catalog_cifi_upload_text%';
]]>
</script>

<form method="post" name="adding_new_page" action="%pre_lang%/admin/updatesrv/%method%/%license_id%">
	%data_field_groups%
	<passthru name="exit_after_save"></passthru>
</form>

END;

?>