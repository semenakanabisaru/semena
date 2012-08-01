<?xml version="1.0" encoding="utf-8"?>
<!--DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file"-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" />

	<xsl:template match="body">
		<html>
			<head>
				<style type="text/css">
					body {
						background-color:	#FFF;
						margin-top:			42px;
						margin-bottom:		42px;
						margin-left:		57px;
						margin-right:		157px;
					}
					body, p, td {font-family:Arial;font-size:13px}
					h1, h2 {font-family:Trebuchet MS;color:#004f9c;font-weight:normal}
					h1 {font-size:20px}
					h2 {margin-top:30px;font-size:17px}
					hr {border:0px;height:1px;background-color:#d1d1d1}
					a, a:visited, a:hover {color:#0072e1;text-decoration:underline}
					div.notice {
						background-color:	#FFE7D6;
						padding-left:		17px;
						padding-right:		17px;
						padding-top:		13px;
						padding-bottom:		14px;
						margin-left:		1px;
					}
					table {border:#d1d1d1 1px solid}
					tr.header {background-color:#e8f1fa;color:#004f9c}
					td.header {border:0px}
					td {padding:8px;border-top:#D1D1D1 1px solid}
					ul {padding-left:22px}
				</style>
			</head>
			<body>
				<h2><xsl:value-of select="header" /></h2>
				<xsl:value-of select="content" disable-output-escaping="yes" />
			</body>
		</html>
	</xsl:template>

</xsl:stylesheet>