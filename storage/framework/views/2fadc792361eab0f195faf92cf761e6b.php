<?php
if (!function_exists('_2fadc792361eab0f195faf92cf761e6b')):
function _2fadc792361eab0f195faf92cf761e6b($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'external' => null,
];
$external ??= $attributes['external'] ?? $__defaults['external']; unset($attributes['external']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('inline font-medium')
    ->add('underline underline-offset-[6px] hover:decoration-current')
    ->add('decoration-zinc-800/20 dark:decoration-white/20')
    ;
?>

<a <?php echo e($attributes->class($classes)); ?> <?php if ($external) : ?>target="_blank"<?php endif; ?>><?php echo e($slot); ?></a><?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\callout\link.blade.php ENDPATH**/ ?>