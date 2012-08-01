<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">
<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template name="search-form-left-column">
		<form class="search" action="/search/search_do/" method="get">
			<input type="text" value="&search-default-text;" name="search_string" class="textinputs" onblur="javascript: if(this.value == '') this.value = '&search-default-text;';" onfocus="javascript: if(this.value == '&search-default-text;') this.value = '';" />
		</form>
	</xsl:template>
</xsl:stylesheet>