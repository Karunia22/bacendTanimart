<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px; }
        th { background: #f2f2f2; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h3>{{ $title }}</h3>
    <p>Dicetak: {{ $generatedAt->format('Y-m-d H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>No. Order</th>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
            <tr>
                <td>{{ $item->nomor_order }}</td>
                <td>{{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') }}</td>
                <td>{{ $item->nama_produk }}</td>
                <td class="right">{{ $item->jumlah }}</td>
                <td class="right">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                <td>{{ $item->status }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5">Total</th>
                <th class="right">Rp {{ number_format($total, 0, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
