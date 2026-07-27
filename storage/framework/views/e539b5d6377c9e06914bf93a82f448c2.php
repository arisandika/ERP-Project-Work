<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'data'
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'data'
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<a x-data="{}"
    x-on:click.prevent="window.navigator.clipboard.writeText(<?php echo \Illuminate\Support\Js::from($data)->toHtml() ?>);$tooltip('<?php echo e(__('filament-breezy::default.clipboard.tooltip')); ?>');"
    href="#" class="flex items-center">
    <?php echo e(svg('heroicon-s-clipboard-document', 'w-4 mr-2')); ?>
    <span class=""><?php echo e(__('filament-breezy::default.clipboard.link')); ?></span>
</a>
<?php /**PATH /var/www/erp-app-main/vendor/jeffgreco13/filament-breezy/resources/views/components/clipboard-link.blade.php ENDPATH**/ ?>