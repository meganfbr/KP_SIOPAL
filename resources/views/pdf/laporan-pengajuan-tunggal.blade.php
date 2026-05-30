<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengajuan Perbaikan</title>
    <style>
        @page { margin: 30px 40px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.4;
        }

        /* MAIN WRAPPER */
        .container {
            border: 2px solid #000;
            width: 100%;
            box-sizing: border-box;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            border-collapse: collapse;
        }
        .header-table td {
            border-right: 1px solid #000;
            vertical-align: middle;
            padding: 8px;
        }
        .header-table td:last-child {
            border-right: none;
        }
        .logo-cell {
            width: 15%;
            text-align: center;
        }
        .logo-text {
            font-family: Arial, sans-serif;
            font-size: 20px;
            font-weight: bold;
            color: #000; /* Monochrome */
        }
        .title-cell {
            width: 55%;
            text-align: center;
        }
        .title-cell h2 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
        }
        .title-cell h3 {
            margin: 5px 0 0 0;
            font-size: 11pt;
            font-weight: normal;
        }
        .meta-cell {
            width: 30%;
            font-size: 9pt;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px;
            border: none;
        }

        /* SECTION UTAMA */
        .section {
            border-bottom: 1px solid #000;
            padding: 8px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .no-border-bottom {
            border-bottom: none;
        }

        /* INFO TABLE */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .info-label {
            width: 25%;
        }
        .info-colon {
            width: 2%;
        }

        /* DATA TABLE (Kerusakan) */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 10pt;
            vertical-align: top;
        }
        .data-table th {
            text-align: center;
            background-color: #f0f0f0; /* Slight gray for formal print */
            font-weight: bold;
        }
        .center {
            text-align: center !important;
        }

        /* DOTTED LINES */
        .dotted-lines {
            margin-top: 10px;
            line-height: 25px;
            color: #000;
            background-image: linear-gradient(to right, #000 33%, rgba(255,255,255,0) 0%);
            background-position: bottom;
            background-size: 3px 1px;
            background-repeat: repeat-x;
        }
        .dotted-row {
            border-bottom: 1px dashed #000;
            height: 20px;
            margin-bottom: 5px;
        }

        /* VERIFIKASI */
        .checkbox-area {
            margin: 10px 0;
        }
        .box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 5px;
            vertical-align: middle;
        }

        /* TTD */
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ttd-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 15px;
            border-right: 1px solid #000;
        }
        .ttd-table td:last-child {
            border-right: none;
        }
        .ttd-space {
            height: 70px;
        }
        .ttd-name {
            margin-bottom: 10px;
        }
        .ttd-details {
            text-align: left;
            font-size: 10pt;
            margin-left: 20%;
        }

        /* FOOTER */
        .footer {
            margin-top: 5px;
            font-size: 8pt;
            color: #666;
            text-align: left;
        }
    </style>
