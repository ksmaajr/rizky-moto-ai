<?php
if (!function_exists('_f9a6b86c1d9ca94a213502dfd98c67ac')):
function _f9a6b86c1d9ca94a213502dfd98c67ac($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$classes = Flux::classes('[grid-area:footer]')
    ->add($attributes->has('container') ? '' : 'p-6 lg:p-8')
    ;
?>

<div <?php echo e($attributes->class($classes)); ?> data-flux-footer>
    <?php if (!function_exists('_513d43e7ea202778414bed5645c9f8e3')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-container.blade.php', $__blaze->compiledPath.'/513d43e7ea202778414bed5645c9f8e3.php'); require $__blaze->compiledPath.'/513d43e7ea202778414bed5645c9f8e3.php'; } ?>
<?php if (isset($__slots513d43e7ea202778414bed5645c9f8e3)) { $__slotsStack513d43e7ea202778414bed5645c9f8e3[] = $__slots513d43e7ea202778414bed5645c9f8e3; } ?>
<?php if (isset($__attrs513d43e7ea202778414bed5645c9f8e3)) { $__attrsStack513d43e7ea202778414bed5645c9f8e3[] = $__attrs513d43e7ea202778414bed5645c9f8e3; } ?>
<?php $__attrs513d43e7ea202778414bed5645c9f8e3 = ['attributes' => $attributes->except('class')->class('p-6 lg:p-8')]; ?>
<?php $__slots513d43e7ea202778414bed5645c9f8e3 = []; ?>
<?php $__blaze->pushData($__attrs513d43e7ea202778414bed5645c9f8e3); ?>
<?php ob_start(); ?>
        <?php echo e($slot); ?>

    <?php $__slots513d43e7ea202778414bed5645c9f8e3['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots513d43e7ea202778414bed5645c9f8e3); ?>
<?php _513d43e7ea202778414bed5645c9f8e3($__blaze, $__attrs513d43e7ea202778414bed5645c9f8e3, $__slots513d43e7ea202778414bed5645c9f8e3, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack513d43e7ea202778414bed5645c9f8e3)) { $__slots513d43e7ea202778414bed5645c9f8e3 = array_pop($__slotsStack513d43e7ea202778414bed5645c9f8e3); } ?>
<?php if (! empty($__attrsStack513d43e7ea202778414bed5645c9f8e3)) { $__attrs513d43e7ea202778414bed5645c9f8e3 = array_pop($__attrsStack513d43e7ea202778414bed5645c9f8e3); } ?>
<?php $__blaze->popData(); ?>
</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\footer.blade.php ENDPATH**/ ?>