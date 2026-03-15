<!DOCTYPE html>
<html>
<body>
    <?php
        // Ambil klien dari Deal (Customer jika ada, jika tidak ambil Lead)
        $client = $quotation->deal?->customer ?? $quotation->deal?->lead;
        $clientName = $client?->name ?? 'Bapak/Ibu';
    ?>
    <p>Halo <?php echo e($clientName); ?>,</p>
    <p>Berikut kami lampirkan penawaran dengan nomor <?php echo e($quotation->quotation_number); ?>.</p>
    <p>Terima kasih.</p>
</body>
</html>
<?php /**PATH D:\Nurul Fauziah\Project Work\Nexicon\ERP-Project-Work\resources\views/emails/quotation.blade.php ENDPATH**/ ?>