</head>
<body>

    @php
        $bagianPelapor = str_replace('LAB ', '', strtoupper($lab));
    @endphp

    <div class="container">
        <!-- HEADER -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <div class="logo-text">UDINUS</div>
                </td>
                <td class="title-cell">
                    <h2>LABORATORIUM KOMPUTER FIK UDINUS</h2>
                    <h3>FORM PERMINTAAN TINDAKAN PERBAIKAN DAN PENCEGAHAN<br>(SOFTWARE DAN HARDWARE)</h3>
                </td>
                <td class="meta-cell">
                    <table class="meta-table">
                        <tr><td>Nomor</td><td>: F-LAB.KOM-UDINUS-01</td></tr>
                        <tr><td>Revisi</td><td>: 0</td></tr>
                        <tr><td>Tanggal Berlaku</td><td>: 19 September 2022</td></tr>
                        <tr><td>Halaman</td><td>: 1 dari 1</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- INFORMASI UTAMA -->
        <div class="section">
            <table class="info-table">
                <tr>
                    <td class="info-label">Laboratorium</td>
                    <td class="info-colon">:</td>
                    <td>{{ $lab }}</td>
                </tr>
                <tr>
                    <td class="info-label">Pelapor</td>
                    <td class="info-colon">:</td>
                    <td>{{ $pelapor }}</td>
                </tr>
                <tr>
                    <td class="info-label">Tanggal Pengajuan</td>
                    <td class="info-colon">:</td>
                    <td>{{ $tanggal }}</td>
                </tr>
                <tr>
                    <td class="info-label">Status</td>
                    <td class="info-colon">:</td>
                    <td>{{ $status }}</td>
                </tr>
                <tr>
                    <td class="info-label">Prioritas</td>
                    <td class="info-colon">:</td>
                    <td>Sedang</td>
                </tr>
            </table>
        </div>

        <!-- URAIAN PENGAMATAN -->
        <div class="section">
            <div class="section-title">Hasil / Uraian Pengamatan Ketidaksesuaian / Potensi Ketidaksesuaian:</div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">No PC</th>
                        <th width="15%">Kode PC</th>
                        <th width="25%">Komponen (Kondisi)</th>
                        <th width="40%">Keterangan Kerusakan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="center">1</td>
                        <td class="center">{{ $no_pc }}</td>
                        <td class="center">{{ $kode_pc ?? '-' }}</td>
                        <td>{{ $komponen }} ({{ $kondisi }})</td>
                        <td>{!! nl2br(e($keterangan)) !!}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TINDAKAN YANG DIAJUKAN -->
        <div class="section">
            <div class="section-title">Tindakan Langsung / Yang Diajukan:</div>
            <div style="padding-left: 10px; font-size: 10pt;">
                @php
                    $komps = explode(',', $komponen);
                    foreach($komps as $k) {
                        echo "- Pengecekan dan perbaikan/penggantian " . trim($k) . "<br>";
                    }
                @endphp
            </div>
            <div class="dotted-row" style="margin-top:10px;"></div>
        </div>

        <!-- PERMINTAAN TINDAKAN PERBAIKAN (MANUAL) -->
        <div class="section">
            <div class="section-title">Permintaan Tindakan Perbaikan dan Pencegahan (Diisi oleh Verifikator):</div>
            <div class="dotted-row" style="margin-top:10px;"></div>
            <div class="dotted-row"></div>
            <div class="dotted-row"></div>
        </div>

        <!-- VERIFIKASI -->
        <div class="section">
            <div class="section-title">Verifikasi Hasil Tindakan:</div>
            <div class="checkbox-area">
                <span style="margin-right: 30px;"><span class="box"></span> Diterima</span>
                <span><span class="box"></span> Ditolak</span>
            </div>
            <div class="section-title" style="margin-top: 10px;">Evaluasi & Efektifitas Hasil Tindakan:</div>
            <div class="dotted-row"></div>
            <div class="dotted-row"></div>
        </div>

        <!-- TANDA TANGAN -->
        <div class="section no-border-bottom" style="padding: 0;">
            <table class="ttd-table">
                <tr>
                    <td>
                        <div>Pelapor</div>
                        <div class="ttd-space"></div>
                        <div class="ttd-name">( ..................................................... )</div>
                        <div class="ttd-details">
                            Bagian&nbsp;&nbsp;&nbsp;&nbsp;: {{ $bagianPelapor }}<br>
                            Jabatan&nbsp;&nbsp;: Laboran
                        </div>
                    </td>
                    <td>
                        <div>Disetujui</div>
                        <div class="ttd-space"></div>
                        <div class="ttd-name">( ..................................................... )</div>
                        <div class="ttd-details">
                            Bagian&nbsp;&nbsp;&nbsp;&nbsp;: UPT Laboratorium<br>
                            Jabatan&nbsp;&nbsp;: Super Admin
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="footer">
        Dokumen dihasilkan otomatis oleh Sistem SIOPAL.
    </div>

</body>
</html>
