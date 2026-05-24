<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengajuan Kerusakan PC</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #666;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .info-table th, .info-table td {
            padding: 10px 15px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }

        .info-table th {
            background-color: #f7f7f7;
            width: 30%;
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            width: 100%;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-top: 50px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
            font-size: 12px;
        }

        .status-menunggu {
            background-color: #f0f0f0;
            color: #666;
            border: 1px solid #ccc;
        }

        .status-diproses {
            background-color: #fef3c7;
            color: #d97706;
            border: 1px solid #f59e0b;
        }

        .status-selesai {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #22c55e;
        }

        .status-ditolak {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #ef4444;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laboratorium Komputer FIK UDINUS</h2>
        <p>Jl. Imam Bonjol No. 207, Semarang - Telp. (024) 3517261</p>
    </div>

    <div class="title">
        Laporan Pengajuan Kerusakan / Perbaikan PC
    </div>

    <table class="info-table">
        <tr>
            <th>Laboratorium / Ruang</th>
            <td>{{ $lab }}</td>
        </tr>
        <tr>
            <th>Nomor PC</th>
            <td>{{ $no_pc }}</td>
        </tr>
        <tr>
            <th>Komponen Bermasalah</th>
            <td>{{ $komponen }}</td>
        </tr>
        <tr>
            <th>Kondisi Terakhir</th>
            <td><strong style="color: #b91c1c;">{{ $kondisi }}</strong></td>
        </tr>
        <tr>
            <th>Periode Rekap Bulanan</th>
            <td>{{ $periode }}</td>
        </tr>
        <tr>
            <th>Keterangan / Deskripsi</th>
            <td>{!! nl2br(e($keterangan)) !!}</td>
        </tr>
        <tr>
            <th>Tanggal Pengajuan</th>
            <td>{{ $tanggal }}</td>
        </tr>
        <tr>
            <th>Pelapor (Laboran)</th>
            <td>{{ $pelapor }}</td>
        </tr>
        <tr>
            <th>Status Pengajuan</th>
            <td>
                @php
                    $statusClass = 'status-' . strtolower(str_replace(' ', '', $status));
                @endphp
                <span class="status-badge {{ $statusClass }}">{{ $status }}</span>
            </td>
        </tr>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td>
                    <p>Pelapor,</p>
                    <br><br><br>
                    <p><strong>( {{ $pelapor }} )</strong></p>
                    <p style="font-size: 12px; color: #666;">Laboran Ruang</p>
                </td>
                <td>
                    <p>Mengetahui,</p>
                    <br><br><br>
                    <p><strong>( Super Admin )</strong></p>
                    <p style="font-size: 12px; color: #666;">Kepala Laboratorium Komputer</p>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
