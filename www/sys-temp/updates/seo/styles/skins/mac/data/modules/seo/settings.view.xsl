<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/seo">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="data[@type = 'settings' and @action = 'view']">

		<xsl:variable name="domains-list" select="document('udata://core/getDomainsList')/udata/domains/domain" />
		<xsl:variable name="http_host" select="//option[@name='http_host']/value" />

		<script type="text/javascript"><![CDATA[
				$(document).ready(function() {
					jQuery('.sort span').click(function(){

						var sort = jQuery(this).attr('id');
						var host = jQuery('#host').attr('value');

						jQuery('tbody').html('');

						var order = jQuery(this).attr('class') == "asc" ? "desc" : "asc";

						getData(host, sort, order);

						jQuery('.sort span').removeAttr('class');
						jQuery(this).attr('class', order);

					});
					getData(document.getElementById('host').value, 'word', 'asc');
				});

				function getData(host, sort, order) {

					jQuery.ajax({
						type: "GET",
						url: "/admin/seo/seo/.xml?" + "host=" + host +"&sort=" + sort + "&order=" + order,
						dataType: "xml",
						success: function(doc){

							var errors = doc.getElementsByTagName('error');
							if (errors.length) {

								var error = "<div>" +
									errors[0].firstChild.nodeValue.replace('&lt;', '<').replace('&gt;', '>') +
									"<form action=\"\" method=\"get\"><input type=\"hidden\" name=\"host\" value=\"" + host + "\"><div class=\"buttons\" style=\"padding-top:5px;\"><div class=\"button\" style=\"float:left;\"><input type=\"submit\" value=\"" +
									getLabel('js-panel-repeat') +
									"\" /><span class=\"l\" /><span class=\"r\" /></div></div></form></div>";
									jQuery('#result').html(error);
									return;

							} else {

								var items =  doc.getElementsByTagName('item');
								for (var i = 0; i < items.length; i++) {
									var item = items[i];

									var tr = "<tr><td>" +
										item.getAttribute('word') +
										"</td><td class=\"center\"><a href=\"http://yandex.ru/yandsearch?text=" +
										item.getAttribute('word') +
										"&amp;lr=2'\" title=\"\" target=\"_blank\">"+
										item.getAttribute('pos_y') +
										"</a></td><td class=\"center\"><a href=\"http://www.google.ru/#sclient=psy&amp;hl=ru&amp;newwindow=1&amp;site=&amp;source=hp&amp;q="+
										item.getAttribute('word') +
										"\" title=\"\" target=\"_blank\">"+
										item.getAttribute('pos_g') +
										"</a></td><td class=\"center\">" +
										item.getAttribute('show_month') +
										"</td><td class=\"center\">"+
										item.getAttribute('wordstat') +
										"</td></tr>";

										jQuery('tbody').append(tr);
								}
							}
						}
					});
				};
			]]></script>

		<div id="webo_in">
			<div class="panel">
				<div class="header">
					<span><xsl:text>&label-site-analysis;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content">

					<form action="" method="get">
						<div class="field">
							<label for="host">
								<span class="label">
									<acronym>
										&label-site-address;
									</acronym>
								</span>
									<input type="text" name="host" value="{$http_host}" id="host" style="position: absolute;
	width: 80%; border-right:none; outline:none;" />
							</label>
							<select onchange="getElementById('host').value = this.options[this.selectedIndex].innerHTML; this.selectedIndex=0" id="domain-selector">
								<option selected="selected"></option>
								<xsl:apply-templates select="$domains-list" mode="domain-selector" />
							</select>
						</div>
						<div class="buttons" style="padding-top:5px;">
							<div class="button">
								<input type="submit" value="&label-button;" /><span class="l" /><span class="r" />
							</div>
						</div>
					</form>
				</div>
			</div>

			<div class="panel">
				<div class="header">
					<span><xsl:text>&label-results;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content" id="result">
					<table class="tableContent">
						<thead>
							<tr>
								<th class="sort">
									<span id="word"><xsl:text>&label-query;</xsl:text></span>
								</th>
								<th class="sort">
									<span id="pos_y"><xsl:text>&label-yandex;</xsl:text></span>
								</th>
								<th class="sort">
									<span id="pos_g"><xsl:text>&label-google;</xsl:text></span>
								</th>
								<th class="sort" style="width:200px;">
									<span id="show_month" style="width:200px;"><xsl:text>&label-count;</xsl:text></span>
								</th>
								<th class="sort">
									<span id="wordstat"><xsl:text>&label-wordstat;</xsl:text></span>
								</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
					<div style="margin-top:20px; font-size:10px;">Основано на данных <a href="http://www.megaindex.ru" target="_blank">megaindex.ru</a></div>
				</div>
			</div>

		</div>
    </xsl:template>

	<xsl:template match="domain" mode="domain-selector">
		<option value="{@id}"><xsl:value-of select="@host"/></option>
	</xsl:template>


</xsl:stylesheet>