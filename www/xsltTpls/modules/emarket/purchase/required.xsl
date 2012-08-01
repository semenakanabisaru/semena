<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink">

	<xsl:template match="purchasing[@stage = 'required'][@step = 'personal']">
		<form enctype="multipart/form-data" method="post" action="{$langPrefix}/emarket/purchase/required/personal/do/">
			<xsl:apply-templates select="document(concat('udata://data/getEditForm/', customer-id))/udata" />

			<div>
				<input type="submit" class="button" value="&save-changes;" />
			</div>
		</form>
	</xsl:template>
</xsl:stylesheet>