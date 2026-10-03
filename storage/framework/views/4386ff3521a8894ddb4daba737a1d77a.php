<?php
if (!function_exists('_4386ff3521a8894ddb4daba737a1d77a')):
function _4386ff3521a8894ddb4daba737a1d77a($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'border-e border-t border-b shadow-xs',
])->add(match ($size) {
    default => 'text-base sm:text-sm rounded-e-lg',
    'sm' => 'text-sm rounded-e-md',
    'xs' => 'text-xs rounded-e-md',
});
?>

<div <?php echo e($attributes->class($classes)); ?> data-flux-input-group-suffix>
    <?php echo e($slot); ?>

</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\input\group\suffix.blade.php ENDPATH**/ ?>