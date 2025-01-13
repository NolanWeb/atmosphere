<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="UTF-8" indent="yes" />

    <xsl:template match="/">
        <html>
            <head>
                <meta charset="UTF-8" />
                <title>Bulletin Météo</title>
                <link rel="stylesheet" href="style.css" />
            </head>
            <body>
                <header>
                    <h1>Bulletin Météo</h1>
                </header>
                <main>
                    <section>
                        <article>
                            <xsl:call-template name="render-weather" />
                        </article>
                    </section>
                </main>
            </body>
        </html>
    </xsl:template>

    <!-- Template pour afficher les informations météo -->
    <xsl:template name="render-weather">
        <div class="forecast">
            <!-- Matin -->
            <div class="time-period">
                <h2>Matin</h2>
                <xsl:apply-templates select="previsions/echeance[@hour = 6]" />
            </div>

            <!-- Midi -->
            <div class="time-period">
                <h2>Midi</h2>
                <xsl:apply-templates select="previsions/echeance[@hour = 12]" />
            </div>

            <!-- Soir -->
            <div class="time-period">
                <h2>Soir</h2>
                <xsl:apply-templates select="previsions/echeance[@hour = 18]" />
            </div>
        </div>
    </xsl:template>

    <!-- Template pour les échéances météo -->
    <xsl:template match="echeance">
        <div class="details">
            <p class="temperature">
                <span>
                    <xsl:choose>
                        <xsl:when test="temperature/level/@val &lt; 0">&#10052;</xsl:when>
                        <xsl:otherwise>&#127777;</xsl:otherwise>
                    </xsl:choose>
                    <xsl:value-of select="format-number(temperature/level[@val='2m'] - 273.15, '#0.0')" /> °C
                </span>
            </p>

            <!-- Précipitations -->
            <p class="precipitation">
                <xsl:choose>
                    <xsl:when test="pluie &gt; 0">&#127783;</xsl:when>
                    <xsl:otherwise>&#9728;</xsl:otherwise>
                </xsl:choose>
            </p>

            <!-- Vent -->
            <p class="wind">
                <span>
                    <xsl:choose>
                        <xsl:when test="vent_moyen/level/@val &gt; 20">&#128168;</xsl:when>
                        <xsl:otherwise>&#127811;</xsl:otherwise>
                    </xsl:choose>
                    <xsl:value-of select="format-number(number(vent_moyen/level[@val='10m']), '#0.0')" /> km/h
                </span>
            </p>
        </div>
    </xsl:template>
</xsl:stylesheet>
