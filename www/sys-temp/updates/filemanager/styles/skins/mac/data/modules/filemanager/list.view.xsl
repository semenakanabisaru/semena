<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common/catalog" [
	<!ENTITY sys-module		   'filemanager'>
	<!ENTITY sys-method-add		   'add_shared_file'>
]>

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi"
	>

	<xsl:template match="data" priority="1">

		<xsl:variable name="filemanager-id" select="document(concat('uobject://',/result/@user-id))/udata//property[@name = 'filemanager']/value/item/@id" />

		<xsl:variable name="filemanager">
			<xsl:choose>
				<xsl:when test="not($filemanager-id)">
					<xsl:text>flash</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="document(concat('uobject://',$filemanager-id))/udata//property[@name = 'fm_prefix']/value" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="imgButtonWrapper">
			<a href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/">&label-add-file;</a>
		</div>

		<div class="imgButtonWrapper" id="filemanager_upload_files">
			<a href="javascript:void(0);"
				umi:lang="{/result/@interface-lang}"
				umi:filemanager="{$filemanager}"
			>&label-filemanager;</a>
		</div>

		<xsl:call-template name="ui-smc-table">
			
		</xsl:call-template>
	</xsl:template>


</xsl:stylesheet>