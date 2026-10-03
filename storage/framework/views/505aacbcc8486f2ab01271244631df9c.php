<?php
if (!function_exists('_505aacbcc8486f2ab01271244631df9c')):
function _505aacbcc8486f2ab01271244631df9c($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'size' => null,
];
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
unset($__defaults);
?>

<?php
$classes = Flux::classes([
    'flex items-center px-4 whitespace-nowrap',
    'text-zinc-800 dark:text-zinc-200',
    'bg-zinc-800/5 dark:bg-white/20',
    'border-zinc-200 dark:border-white/10',
    'border-s border-t border-b shadow-xs',
])->add(match ($size) {
    default => 'text-base sm:text-sm rounded-s-lg',
    'sm' => 'text-sm rounded-s-md',
    'xs' => 'text-xs rounded-s-md',
});
?>

<div <?php echo e($attributes->class($classes)); ?> data-flux-input-group-prefix>
    <?php echo e($slot); ?>

</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\input\group\prefix.blade.php ENDPATH**/ ?>