<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="xml" indent="yes"/>

	<xsl:param name="test_request_param" />
	<xsl:param name="_test_server_param" />

	<xsl:template match="/">
		<tests>
			<!-- Тест на входящие параметры -->
			<test name="testRequestParam"><xsl:value-of select="$test_request_param" /></test>
			<test name="testServerParam"><xsl:value-of select="$_test_server_param" /></test>

			<!-- Для теста на парсинг свойств страницы  -->
			<test name="testParseProperies">
				<xsl:apply-templates
					select="result/page/properties/group/property"
					mode="testParseProperies"
				/>
			</test>

		</tests>
	</xsl:template>

	<xsl:template match="property" mode="testParseProperies">
		<prop name="{@name}"><xsl:value-of select="value" /></prop>
	</xsl:template>

</xsl:stylesheet>