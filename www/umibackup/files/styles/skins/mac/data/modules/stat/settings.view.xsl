<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/stat">[
	<!ENTITY sys-module 'stat'>
]>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<xsl:template match="/result[@method='clear']/data[@type = 'settings' and @action = 'view']">
		<script type="text/javascript" language="javascript">
		<![CDATA[
			function ClearButtonClick () {
				var callback = function () {
					window.location.href = "]]><xsl:value-of select="$lang-prefix"/>/admin/&sys-module;/clear/do<![CDATA[";
				};

				openDialog({
					title       : "]]>&label-stat-clear;<![CDATA[",
					text        : "]]>&label-stat-clear-confirm;<![CDATA[",
					OKText      : "]]>&label-clear;<![CDATA[",
					cancelText  : "]]>&label-cancel;<![CDATA[",
					OKCallback	: callback
				});

				return false;
			}
		]]>
		</script>
		<div class="panel">
			<div class="header" onclick="panelSwitcher(this);">
				<span />
				<div class="l" /><div class="r" />
			</div>
			<div class="content">
				&label-stat-clear-help;
				<div class="buttons">
					<div>
						<input type="button" value="&label-stat-clear;" onclick="javascript:ClearButtonClick();" />
						<span class="l" />
						<span class="r" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[not(@method='clear')]/data[@type = 'settings' and @action = 'view']">
		<div id="stat_links">
			<xsl:apply-templates select="/result[@module = 'stat']" mode="stat_links" />
		</div>
		<xsl:apply-templates select="group[@name = 'filter']" mode="settings-view"/>
		<xsl:apply-templates select="group[not(@name = 'filter')]" mode="settings-view"/>
	</xsl:template>

	<xsl:template match="group[@name = 'filter']" mode="settings-view">
		<form method="post">
			<table id="statFilter" class="tableContent">
				<tr>
					<th><xsl:text>&label-domain;</xsl:text></th>
					<th><xsl:text>&label-period-start;</xsl:text></th>
					<th><xsl:text>&label-period-end;</xsl:text></th>
				</tr>
				<tr>
					<td>
						<xsl:apply-templates select="option[@type='domain']" mode="settings-view"/>
					</td>
					<td>
						<xsl:apply-templates select="option[@type='period' and @name='start']" mode="settings-view"/>
					</td>
					<td>
						<xsl:apply-templates select="option[@type='period' and @name='end']" mode="settings-view"/>
					</td>
				</tr>
				<tr>
					<td><strong><xsl:text>&label-users;</xsl:text></strong></td>
					<td>
						<xsl:apply-templates select="option[@type='users' and @name='user']" mode="settings-view"/>
					</td>
					<td><xsl:text>&nbsp;</xsl:text></td>
				</tr>
			</table>
			<div class="buttons">
				<div>
					<input type="submit" value="&label-apply-filter;" />
					<span class="l" />
					<span class="r" />
				</div>
			</div>
		</form>
	</xsl:template>

	<xsl:template match="group[not(@name = 'filter')]" mode="settings-view">
		<div class="panel">
			<div class="header" onclick="panelSwitcher(this);">
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
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group[not(@name = 'filter') and ./option/@type = 'flash']" mode="settings-view">
		  <xsl:apply-templates select="option" mode="settings-view"/>
	</xsl:template>

	<xsl:template match="option[@type = 'flash']" mode="settings-view">
		<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
				codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0"
				width="100%" height="780" align="middle">
			<param name="allowScriptAccess" value="sameDomain"/>
			<param name="movie" value="">
				<xsl:attribute disable-output-escaping="yes" name="value">
					/images/cms/stat/Chart.swf?<xsl:value-of select="value/node()"/><![CDATA[&]]>ilang=<xsl:value-of select="/result/@interface-lang" />
				</xsl:attribute>
			</param>
			<param name="quality" value="high"/>
			<param name="bgcolor" value="#ffffff"/>
			<param name="wmode" value="transparent"/>
			<param name="FlashVars">
				<xsl:attribute disable-output-escaping="yes" name="FlashVars">
					<xsl:value-of select="value/node()"/><![CDATA[&]]>ilang=<xsl:value-of select="/result/@interface-lang" />
				</xsl:attribute>
			</param>
			<embed quality="high" bgcolor="#ffffff" width="100%" height="780"
				   name="report_graph" align="middle" allowScriptAccess="sameDomain"
				   type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"
				   wmode="transparent">
				<xsl:attribute disable-output-escaping="yes" name="src">
					/images/cms/stat/Chart.swf?amp;ilang=<xsl:value-of select="/result/@interface-lang" />
				</xsl:attribute>
				<xsl:attribute name="FlashVars" disable-output-escaping="yes">
					<xsl:value-of select="value/node()"/><![CDATA[&]]>ilang=<xsl:value-of select="/result/@interface-lang" />
				</xsl:attribute>
			</embed>
		</object>
	</xsl:template>

	<xsl:template match="option[@type = 'tags']" mode="settings.view">
		<tr>
			<td style="text-align:center;">
				<xsl:apply-templates select="value/tag"/>
				<xsl:apply-templates select="value/message"/>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="option[@type = 'domain']" mode="settings-view">
		<select name="{@name}" id="{@name}" style="width:100%">
			<xsl:apply-templates select="value/item">
				<xsl:with-param name="value" select="value/@id"/>
			</xsl:apply-templates>
		</select>
	</xsl:template>

	<xsl:template match="option[@type = 'period']" mode="settings-view">
		<select name="{@name}_day" id="{@name}_day" class="date">
			<xsl:apply-templates select="value/entity[@type='day']/item">
				<xsl:with-param name="value" select="value/entity[@type='day']/@id"/>
			</xsl:apply-templates>
		</select>
		<select name="{@name}_month" id="{@name}_month" class="date">
			<xsl:apply-templates select="value/entity[@type='month']/item">
				<xsl:with-param name="value" select="value/entity[@type='month']/@id"/>
			</xsl:apply-templates>
		</select>
		<select name="{@name}_year" id="{@name}_year" class="date">
			<xsl:apply-templates select="value/entity[@type='year']/item">
				<xsl:with-param name="value" select="value/entity[@type='year']/@id"/>
			</xsl:apply-templates>
		</select>
	</xsl:template>

	<xsl:template match="option[@type = 'users']" mode="settings-view">
		<select name="{@name}" id="{@name}" style="width:100%">
			<xsl:apply-templates select="value/item">
				<xsl:with-param name="value" select="value/@id"/>
			</xsl:apply-templates>
		</select>
	</xsl:template>

	<xsl:template match="item">
		<xsl:param name="value"/>
		<option value="{@id}">
			<xsl:if test="$value = @id">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="."/>
		</option>
	</xsl:template>

	<xsl:template match="tag">
		<a href="{$lang-prefix}/admin/stat/tag/{@id}/">
			<span style="font-size: {@fontweight}px">
				<xsl:value-of select="text()"/>
				<xsl:text>(</xsl:text>
				<xsl:value-of select="@weight"/>
				<xsl:text>%)</xsl:text>
			</span>
		</a>
		<xsl:text>&nbsp;</xsl:text>
	</xsl:template>

	<xsl:template match="message">
		<xsl:value-of select="." disable-output-escaping="yes"/>
	</xsl:template>

	<xsl:template match="result" mode="stat_links" />

	<xsl:template match="result[@method = 'popular_pages' or @method = 'sectionHits']" mode="stat_links">
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'popular_pages'" />
			<xsl:with-param name="label" select="'&menu-pages;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'sectionHits'" />
			<xsl:with-param name="label" select="'&menu-sections;'" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="result[
		@method = 'visits' or
		@method = 'visits_sessions' or
		@method = 'visits_visitors' or
		@method = 'auditoryActivity' or
		@method = 'auditoryLoyality' or
		@method = 'auditoryLocation' or
		@method = 'visitDeep' or
		@method = 'visitTime'
	]" mode="stat_links">
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'visits'" />
			<xsl:with-param name="label" select="'&menu-hits;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'visits_sessions'" />
			<xsl:with-param name="label" select="'&menu-sessions;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'visits_visitors'" />
			<xsl:with-param name="label" select="'&menu-visitors;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'auditoryActivity'" />
			<xsl:with-param name="label" select="'&menu-auditory-activity;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'auditoryLoyality'" />
			<xsl:with-param name="label" select="'&menu-auditory-loyality;'" />
		</xsl:call-template>
		<xsl:if test="document('udata://config/menu')/udata/items/item[@name = 'geoip']">
			<xsl:call-template name="stat_links">
				<xsl:with-param name="link" select="'auditoryLocation'" />
				<xsl:with-param name="label" select="'&menu-auditory-location;'" />
			</xsl:call-template>
		</xsl:if>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'visitDeep'" />
			<xsl:with-param name="label" select="'&menu-visit-deep;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'visitTime'" />
			<xsl:with-param name="label" select="'&menu-visit-time;'" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="result[
		@method = 'sources' or
		@method = 'engines' or
		@method = 'phrases' or
		@method = 'entryPoints' or
		@method = 'exitPoints'
	]" mode="stat_links">
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'sources'" />
			<xsl:with-param name="label" select="'&menu-sources;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'engines'" />
			<xsl:with-param name="label" select="'&menu-engines;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'phrases'" />
			<xsl:with-param name="label" select="'&menu-phrases;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'entryPoints'" />
			<xsl:with-param name="label" select="'&menu-entry;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'exitPoints'" />
			<xsl:with-param name="label" select="'&menu-exit;'" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="result[
		@method = 'openstatCampaigns' or
		@method = 'openstatServices' or
		@method = 'openstatSources' or
		@method = 'openstatAds'
	]" mode="stat_links">
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'openstatCampaigns'" />
			<xsl:with-param name="label" select="'&menu-ostat-campaigns;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'openstatServices'" />
			<xsl:with-param name="label" select="'&menu-ostat-services;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'openstatSources'" />
			<xsl:with-param name="label" select="'&menu-ostat-sources;'" />
		</xsl:call-template>
		<xsl:call-template name="stat_links">
			<xsl:with-param name="link" select="'openstatAds'" />
			<xsl:with-param name="label" select="'&menu-ostat-ads;'" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="stat_links">
		<xsl:param name="link" />
		<xsl:param name="label" />
		<xsl:choose>
			<xsl:when test="@method = $link">
				<span><xsl:value-of select="$label" /></span>
			</xsl:when>
			<xsl:otherwise>
				<a href="{$lang-prefix}/admin/&sys-module;/{$link}/">
					<xsl:value-of select="$label" />
				</a>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="/result[@method='get_counters']/data[@type = 'settings' and @action = 'view']">
		<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<a id="addCounter" href="{$lang-prefix}/admin/stat/add_counter/" class="type_select_gray">
				<xsl:text>&label-counter-add;</xsl:text>
			</a>
		</div>

		<div class="clear" xmlns:umi="http://www.umi-cms.ru/TR/umi"></div>

		<div class="tableItemContainer">

			<div class="content" style="border:none;">
				<table class="table-container tableContent" id="yandex_table" style="margin-top:0;">
					<thead>
						<tr class="header-row">
							<th><xsl:text>&label-counter-name;</xsl:text></th>
							<th><xsl:text>&label-counter-site;</xsl:text></th>
							<th style="width:300px;"><xsl:text>&label-counter-status;</xsl:text></th>
							<th><xsl:text>&label-counter-edit;</xsl:text></th>
							<th><xsl:text>&label-counter-delete;</xsl:text></th>
						</tr>
					</thead>
					<tbody>
						<xsl:apply-templates select="//counter" mode="yandex" />
					</tbody>
				</table>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="counter" mode="yandex">
		<tr>
			<td>
				<a href="/admin/stat/view_counter/summary/common/{id}/">
					<xsl:if test="name = ''">
						<xsl:text>&label-counter-no-name;</xsl:text>
					</xsl:if>
					<xsl:if test="name != ''">
						<xsl:value-of select="name" />
					</xsl:if>
				</a>
			</td>
			<td><xsl:value-of select="site" /></td>
			<td class="center">
				<a href="/admin/stat/check_counter/{id}" id="{id}_{code_status}" title="&label-status-check;">
					<xsl:value-of select="code_status" />
				</a>
				<script>
					jQuery('#<xsl:value-of select="id" />_<xsl:value-of select="code_status" />').html(getLabel('js-label-status-<xsl:value-of select="code_status" />'));
				</script>
			</td>
			<td class="center" style="padding:0;">
				<a href="{$lang-prefix}/admin/stat/edit_counter/{id}">
					<img src="/images/cms/admin/mac/ico_edit.gif" title="&label-edit;" alt="&label-edit;" />
				</a>
			</td>
			<td class="center">
				<a class="delete unrestorable config_langs_btn" href="/admin/stat/delete_counter/{id}">
					<span>&label-counter-delete;</span>
				</a>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="/result[@method='add_counter']">

		<xsl:variable name="counter-name">
			<xsl:choose>
				<xsl:when test="count(//error/name)">
					<xsl:value-of select="//error/name"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text></xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="counter-site">
			<xsl:choose>
				<xsl:when test="count(//error/site)">
					<xsl:value-of select="//error/site"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text></xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div id="page">
			<div id="content" class="content-expanded">
				<div style="float:left; width:100%; _float:none;">
					<form enctype="multipart/form-data" action="do/" method="post" xmlns:php="http://php.net/xsl" xmlns:umi="http://www.umi-cms.ru/TR/umi">
						<div class="panel properties-group" name="g_common">
							<div class="header">
								<span class="c">&label-counter-new;</span>
								<div class="l"></div>
								<div class="r"></div>
							</div>
							<div class="content">
								<xsl:if test="count(//error)">
									<div style="color:red; margin-bottom:20px;"><xsl:value-of select="//error/text" disable-output-escaping="yes"/></div>
								</xsl:if>
								<div class="field">
									<label>
										<span class="label">
											<acronym class="acr" title="">&label-counter-name;</acronym>
										</span>
										<span>
											<input type="text" value="{$counter-name}" name="counter-name"/>
										</span>
									</label>
								</div>
								<div class="field">
									<label>
										<span class="label">
											<acronym class="acr" title="">&label-counter-site;</acronym>
										</span>
										<span>
											<input type="text" value="{$counter-site}" name="counter-site"/>
										</span>
									</label>
								</div>
								<div class="buttons">
									<div>
										<input type="submit" name="save-mode" value="&label-counter-add;"/>
										<span class="l"></span>
										<span class="r"></span>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method='edit_counter']">

		<div id="page">
			<div id="content" class="content-expanded">
				<div style="float:left; width:100%; _float:none;">
					<form enctype="multipart/form-data" action="do/" method="post" xmlns:php="http://php.net/xsl" xmlns:umi="http://www.umi-cms.ru/TR/umi">
						<div class="panel properties-group" name="g_common">
							<div class="header">
								<span class="c">&label-counter;</span>
								<div class="l"></div>
								<div class="r"></div>
							</div>
							<div class="content">
								<xsl:if test="count(//error)">
									<div style="color:red; margin-bottom:20px;"><xsl:value-of select="//error/text" disable-output-escaping="yes"/></div>
								</xsl:if>
								<div class="field">
									<label>
										<span class="label">
											<acronym class="acr" title="">&label-counter-name;</acronym>
										</span>
										<span>
											<input type="text" value="{//counter/name}" name="counter-name"/>
										</span>
									</label>
								</div>
								<div class="field">
									<label>
										<span class="label">
											<acronym class="acr" title="">&label-counter-site;</acronym>
										</span>
										<span>
											<input type="text" value="{//counter/site}" name="counter-site"/>
										</span>
									</label>
								</div>
								<div class="field text">
									<label>
										<span class="label">
											<acronym class="acr" title="">&label-counter-code;</acronym>
										</span>
										<textarea class="text" name="counter-code"><xsl:value-of select="//counter/code"/></textarea>
									</label>
								</div>
								<div class="buttons">
									<div>
										<input type="submit" name="save-mode" value="&label-counter-save;"/>
										<span class="l"></span>
										<span class="r"></span>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</xsl:template>


	<xsl:template match="/result[@method='view_counter']/data[@type = 'settings' and @action = 'view']">

		<xsl:variable name="counterId" select="//counter" />

		<xsl:variable name="section">
			<xsl:choose>
				<xsl:when test="not(//sections/section[@selected = 1]/@name)">
					<xsl:value-of select="//sections/section[@default = 1]/@name" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="//sections/section[@selected = 1]/@name" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="report">
			<xsl:choose>
				<xsl:when test="not(//reports/report[@selected = 1]/@name)">
					<xsl:value-of select="//sections/section[@name = $section]/reports/report[@default = 1]/@name" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="//reports/report[@selected = 1]/@name" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="chartType" select="//sections/section[@name = $section]/reports/report[@name = $report]/@graph"/>
		<xsl:variable name="mainColumn" select="//sections/section[@name = $section]/reports/report[@name = $report]/@order-by"/>

		<xsl:variable name="filter">
			<xsl:choose>
				<xsl:when test="not(//filters/filter[@selected = 1]/@name)">
					<xsl:value-of select="//sections/section[@name = $section]/reports/report[@name = $report]/filters/filter[@default = 1]/@name" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="//filters/filter[@selected = 1]/@name" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<script type="text/javascript" language="javascript">

			<![CDATA[

					google.load("visualization", "1", {packages:["corechart", "table", "geomap"]});
					google.setOnLoadCallback(function() {
						drawChart(']]><xsl:value-of select="$section"/><![CDATA[', ']]><xsl:value-of select="$report"/><![CDATA[', ']]><xsl:value-of select="$filter"/><![CDATA[', ']]><xsl:value-of select="$counterId"/><![CDATA[',']]><xsl:value-of select="$mainColumn"/><![CDATA[', ']]><xsl:value-of select="$chartType"/><![CDATA[', 'chart', true);
					});

					function drawChart(section, report, filter, counter, ordercolumn, graph, container, loadTable) {

						var container = container || 'chart';
						var loadTable = loadTable || false;

						var date1 = jQuery('#date1').attr('value');
						var date2 = jQuery('#date2').attr('value');

						jQuery("#charts_submenu a").removeClass('act');
						jQuery("#" + section + '_' + report).addClass('act');

						jQuery(".filter_submenu a").removeClass('act');
						jQuery("#" + filter).addClass('act');

						jQuery("#filter_date").unbind('click');
						jQuery("#filter_date").click(function() {
							drawChart(section, report, filter, counter, ordercolumn, graph, 'chart', true);
							return false;
						});

						jQuery("#chart").ajaxStart(function(){
							jQuery(this).html('<img src="/images/cms/admin/mac/ajax_loader.gif" alt="Loading..." style="margin-top:155px;"/>');
						 });

						 $('#charts a').each(function() {
							jQuery(this).unbind('click');
							jQuery(this).click(function() {
								drawChart(section, report, filter, counter, ordercolumn, jQuery(this).attr('id'), 'chart', false);
								return false;
							});
						});

						var maincolumn = ordercolumn;
						if (section == 'geo' && graph != 'GeoMap') maincolumn = 'name';

						jQuery.ajax({
							type: "GET",
							url: "/admin/stat/view_counter_json/?" + "section=" + section +"&report=" + report + "&filter=" + filter  + "&counter=" + counter + "&order=" + maincolumn + "&date1=" +date1 + "&date2=" + date2 + "&rrr=" + Math.random(),
							dataType: "json",
							success: function(json){

								jQuery(".errors").remove();

								jQuery("#date1").attr('value', json.date1);
								jQuery("#date2").attr('value', json.date2);

								if (json.errors !== undefined) {
									jQuery("#chart").html('');
									for (var i = 0; i < json.errors.length; i++) {

										jQuery('<div style="color:red;" class="errors">' + json.errors[i].text + '</div>').insertBefore('#chart');
									}
									return;
								}

								var data = new google.visualization.DataTable();

								var result = json.data;

								var maincolumnAdded = false;
								var filtercolumnAdded = false;

								var rows = [];
								for (var i = 0; i < result.length; i++) {
									var row = result[i];

									var values = [];
									for (var column in row) {

										if(column != maincolumn && column != filter) continue;
										if(column == maincolumn && row[filter] === undefined) {
											continue;
										}

										if (row[filter] !== undefined && row[maincolumn] !== undefined) {
											if (maincolumnAdded === false && column == maincolumn) {
												data.addColumn('string', getLabel('js-label-counter-' + column));
												maincolumnAdded = true;
											} else if (filtercolumnAdded === false && column == filter) {
												data.addColumn('number', getLabel('js-label-counter-' + column));
												if (maincolumn == 'en_name' && graph=='GeoMap') {
													data.addColumn('string', 'HOVER', 'HoverText');
												}
												filtercolumnAdded = true;
											}
										}

										if(row[column] !== null) {
											values.push(row[column]);
										} else {
											values.push(0);
										}
									}

									if(values.length) {
										if (maincolumn == 'en_name' && graph=='GeoMap') values.push(row['name']);
										rows.push(values);
									}
								}

								data.addRows(rows);
								var chart = new google.visualization[graph](document.getElementById(container));
								chart.draw(data, {width: 700, height: 340, title: getLabel('js-label-counter-' + section + '-' + report) + ' - ' + getLabel('js-label-counter-' + filter)});

								if (loadTable) {

									var tableData = new google.visualization.DataTable();
									var tablemaincolumn = maincolumn;

									for (var i = 0; i < result.length; i++) {
										var row = result[i];
										var columns = [];
										for (var column in row) {
											if (column == 'max_users_date' || column == 'max_rps_date' || column == 'id' || column == 'search_engines' || column == 'version') continue;
											if (column == 'en_name') {
												column = 'name';
												tablemaincolumn = 'name';
											}
											if (columns[column] === undefined) columns[column] = column;
										}
									}

									var rows = [];
									for (var i = 0; i < result.length; i++) {
										var row = result[i];

										var values = [];
										for (var column in columns) {

											if(row[column] !== null && row[column] !== undefined) {
												values.push(row[column]);
											} else {
												values.push(0);
											}
										}

										if(values.length) rows.push(values);
									}

									for (var column in columns) {
										if (column == tablemaincolumn || column == 'max_rps_time' || column == 'max_users_time') {
											tableData.addColumn('string', getLabel('js-label-counter-' + column));
										} else {
											tableData.addColumn('number', getLabel('js-label-counter-' + column));
										}
									}

									tableData.addRows(rows);

									var table = new google.visualization.Table(document.getElementById('tableChart'));
        							table.draw(tableData, {showRowNumber: false});

								}

							}

						});
					}

			]]>
		</script>



		<div id="page">
			<div id="content" class="content-expanded">
				<div style="float:left; width:100%; _float:none;">
					<div class="panel">
						<div class="tabs">

							<xsl:apply-templates select="sections/section" mode="yandex">
								<xsl:with-param name="counter" select="$counterId" />
								<xsl:with-param name="section" select="$section" />
							</xsl:apply-templates>

						</div>

						<div class="content">
							<div id="charts_submenu">
								<xsl:if test="count(sections/section[@name= $section]/reports/report) &gt; 1">
									<xsl:apply-templates select="sections/section[@name= $section]/reports/report" mode="yandex">
										<xsl:with-param name="section" select="$section" />
										<xsl:with-param name="counter" select="$counterId" />
									</xsl:apply-templates>
								</xsl:if>
							</div>
							<div style="height:70px; margin-top:20px;" id="dates">

								<div class="field datePicker">
									<label for="date1">
										<span class="label">
											<acronym>&label-counter-startdate;</acronym>
										</span>
										<span>
											<input id="date1" type="text" value="" name="date1" />
										</span>
									</label>
								</div>

								<div class="field datePicker">
									<label for="date2">
										<span class="label">
											<acronym>&label-counter-enddate;</acronym>
										</span>
										<span>
											<input id="date2" type="text" value="" name="date2" />
										</span>
									</label>
								</div>

								<div class="buttons">
									<div>
										<input type="button" value="&label-counter-filter;" id="filter_date" />
										<span class="l"></span>
										<span class="r"></span>
									</div>
								</div>
							</div>
							<div style="clear:both;"/>
							<div class="filter_submenu">
								<table>
									<tbody>
										<tr>
											<td>
												<div id="chart" />
											</td>
											<td>
												<div id="charts">
													<xsl:apply-templates select="sections/section[@name=$section]/reports/report[@name=$report]/charts/chart" mode="yandex"/>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
								<xsl:if test="count(sections/section[@name=$section]/reports/report[@name=$report]/filters/filter) &gt; 1">
									<xsl:apply-templates select="sections/section[@name=$section]/reports/report[@name=$report]/filters/filter" mode="yandex">
										<xsl:with-param name="section" select="$section" />
										<xsl:with-param name="report" select="$report" />
										<xsl:with-param name="counter" select="$counterId" />
										<xsl:with-param name="chartType" select="$chartType" />
										<xsl:with-param name="mainColumn" select="$mainColumn" />
									</xsl:apply-templates>
								</xsl:if>
								<div id="tableChart"/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="chart" mode="yandex">
		<a href="#" id="{@name}"><img src="/images/cms/admin/mac/{@name}" alt="{@name}"/></a>
	</xsl:template>

	<xsl:template match="filter" mode="yandex">
		<xsl:param name="counter" />
		<xsl:param name="section" />
		<xsl:param name="report" />
		<xsl:param name="chartType" />
		<xsl:param name="mainColumn" />
		<a href="/admin/stat/view_counter/{$section}/{$report}/{$counter}/?filter={@name}" id="{@name}" onclick="drawChart('{$section}', '{$report}', '{@name}', '{$counter}', '{$mainColumn}', '{$chartType}'); return false;" />
		<script>
			jQuery('#<xsl:value-of select="@name" />').html(getLabel('js-label-counter-<xsl:value-of select="@name" />'));
		</script>

	</xsl:template>

	<xsl:template match="report" mode="yandex">
		<xsl:param name="counter" />
		<xsl:param name="section" />

		<a href="/admin/stat/view_counter/{$section}/{@name}/{$counter}" id="{$section}_{@name}" />
		<script>
			jQuery('#<xsl:value-of select="$section" />_<xsl:value-of select="@name" />').html(getLabel('js-label-counter-<xsl:value-of select="$section" />-<xsl:value-of select="@name" />'));
		</script>

	</xsl:template>

	<xsl:template match="section" mode="yandex">
		<xsl:param name="counter" />
		<xsl:param name="section" />

		<a href="/admin/stat/view_counter/{@name}/{reports/report[@default = 1]/@name}/{$counter}" id="{@name}">
			<xsl:attribute name="class">
				<xsl:text>header</xsl:text>
				<xsl:if test="@name = $section"><xsl:text> act</xsl:text></xsl:if>
				<xsl:choose>
					<xsl:when test="position() = 1"><xsl:text> first</xsl:text></xsl:when>
					<xsl:when test="position() = last()"><xsl:text> last</xsl:text></xsl:when>
					<xsl:otherwise>
						<xsl:choose>
							<xsl:when test="@name = $section" />
							<xsl:when test="preceding-sibling::node()[@name = $section]"><xsl:text> next</xsl:text></xsl:when>
							<xsl:otherwise><xsl:text> prev</xsl:text></xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<span class="c" id="{generate-id()}" />
			<script>
				jQuery('#<xsl:value-of select="generate-id()" />').html(getLabel('js-label-counter-<xsl:value-of select="@name" />'));
			</script>
			<span class="l" /><span class="r" />
		</a>
	</xsl:template>

</xsl:stylesheet>
