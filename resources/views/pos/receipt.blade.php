<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $transaction->midtrans_order_id }}</title>
    <!-- Tailwind for base styling -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- QRCode & jsPDF JS Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #f3f4f6; }
        .receipt-container {
            width: 58mm;
            min-height: 100mm;
            margin: 20px auto;
            background: #fff;
            padding: 5mm;
            font-size: 11px;
            color: #000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header { text-align: center; margin-bottom: 5mm; }
        .header h1 { font-size: 14px; font-weight: bold; margin: 0; }
        .header p { margin: 2px 0; }
        
        .divider { border-top: 1px dashed #000; margin: 5mm 0; }
        
        table { width: 100%; }
        th, td { text-align: left; padding: 2px 0; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .item-name { max-width: 30mm; word-wrap: break-word; }
        
        #qrcode { margin-top: 5mm; display: flex; justify-content: center; }
        #qrcode img { width: 30mm; height: 30mm; }

        .actions {
            width: 58mm;
            margin: 0 auto 20px auto;
            display: flex;
            gap: 10px;
        }

        /* Print Media Defaults */
        @media print {
            body { background: transparent; }
            .receipt-container { margin: 0; box-shadow: none; border: none; padding: 0; width: 58mm; }
            .actions { display: none; }
            @page {
                size: 58mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="actions">
        <button onclick="window.print()" class="flex-1 bg-blue-600 text-white font-bold py-2 rounded shadow hover:bg-blue-700 font-sans text-sm">
            Print Thermal
        </button>
        <button onclick="downloadPDF()" class="flex-1 bg-green-600 text-white font-bold py-2 rounded shadow hover:bg-green-700 font-sans text-sm">
            Save PDF
        </button>
    </div>

    <!-- This is the container that gets printed by Thermal or captured by HTML2Canvas -->
    <div id="receipt-capture" class="receipt-container">
        <div class="header">
            <h1>TUGAS POS</h1>
            <p>Jalan Laravel No. 13</p>
            <p>Telp: 0812-3456-7890</p>
        </div>
        
        <div class="divider"></div>
        
        <table style="margin-bottom: 5mm;">
            <tr>
                <td>ID</td>
                <td class="text-right">{{ $transaction->midtrans_order_id }}</td>
            </tr>
            <tr>
                <td>Tgl</td>
                <td class="text-right">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td class="text-right">{{ substr($transaction->user->name ?? 'Kasir', 0, 10) }}</td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <table>
            @foreach($transaction->items as $item)
            <tr>
                <td class="item-name">{{ $item->product->name ?? 'Item' }}</td>
                <td class="text-right">{{ $item->quantity }}x</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </table>
        
        <div class="divider"></div>
        
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="text-right">{{ number_format($transaction->total_amount - $transaction->tax_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tax (11%)</td>
                <td class="text-right">{{ number_format($transaction->tax_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="font-bold">Total</td>
                <td class="text-right font-bold">{{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Metode</td>
                <td class="text-right uppercase">{{ $transaction->payment_method }}</td>
            </tr>
        </table>

        <div class="divider"></div>
        
        <div class="text-center" style="margin-top: 5mm;">
            <p>Terima Kasih Atas<br>Kunjungan Anda</p>
        </div>

        <!-- Placeholder for QR Code generated by Javascript -->
        <div id="qrcode"></div>
    </div>

    <script>
        // Generate QR Code containing the Invoice ID
        window.onload = function() {
            new QRCode(document.getElementById("qrcode"), {
                text: "{{ $transaction->midtrans_order_id }}",
                width: 100,
                height: 100,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        };

        // Download as PDF logic
        async function downloadPDF() {
            const { jsPDF } = window.jspdf;
            
            // Capture HTML Element with better scaling
            const element = document.getElementById('receipt-capture');
            const canvas = await html2canvas(element, { scale: 3 });
            const imgData = canvas.toDataURL('image/png');
            
            // PDF sizing (58mm width roughly equals 164.4 points in jsPDF, height scales proportionally)
            const pdfWidth = 58;
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: [pdfWidth, pdfHeight]
            });
            
            doc.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            doc.save('struk_{{ $transaction->midtrans_order_id }}.pdf');
        }
    </script>
</body>
</html>
