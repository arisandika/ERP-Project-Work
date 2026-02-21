<!DOCTYPE html>
<html>
<body>
    <h2>Halo, <?php echo e($invoice->customer->name); ?></h2>
    <p>Kami ingin mengingatkan bahwa tagihan berikut telah jatuh tempo:</p>

    <ul>
        <li><strong>No. Invoice:</strong> <?php echo e($invoice->invoice_number); ?></li>
        <li><strong>Tanggal Jatuh Tempo:</strong> <?php echo e($invoice->due_date->format('d M Y')); ?></li>
        <li><strong>Total Tagihan:</strong> Rp <?php echo e(number_format($invoice->grand_total, 0, ',', '.')); ?></li>
    </ul>

    <p>Mohon segera melakukan pembayaran.</p>
    <p>Terima kasih,<br>Tim Nexicon</p>
</body>
</html>
<?php /**PATH C:\laragon\www\erp-app\resources\views\emails\invoice-reminder.blade.php ENDPATH**/ ?>