<!DOCTYPE html>
<html>
<body>
    <p>Halo <?php echo e($quotation->customer->name); ?>,</p>
    <p>Berikut kami lampirkan penawaran dengan nomor <?php echo e($quotation->quotation_number); ?>.</p>
    <p>Terima kasih.</p>
</body>
</html>
<?php /**PATH C:\laragon\www\erp-app\resources\views\emails\quotation.blade.php ENDPATH**/ ?>