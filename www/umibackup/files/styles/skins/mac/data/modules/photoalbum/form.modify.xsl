<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/photoalbum" [
		<!ENTITY sys-module        'photoalbum'>
		<!ENTITY sys-method-add        'add'>
		<!ENTITY sys-method-edit    'edit'>
		<!ENTITY sys-method-del        'del'>
		<!ENTITY sys-method-list    'lists'>
		<!ENTITY sys-method-acivity     'activity'>

		<!ENTITY sys-type-list        'album'>
		<!ENTITY sys-type-item        'photo'>
		]>


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink"
	xmlns:umi="http://www.umi-cms.ru/TR/umi"
	xmlns:php="http://php.net/xsl">

	<xsl:template match="field[@type = 'img_file' and @name='photo']" mode="form-modify">
		<xsl:variable name="altname" select="document(concat('upage://', /result/data/page/@parentId))/udata/page/@alt-name" />
		<xsl:variable name="destination-folder">
			<xsl:choose>
				<xsl:when test="not(text())">
					<xsl:value-of select="concat(@destination-folder, '', document(concat('upage://', /result/data/page/@parentId))/udata/page/@link)" />
					
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@destination-folder" />
				</xsl:otherwise>
			</xsl:choose>			
		</xsl:variable>

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

		<div class="field file" id="{generate-id()}" name="{@input_name}"
			umi:field-type="{@type}"
			umi:name="{@name}"
			umi:folder="{$destination-folder}"
			umi:file="{@relative-path}"
			umi:folder-hash="{php:function('elfinder_get_hash', string($destination-folder))}"
			umi:file-hash="{php:function('elfinder_get_hash', string(@relative-path))}"
			umi:lang="{/result/@interface-lang}"
			umi:filemanager="{$filemanager}"
			>
			<label for="symlinkInput{generate-id()}">
				<span class="label">
					<acronym>
						<xsl:apply-templates select="." mode="sys-tips" />						
						<xsl:value-of select="@title" />						
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</span>
				<span id="fileControlContainer_{generate-id()}">
					
				</span>
			</label>
		</div>
	</xsl:template>	

</xsl:stylesheet>