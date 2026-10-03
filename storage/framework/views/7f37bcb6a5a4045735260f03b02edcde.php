<?php
if (!function_exists('_7f37bcb6a5a4045735260f03b02edcde')):
function _7f37bcb6a5a4045735260f03b02edcde($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $srOnly = $srOnly ??= $attributes->pluck('sr-only'); ?>

<?php
$__defaults = [
    'srOnly' => null,
];
$srOnly ??= $attributes['sr-only'] ?? $attributes['srOnly'] ?? $__defaults['srOnly']; unset($attributes['srOnly'], $attributes['sr-only']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('text-sm text-zinc-500 dark:text-white/60')
    ->add($srOnly ? 'sr-only' : '')
    ;
?>

<ui-description <?php echo e($attributes->class($classes)); ?> data-flux-description>
    <?php echo e($slot); ?>

</ui-description>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\description.blade.php ENDPATH**/ ?>