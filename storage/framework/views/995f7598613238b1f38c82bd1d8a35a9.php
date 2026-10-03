<?php
if (!function_exists('_995f7598613238b1f38c82bd1d8a35a9')):
function _995f7598613238b1f38c82bd1d8a35a9($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$classes = Flux::classes([
    'p-2 pb-1 w-full',
    'flex items-center',
    'text-start text-xs font-medium',
    'text-zinc-500 font-medium dark:text-zinc-300',
]);
?>

<div <?php echo e($attributes->class($classes)); ?> data-flux-menu-heading>
    <div class="w-7 hidden [[data-flux-menu]:has(>[data-flux-menu-item-has-icon])_&]:block"></div>

    <div><?php echo e($slot); ?></div>
</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\menu\heading.blade.php ENDPATH**/ ?>