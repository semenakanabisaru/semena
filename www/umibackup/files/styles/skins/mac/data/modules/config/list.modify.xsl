<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:variable name="lang-items" select="document('udata://system/getLangsList/')/udata/items/item" />

	<xsl:template match="/result[@method = 'langs']/data[@type = 'list' and @action = 'modify']">
		<form id="{../@module}_{../@method}_form" action="do/" method="post">
			<table class="tableContent">
				<thead>
					<tr>
						<th>
							<xsl:text>&label-langs-list;</xsl:text>
						</th>
						<th>
							<xsl:text>&label-lang-prefix;</xsl:text>
						</th>
						<th>
							<xsl:text>&label-delete;</xsl:text>
						</th>
					</tr>
				</thead>
				<tbody>
					<xsl:apply-templates mode="list-modify"/>
					<tr>
						<td>
							<input type="text" name="data[new][title]" />
						</td>
						<td>
							<input type="text" name="data[new][prefix]" />
						</td>
						<td />
					</tr>
				</tbody>
			</table>
			<xsl:call-template name="std-save-button" />
		</form>
		<xsl:apply-templates select="../@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="lang" mode="list-modify">
		<tr>
			<td>
				<input type="text" name="data[{@id}][title]" value="{@title}"/>
			</td>

			<td>
				<input type="text" name="data[{@id}][prefix]" value="{@prefix}"/>
			</td>

			<td class="center">
				<a href="{$lang-prefix}/admin/config/lang_del/{@id}/" class="delete unrestorable {/result/@module}_{/result/@method}_btn">
					<span><xsl:text>&label-delete;</xsl:text></span>
				</a>
			</td>
		</tr>
	</xsl:template>


	<xsl:template match="/result[@method = 'domains']/data">
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('.<xsl:value-of select="../@module" />_<xsl:value-of select="../@method" />_btn.refresh').click(function(){
					var id = this.rel;
					updateSitemap(id);
					return false;
				});
			});
			
			<![CDATA[
			var updateSitemap = function(id) {

				openDialog({
					stdButtons: true,
					title      : getLabel('js-update-sitemap'),
					text       : getLabel('js-update-sitemap-submit'),
					width      : 390,
					OKCallback : function () {

						var h  = '<div class="exchange_container">';
						h += '<div id="process-header">' + getLabel('js-update-sitemap') + '</div>';
						h += '<div><img id="process-bar" src="/images/cms/admin/mac/process.gif" class="progress" /></div>';

						h += '</div>';
						h += '<div id="export_log"></div>';
						h += '<div class="eip_buttons">';
						h += '<input id="stop_btn" type="button" value="' + getLabel('js-label-stop') + '" class="stop" />';
						h += '<div style="clear: both;"/>';
						h += '</div>';


						openDialog({
							stdButtons: false,
							title      : getLabel('js-update-sitemap'),
							text       : h,
							width      : 390,
							OKCallback : function () {

							}
						});
						processUpdateSitemap(id);
					}

				});

				var reportError = function(msg) {
					$('#export_log').append(msg + "<br />");
					$('#process-bar').detach();
					$('#process-header').detach();
					$('#exchange-container').detach();
					$('.eip_buttons').html('<input id="ok_btn" type="button" value="' + getLabel('js-exchange-btn_ok') + '" class="ok" style="margin:0;" /><div style="clear: both;"/>')
					$('#ok_btn').one("click", function() { closeDialog(); });
					if(window.session) {
						window.session.stopAutoActions();
					}

				}

				var processUpdateSitemap = function (id) {

					$('#stop_btn').one("click", function() { closeDialog(); return false; });

					if(window.session) {
						window.session.startAutoActions();
					}

					$.ajax({
						type: "GET",
						url: "/admin/config/update_sitemap/"+ id +".xml"+"?r=" + Math.random(),
						dataType: "xml",

						success: function(doc){
							var data_nl = doc.getElementsByTagName('data');
							if (!data_nl.length) {
								reportError(getLabel('js-exchange-ajaxerror'));
								return false;
							}
							var data = data_nl[0];
							var complete = data.getAttribute('complete') || false;

							if (complete === false) {
								var errors = data.getElementsByTagName('error');
								var error = errors[0] || false;

								var errorMessage = '';
								if(error !== false) {
									errorMessage = jQuery.browser.msie ? error.text : error.textContent;
								} else {
									errorMessage = getLabel('Parse data error. Required attribute complete not found');
								}

								reportError(errorMessage);
								return false;
							}

							if (complete == 1) {
								if(window.session) {
									window.session.stopAutoActions();
								}
								closeDialog();
							} else {
								processUpdateSitemap(id);
							}

						},

						error: function(event, XMLHttpRequest, ajaxOptions, thrownError) {
							if(window.session) {
								window.session.stopAutoActions();
							}
							reportError(getLabel('js-exchange-ajaxerror'));
						}

					});
				};
			};
		]]></script>

		<form id="{../@module}_{../@method}_form" action="do/" method="post">
			<table class="tableContent">
				<thead>
					<tr>
						<th>
							<xsl:text>&label-domain-address;</xsl:text>
						</th>
						<th>
							<xsl:text>&label-domain-lang;</xsl:text>
						</th>
						<th>
							<xsl:text>&label-mirrows;</xsl:text>
						</th>
						<th>
							<xsl:text>&label-update-sitemap;</xsl:text>
						</th>
						<th>
							<xsl:text>&label-delete;</xsl:text>
						</th>
					</tr>
				</thead>
				<tbody>
					<xsl:apply-templates mode="list-modify"/>
					<tr>
						<td>
							<input type="text" name="data[new][host]" />
						</td>
						<td>
							<select name="data[new][lang_id]">
								<xsl:apply-templates select="$lang-items" mode="std-form-item" />
							</select>
						</td>
						<td colspan="3" />
					</tr>
				</tbody>
			</table>
			<xsl:call-template name="std-save-button" />
		</form>

		<xsl:apply-templates select="../@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="domain" mode="list-modify">

		<tr>
			<td>
				<input type="text" name="data[{@id}][host]" value="{@host}" />
			</td>

			<td>
				<select name="data[{@id}][lang_id]">
					<xsl:apply-templates select="$lang-items" mode="std-form-item">
						<xsl:with-param name="value" select="@lang-id" />
					</xsl:apply-templates>
				</select>
			</td>

			<td align="center" style="padding:0;">
				<a href="{$lang-prefix}/admin/config/domain_mirrows/{@id}/" class="subitems">
					<img src="/images/cms/admin/mac/ico_edit.gif" title="&label-edit;" alt="&label-edit;" />
				</a>
			</td>

			<td align="center" style="padding:0;">
				<a href="#"  rel='{@id}' class="{/result/@module}_{/result/@method}_btn refresh">
					&label-update;
				</a>
			</td>

			<td>
				<a href="{$lang-prefix}/admin/config/domain_del/{@id}/" class="delete unrestorable {/result/@module}_{/result/@method}_btn">
					<span><xsl:text>&label-delete;</xsl:text></span>
				</a>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="domain[@id = '1']" mode="list-modify">
		<tr>
			<td>
				<input type="text" name="data[{@id}][host]" value="{@host}" disabled="disabled" />
			</td>

			<td>
				<select name="data[{@id}][lang_id]">
					<xsl:apply-templates select="$lang-items" mode="std-form-item">
						<xsl:with-param name="value" select="@lang-id" />
					</xsl:apply-templates>
				</select>
			</td>

			<td align="center" style="padding:0;">
				<a href="{$lang-prefix}/admin/config/domain_mirrows/{@id}/" class="subitems">
					<img src="/images/cms/admin/mac/ico_edit.gif" title="&label-edit;" alt="&label-edit;" />
				</a>
			</td>

			<td align="center" style="padding:0;">
				<a href="#"   rel='{@id}' class="{/result/@module}_{/result/@method}_btn refresh">
					&label-update;
				</a>
			</td>

			<td />
		</tr>
	</xsl:template>

	<xsl:template match="/result[@method = 'domain_mirrows']/data">
		<form action="do/" method="post">
			<xsl:apply-templates select="group" mode="settings-modify" />

			<table class="tableContent">
				<thead>
					<tr>
						<th>
							<xsl:text>&label-domain-mirror-address;</xsl:text>
						</th>
						<th>
							<xsl:text>&label-delete;</xsl:text>
						</th>
					</tr>
				</thead>
				<tbody>
					<xsl:apply-templates select="domainMirrow" mode="list-modify"/>
					<tr>
						<td>
							<input type="text" name="data[new][host]" />
						</td>
						<td />
					</tr>
				</tbody>
			</table>
			<xsl:call-template name="std-save-button" />
		</form>
		<xsl:apply-templates select="../@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="domainMirrow" mode="list-modify">
		<tr>
			<td>
				<input type="text" name="data[{@id}][host]" value="{@host}" />
			</td>

			<td class="center">
				<input type="checkbox" name="dels[]" value="{@id}" class="check" />
			</td>
		</tr>
	</xsl:template>


	<xsl:template match="group" mode="settings-modify">
		<table class="tableContent">
			<thead>
				<tr>
					<th colspan="2">
						<xsl:value-of select="@label" />
					</th>
				</tr>
			</thead>

			<tbody>
				<xsl:apply-templates select="option" mode="settings-modify" />
			</tbody>
		</table>

		<xsl:call-template name="std-save-button" />
	</xsl:template>

	<xsl:template match="option" mode="settings-modify">
		<tr>
			<td>
				<label for="{@name}">
					<xsl:value-of select="@label" />
				</label>
			</td>

			<td>
				<input type="text" name="{@name}" id="{@name}" value="{.}" />
			</td>
		</tr>
	</xsl:template>
</xsl:stylesheet>