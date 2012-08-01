<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				exclude-result-prefixes="xsl date udt xlink">

	<xsl:template match="udata[@module = 'dispatches'][@method = 'subscribe']" mode="right">
		<div class="infoblock">
			<div class="title"><h2><xsl:text>Рассылки</xsl:text></h2></div>
			<div class="body">
				<div class="in">
					<form action="/dispatches/subscribe_do/" name="sbs_frm" method="post">
						<ul>
							<li><input	type="text"
									onblur="if(this.value == '') this.value = 'Ваш e-mail';"
									onfocus="if(this.value == 'Ваш e-mail') this.value = '';"
									value="Ваш e-mail"
									class="input"
									id="subscribe"
									name="sbs_mail" /></li>
						</ul>
						<input type="submit" class="button" value="Подписаться" />
					</form>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'dispatches'][@method = 'subscribe'][subscriber_dispatches]" mode="right">
		<xsl:apply-templates select="subscriber_dispatches" mode="right" />
	</xsl:template>

	<xsl:template match="subscriber_dispatches" mode="right" />

	<xsl:template match="subscriber_dispatches[items]" mode="right">
		<div class="infoblock">
			<div class="title"><h2><xsl:text>Рассылки</xsl:text></h2></div>
			<div class="body">
				<div class="in">
					<form action="/dispatches/subscribe_do/" name="sbs_frm" method="post">
						<ul><xsl:apply-templates select="items" mode="dispatches" /></ul>
						<input type="submit" class="button" value="Подписаться" />
					</form>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="result[@module = 'dispatches'][@method = 'subscribe']">
		<form action="/dispatches/subscribe_do/" enctype="multipart/form-data" name="sbs_frm" method="post">
			<xsl:apply-templates select="document('udata://dispatches/subscribe/')/udata" />
			<input type="submit" class="button" value="Подписаться" />
		</form>
	</xsl:template>

	<xsl:template match="udata[@module = 'dispatches'][@method = 'subscribe']">
		<div>
			<input	type="text"
					onblur="javascript: if(this.value == '') this.value = 'Ваш e-mail';"
					onfocus="javascript: if(this.value == 'Ваш e-mail') this.value = '';"
					value="Ваш e-mail"
					class="input"
					id="subscribe"
					name="sbs_mail" />
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'dispatches'][@method = 'subscribe'][subscriber_dispatches]">
		<xsl:apply-templates select="subscriber_dispatches" />
	</xsl:template>

	<xsl:template match="subscriber_dispatches" />

	<xsl:template match="subscriber_dispatches[items]">
		<ul><xsl:apply-templates select="items" mode="dispatches" /></ul>
	</xsl:template>

	<xsl:template match="items" mode="dispatches">
		<li>
			<label>
				<input type="checkbox" name="subscriber_dispatches[{@id}]" value="{@id}">
					<xsl:if test="@is_checked = '1'">
						<xsl:attribute name="checked">
							<xsl:text>checked</xsl:text>
						</xsl:attribute>
					</xsl:if>
				</input>
				<xsl:value-of select="." />
			</label>
		</li>
	</xsl:template>

</xsl:stylesheet>