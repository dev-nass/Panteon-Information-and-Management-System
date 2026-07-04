<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certification of Service</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        /* ── WAVE BANDS (images, not CSS curves — DomPDF renders these pixel-perfect) ── */
        .wave-top img,
        .wave-bottom img {
            width: 100%;
            height: 110px;
            display: block;
        }

        /* ── PAGE CONTENT WRAPPER (fixed px padding, not %, so it behaves the same in DomPDF as in a browser).
             Bottom padding reserves space so text never runs under the fixed footer wave. */
        .content {
            padding: 0 45px 140px 45px;
        }

        /* ── HEADER ──
             The table itself is NOT pulled up — only the logo image is, via its own
             negative margin. This way the wave only ever overlaps the logo, never the text. */
        .header-table {
            width: 100%;
            margin: 8px 0 10px 0;
        }

        .header-table td {
            vertical-align: middle;
        }

        .h-logo-l {
            width: 80px;
        }

        .h-logo-l img {
            width: 68px;
            height: 68px;
            border-radius: 50%;
        }

        .h-text {
            padding-left: 12px;
            font-size: 12px;
            color: #333;
            line-height: 1.55;
        }

        .h-logo-r {
            width: 88px;
            text-align: right;
        }

        .h-logo-r img {
            width: 76px;
            height: auto;
            margin-top: -55px;
            /* pulls just this logo up over the wave, which now extends on the right */
            margin-bottom: -13px;
            /* keeps row height/spacing from shifting */
        }

        /* ── DIVIDER ── */
        .divider {
            border: none;
            border-top: 1.5px solid #15803d;
            margin: 8px 0 14px 0;
        }

        /* ── CONTENT TEXT ── */
        .gov-title {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #2c3e50;
            margin-bottom: 6px;
        }

        .cert-title {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 6px;
            color: #222;
            text-align: center;
            margin: 14px 0 10px 0;
        }

        /* ── SPACING STRATEGY ──
             Group the content logically: (1) titles, (2) the full certification
             body text as ONE unit with normal paragraph spacing, (3) signature,
             (4) seal note. Only ONE deliberate gap (.body-group's margin-bottom)
             does the "spreading" — pushing the signature block down the page.
             Adding big gaps between every sentence (the previous attempt) pushed
             the whole thing onto a second page instead. */
        .body-group {
            margin-bottom: 150px;
            /* the main spacer that spreads content down the page */
        }

        .body-text {
            font-size: 13px;
            line-height: 1.9;
            color: #222;
            text-align: justify;
            text-indent: 40px;
            margin-bottom: 16px;
        }

        .body-text-center {
            font-size: 13px;
            line-height: 1.9;
            color: #222;
            text-align: center;
            margin: 24px 0 0 0;
        }

        /* ── SIGNATURE ── */
        .sig-table {
            width: 100%;
            margin-top: 10px;
        }

        .sig-table td {
            vertical-align: top;
        }

        .sig-gap {
            width: 58%;
        }

        .sig-block {
            width: 42%;
            text-align: center;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }

        .sig-space {
            height: 45px;
        }

        .sig-name {
            font-weight: bold;
            font-size: 13px;
            border-bottom: 1px solid #333;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }

        .seal-note {
            font-style: italic;
            font-size: 11px;
            color: #777;
            margin-top: 20px;
        }

        /* ── FOOTER ──
             position: fixed anchors this to the physical bottom of every page in DomPDF,
             regardless of how much (or little) content precedes it — this is what was
             missing before, since a normal-flow element just sits wherever the content ends. */
        .footer-wrap {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
        }

        .footer-text-table {
            width: 100%;
            position: absolute;
            bottom: 18px;
            left: 0;
            padding: 0 40px;
        }

        .footer-text-table td {
            vertical-align: middle;
            color: #ffffff;
        }

        .f-left {
            font-size: 10px;
            line-height: 1.6;
            text-align: left;
        }

        .f-right {
            text-align: right;
        }

        .motto {
            font-style: italic;
            font-size: 13px;
        }

        .footer-city {
            font-weight: bold;
            font-size: 15px;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body>

    <!-- Top Wave (image, full bleed) -->
    <div class="wave-top">
        <img src="{{ $waveTop }}" alt="">
    </div>

    <div class="content">

        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="h-logo-l">
                    <img src="{{ $logoLeft }}" alt="Dasmariñas">
                </td>
                <td class="h-text">
                    Republic of the Philippines<br>
                    Province of Cavite<br>
                    City of Dasmariñas
                </td>
                <td class="h-logo-r">
                    <img src="{{ $logoRight }}" alt="Bagong Pilipinas">
                </td>
            </tr>
        </table>

        <hr class="divider">

        <div class="gov-title">CITY GOVERNMENT OF DASMARIÑAS</div>

        <div class="cert-title">C E R T I F I C A T I O N</div>

        <div class="body-group">
            <div class="body-text">
                This is to certify that <strong>{{ strtoupper($data['deceased_name']) }}</strong>
                of {{ $data['deceased_address'] ?? 'N/A' }}.
                Passed away on
                {{ $data['date_of_death'] ? \Carbon\Carbon::parse($data['date_of_death'])->format('F d, Y') : 'N/A' }},
                at {{ $data['place_of_death'] ?? 'N/A' }}.
                His/her <u>burial</u> took place on
                <strong>{{ $data['date_of_depository'] ? \Carbon\Carbon::parse($data['date_of_depository'])->format('F d, Y') : 'N/A' }}</strong>
                at <strong>{{ $data['burial_place'] ?? 'Panteon De Dasmariñas' }}</strong>.
            </div>

            <div class="body-text">
                This certification is issued to <strong>{{ strtoupper($data['applicant_name']) }}</strong>
                legal-age Filipino citizen residing in {{ $data['applicant_address'] }}.
                He/she is the <strong>{{ $data['relationship'] ?? 'N/A' }}</strong> of the deceased.
            </div>

            <div class="body-text">
                This Certification is being issued for his/her application for
                <strong>Interment/Inurnment / and other purposes.</strong>
            </div>

            <div class="body-text-center">
                Issued this <strong>{{ \Carbon\Carbon::now()->format('jS') }} day of
                    {{ \Carbon\Carbon::now()->format('F Y') }}</strong>
                at the City of Dasmariñas, Cavite
            </div>
        </div>

        <table class="sig-table">
            <tr>
                <td class="sig-gap"></td>
                <td class="sig-block">
                    <div class="sig-space"></div>
                    <div class="sig-name">LIEZYL M. CAMAGANACAN</div>
                    <div>
                        La Funeraria De Dasmariñas<br>
                        Panteon De Dasmariñas<br>
                        Officer-in-Charge/Unit Head
                    </div>
                </td>
            </tr>
        </table>

        <div class="seal-note">(Not valid without seal)</div>

    </div>

    <!-- Bottom Wave (image, full bleed) with footer text overlaid -->
    <div class="footer-wrap">
        <div class="wave-bottom">
            <img src="{{ $waveBottom }}" alt="">
        </div>
        <table class="footer-text-table">
            <tr>
                <td class="f-left">
                    Address: Brgy. Burol Main, City of Dasmariñas, Cavite<br>
                    Trunkline: 481-4600
                </td>
                <td class="f-right">
                    <div class="motto">Onward. Forward.</div>
                    <div class="footer-city">CITY OF DASMARIÑAS!</div>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>