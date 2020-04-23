<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:a="http://www.fatturapa.gov.it/sdi/messaggi/v1.0" xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
    <xsl:output version="4.0" method="html" indent="no" encoding="UTF-8" doctype-public="-//W3C//DTD HTML 4.0 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>
    <xsl:param name="SV_OutputFormat" select="'HTML'"/>
    <xsl:variable name="XML" select="/"/>

    <xsl:template match="/">
        <html>
            <head>
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <style type="text/css">
                    #notifica-container { width: 100%; position: relative; font-family: sans-serif; }

                    #notifica { margin-left: auto; margin-right: auto; max-width: 1280px; min-width: 930px; padding: 0; }
                    #notifica h1 { padding: 20px 0 0 0; margin: 0; font-size: 30px; }
                    #notifica h2 { padding: 20px 0 0 0; margin: 0; font-size: 20px; border-bottom: 2px solid #333333; }
                    #notifica h3 { padding: 20px 0 0 0; margin: 0; font-size: 17px; }
                    #notifica h4 { padding: 20px 0 0 0; margin: 0; font-size: 15px; }
                    #notifica h5 { padding: 15px 0 0 0; margin: 0; font-size: 14px; font-style: italic; }
                    #notifica ul { list-style-type: none; margin: 0 !important; padding: 1em 0 1em 2em !important; }
                    #notifica ul li {}
                    #notifica ul li span { font-weight: bold; }
                    #notifica div { padding: 0; margin: 0; }

                    #notifica div.page {
                    background: #fff url("http://www.fatturapa.gov.it/img/sdi.png") right bottom no-repeat !important;
                    position: relative;

                    margin: 20px 0
                    50px 0;
                    padding: 60px;

                    background: -moz-linear-gradient(0% 0 360deg, #FFFFFF, #F2F2F2 20%, #FFFFFF) repeat scroll 0 0 transparent;
                    border: 1px solid #CCCCCC;
                    -webkitbox-shadow: 0 0 10px rgba(0, 0, 0,
                    0.3);
                    -mozbox-shadow: 0
                    0 10px rgba(0, 0, 0, 0.3);
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
                    }

                    #notifica div.header { padding: 50px 0 0 0; margin: 0; font-size: 11px; text-align: center; color: #777777; }
                    #notifica div.footer { padding: 50px 0 0 0; margin: 0; font-size: 11px; text-align: center; color: #777777; }
                    #notifica-container .versione { font-size: 11px; float:right; color: #777777; }

                    #notifica table { font-size: .9em; margin-top: 1em; border-collapse: collapse; border: 1px solid black; }
                    #notifica table caption { color: black; padding: .5em 0; font-weight: bold; }
                    #notifica table th { border: 1px solid black; background-color: #f0f0f0; padding: .2em .5em; }
                    #notifica table td { border: 1px solid black; padding: .2em .5em; }
                    #notifica table td:first-child { text-align: center; font-weight: bold; }
                </style>
            </head>
            <body>

                <xsl:for-each select="a:AttestazioneTrasmissioneFattura">

                    <div id="notifica-container">
                        <div id="notifica">
                            <div class="page">

                                <div class="versione">
                                    <xsl:if test="ds:Signature">
                                        File con firma elettronica -
                                    </xsl:if>
                                    Versione <xsl:value-of select="@versione"/>
                                </div>
                                <h1>Attestazione di avvenuta trasmissione della fattura con impossibilità di recapito</h1>

                                <ul>
                                    <li>
                                        Identificativo SdI:
                                        <span><xsl:value-of select="IdentificativoSdI" /></span>
                                    </li>

                                    <li>
                                        Nome File:
                                        <span><xsl:value-of select="NomeFile" /></span>
                                    </li>

                                    <li>
                                        Data Ora Ricezione:
                                        <span><xsl:value-of select="DataOraRicezione" /></span>
                                    </li>

                                    <xsl:if test="RiferimentoArchivio">
                                        <li>
                                            <h3>Riferimento Archivio:</h3>
                                            <ul>
                                                <li>
                                                    Identificativo SdI:
                                                    <span><xsl:value-of select="RiferimentoArchivio/IdentificativoSdI" /></span>
                                                </li>
                                                <li>
                                                    Nome File:
                                                    <span><xsl:value-of select="RiferimentoArchivio/NomeFile" /></span>
                                                </li>
                                            </ul>
                                        </li>
                                    </xsl:if>

                                    <li>
                                        Destinatario:
                                        <span><xsl:value-of select="Destinatario" /></span>
                                    </li>

                                    <li>
                                        Message Id:
                                        <span><xsl:value-of select="MessageId" /></span>
                                    </li>
                                    <xsl:if test="PecMessageId">
                                        <li>
                                            Pec Message-ID:
                                            <span><xsl:value-of select="PecMessageId" /></span>
                                        </li>
                                    </xsl:if>

                                    <xsl:if test="Note">
                                        <li>
                                            Note:
                                            <span><xsl:value-of select="Note" /></span>
                                        </li>
                                    </xsl:if>

                                    <li>
                                        Hash del File Originale:
                                        <span><xsl:value-of select="HashFileOriginale" /></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </xsl:for-each>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
