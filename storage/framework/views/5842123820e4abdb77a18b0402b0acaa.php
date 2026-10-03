<?php
if (!function_exists('_5842123820e4abdb77a18b0402b0acaa')):
function _5842123820e4abdb77a18b0402b0acaa($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;

if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php
$__defaults = [
    'iconVariant' => 'mini',
    'size' => null,
];
$iconVariant ??= $attributes['icon-variant'] ?? $attributes['iconVariant'] ?? $__defaults['iconVariant']; unset($attributes['iconVariant'], $attributes['icon-variant']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
unset($__defaults);
?>

<?php
$attributes = $attributes->merge([
    'variant' => 'subtle',
    'class' => '-me-1 [[data-flux-input]:has(input:placeholder-shown)_&]:hidden [[data-flux-input]:has(input[disabled])_&]:hidden',
    'square' => true,
    'size' => null,
]);
?>

<?php if (!function_exists('_1c2056a1bf3bb0b63a18671dc5ed9823')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/button/index.blade.php', $__blaze->compiledPath.'/1c2056a1bf3bb0b63a18671dc5ed9823.php'); require $__blaze->compiledPath.'/1c2056a1bf3bb0b63a18671dc5ed9823.php'; } ?>
<?php if (isset($__slots1c2056a1bf3bb0b63a18671dc5ed9823)) { $__slotsStack1c2056a1bf3bb0b63a18671dc5ed9823[] = $__slots1c2056a1bf3bb0b63a18671dc5ed9823; } ?>
<?php if (isset($__attrs1c2056a1bf3bb0b63a18671dc5ed9823)) { $__attrsStack1c2056a1bf3bb0b63a18671dc5ed9823[] = $__attrs1c2056a1bf3bb0b63a18671dc5ed9823; } ?>
<?php $__attrs1c2056a1bf3bb0b63a18671dc5ed9823 = ['attributes' => $attributes,'size' => $size === 'sm' || $size === 'xs' ? 'xs' : 'sm','xData' => 'fluxInputClearable','xOn:click' => 'clear()','tabindex' => '-1','ariaLabel' => e(__('Clear input')),'dataFluxClearButton' => true]; ?>
<?php $__slots1c2056a1bf3bb0b63a18671dc5ed9823 = []; ?>
<?php $__blaze->pushData($__attrs1c2056a1bf3bb0b63a18671dc5ed9823); ?>
<?php ob_start(); ?>
    <?php if (!function_exists('_73f709e1a2f1e6127cef68ab22ebb6e5')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/x-mark.blade.php', $__blaze->compiledPath.'/73f709e1a2f1e6127cef68ab22ebb6e5.php'); require $__blaze->compiledPath.'/73f709e1a2f1e6127cef68ab22ebb6e5.php'; } ?>
<?php $__blaze->pushData(['variant' => $iconVariant]); ?>
<?php _73f709e1a2f1e6127cef68ab22ebb6e5($__blaze, ['variant' => $iconVariant], [], ['variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
<?php $__slots1c2056a1bf3bb0b63a18671dc5ed9823['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots1c2056a1bf3bb0b63a18671dc5ed9823); ?>
<?php _1c2056a1bf3bb0b63a18671dc5ed9823($__blaze, $__attrs1c2056a1bf3bb0b63a18671dc5ed9823, $__slots1c2056a1bf3bb0b63a18671dc5ed9823, ['attributes', 'size', 'dataFluxClearButton'], ['xData' => 'x-data', 'xOn:click' => 'x-on:click', 'ariaLabel' => 'aria-label', 'dataFluxClearButton' => 'data-flux-clear-button'], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack1c2056a1bf3bb0b63a18671dc5ed9823)) { $__slots1c2056a1bf3bb0b63a18671dc5ed9823 = array_pop($__slotsStack1c2056a1bf3bb0b63a18671dc5ed9823); } ?>
<?php if (! empty($__attrsStack1c2056a1bf3bb0b63a18671dc5ed9823)) { $__attrs1c2056a1bf3bb0b63a18671dc5ed9823 = array_pop($__attrsStack1c2056a1bf3bb0b63a18671dc5ed9823); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\input\clearable.blade.php ENDPATH**/ ?>