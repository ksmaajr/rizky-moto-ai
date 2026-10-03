<?php
if (!function_exists('_fb56abdd7371614944568b6fc59d433b')):
function _fb56abdd7371614944568b6fc59d433b($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php
$__defaults = [
    'key' => null,
    'sticky' => false,
];
$key ??= $attributes['key'] ?? $__defaults['key']; unset($attributes['key']);
$sticky ??= $attributes['sticky'] ?? $__defaults['sticky']; unset($attributes['sticky']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add($sticky ? 'last:sticky last:bottom-0 last:z-20' : '')
    ;
?>

<tr <?php if($key): ?> <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'table-'.e($key).''; ?>wire:key="table-<?php echo e($key); ?>" <?php endif; ?> <?php echo e($attributes->class($classes)); ?> data-flux-row>
    <?php echo e($slot); ?>

</tr>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\table\row.blade.php ENDPATH**/ ?>