<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common" [
	<!ENTITY sys-module	'webforms'>
]>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<xsl:template match="data[@type = 'list' and @action = 'view']">
		<xsl:variable name="menu">
			<xsl:choose>
				<xsl:when test="$method = 'messages'"><![CDATA[
					var menu = [
						['view', 'view',     ContextMenu.itemHandlers.viewMessage],
						['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
					]
				]]></xsl:when>				
				<xsl:otherwise />
			</xsl:choose>
		</xsl:variable>		
		<xsl:variable name="show-toolbar">
			<xsl:choose>
				<xsl:when test="$method = 'messages'">0</xsl:when>
				<xsl:otherwise>1</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="control-params">
			<xsl:choose>
				<xsl:when test="$method = 'messages' and $param0">
					<xsl:value-of select="concat($method, '&amp;type_id=', $param0)" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$method" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<div class="imgButtonWrapper">
			<xsl:choose>
				<xsl:when test="$method = 'addresses'">
					<a id="addAddress" href="{$lang-prefix}/admin/&sys-module;/address_add/">&label-address-add;</a>
				</xsl:when>
				<xsl:when test="$method = 'templates'">
					<a id="addTemplates" href="{$lang-prefix}/admin/&sys-module;/template_add/">&label-template-add;</a>
				</xsl:when>
				<xsl:when test="$method = 'messages'">
					<xsl:apply-templates select="document(concat('udata://webforms/getForms/', $param0))/udata" />
				</xsl:when>
				<xsl:otherwise />
			</xsl:choose>
		</div>
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="control-params" select="$control-params" />
			<xsl:with-param name="content-type" select="'objects'" />
			<xsl:with-param name="menu" select="$menu" />
			<xsl:with-param name="show-toolbar" select="$show-toolbar" />
			<xsl:with-param name="control-type-id" select="$param0" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="udata[@module = 'webforms'][@method = 'getForms']">
		<form class="filter-container" action="" method="post" onsubmit="location.href = '/admin/webforms/messages/' + this.form_id.value; return false;">
			<select name="form_id">
				<option value=""></option>
				<xsl:apply-templates select="items/item" />
			</select>
			<input type="submit" class="fcApplyButton" style="width:90px;" value="фильтровать" />
		</form>
	</xsl:template>

	<xsl:template match="udata[@module = 'webforms' and @method = 'getForms']/items/item">
		<option value="{@id}"><xsl:value-of select="." /></option>
	</xsl:template>

	<xsl:template match="udata[@module = 'webforms' and @method = 'getForms']/items/item[@selected]">
		<option value="{@id}" selected="selected"><xsl:value-of select="." /></option>
	</xsl:template>

	<xsl:template match="/result[@method = 'forms']/data[@type = 'list' and @action = 'view']">
		<xsl:variable name="menu">
			<![CDATA[
					var menu = [
						['edit-item', 'ico_edit',     ContextMenu.itemHandlers.editItem],
						['delete', 'ico_del', ContextMenu.itemHandlers.deleteItem]
					]
				]]>
		</xsl:variable>
		<div class="imgButtonWrapper">
			<a id="addForms" href="{$lang-prefix}/admin/&sys-module;/form_add/{basetype/@id}">&label-type-add;</a>
		</div>
		<script type="text/javascript">
			var count = 0;
			var forms = [];
			function updatePages(req) {
				if(!forms.length) return;
				var pages = $('page', req);
				var cache = {};
				for(var i=0; i&lt;forms.length; i++) {
					var id = 'wfpage' + forms[i];
					var e  = document.getElementById(id);
					e.innerHTML = "&lt;a href='<xsl:value-of select="$lang-prefix"/>/admin/webforms/placeOnPage/"+forms[i]+"/'&gt;&label-place;&lt;/a&gt;"
					cache[ forms[i] ] = e;
				}
				if(pages)
				for(var i=0; i&lt;pages.length; i++) {
					var page   = pages.get(i);
					var pageId = parseInt(page.getAttribute('id'));
					if(pageId != 0) {
						var formId  = parseInt(page.getAttribute('form'));
						var href    = page.getAttribute('href');
						var e       = cache[formId];
						e.innerHTML = "&lt;a href='"+href+"'&gt;"+href+"&lt;/a&gt;&nbsp;&nbsp;&nbsp;(&lt;a href='<xsl:value-of select="$lang-prefix"/>/admin/content/edit/"+pageId+"/'&gt;&label-edit;&lt;/a&gt;)";
					}
				}
				count = 0;
				forms = [];
			}
		</script>
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="control-params" select="$method" />
			<xsl:with-param name="flat-mode" select="1" />
			<xsl:with-param name="content-type"><xsl:text>types</xsl:text></xsl:with-param>
			<xsl:with-param name="menu" select="$menu" />
			<xsl:with-param name="js-dataset-events">
				<![CDATA[
					oDataSet.addEventHandler('onBeforeLoad', function(p) { forms = []; });
					oDataSet.addEventHandler('onAfterLoad', function(p){ count = p.objects.length; });
				]]>
			</xsl:with-param>
			<xsl:with-param name="js-value-callback">
				<![CDATA[
					function (value, name, item) {						
						var data = item.getData();
						if(name === "page") {							
							forms.push(data.id);							
							if(count == forms.length) {
								$.get(window.pre_lang + "/admin/webforms/getPages.xml?id[]="+forms.join('&id[]='), {}, updatePages);									
							}							
							return "<span id='wfpage"+data.id+"'>"+getLabel('js-label-wait')+"</span>";
						} else {
							return data.title;
						}
					}
				]]>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

</xsl:stylesheet>