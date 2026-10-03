<?php
if (!function_exists('_6dd4ab2ca8d1140a4a14557fcbcfaffd')):
function _6dd4ab2ca8d1140a4a14557fcbcfaffd($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'class' => '-me-1',
    'square' => true,
    'size' => null,
]);
?>

<?php if (!function_exists('_1c2056a1bf3bb0b63a18671dc5ed9823')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/button/index.blade.php', $__blaze->compiledPath.'/1c2056a1bf3bb0b63a18671dc5ed9823.php'); require $__blaze->compiledPath.'/1c2056a1bf3bb0b63a18671dc5ed9823.php'; } ?>
<?php if (isset($__slots1c2056a1bf3bb0b63a18671dc5ed9823)) { $__slotsStack1c2056a1bf3bb0b63a18671dc5ed9823[] = $__slots1c2056a1bf3bb0b63a18671dc5ed9823; } ?>
<?php if (isset($__attrs1c2056a1bf3bb0b63a18671dc5ed9823)) { $__attrsStack1c2056a1bf3bb0b63a18671dc5ed9823[] = $__attrs1c2056a1bf3bb0b63a18671dc5ed9823; } ?>
<?php $__attrs1c2056a1bf3bb0b63a18671dc5ed9823 = ['attributes' => $attributes,'size' => $size === 'sm' || $size === 'xs' ? 'xs' : 'sm','xData' => 'fluxInputViewable','xOn:click' => 'toggle()','xBind:dataViewableOpen' => 'open','ariaLabel' => e(__('Toggle password visibility'))]; ?>
<?php $__slots1c2056a1bf3bb0b63a18671dc5ed9823 = []; ?>
<?php $__blaze->pushData($__attrs1c2056a1bf3bb0b63a18671dc5ed9823); ?>
<?php ob_start(); ?>
    <?php if (!function_exists('_a041ddd088c74bd1200b4b5504aad1bd')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/eye-slash.blade.php', $__blaze->compiledPath.'/a041ddd088c74bd1200b4b5504aad1bd.php'); require $__blaze->compiledPath.'/a041ddd088c74bd1200b4b5504aad1bd.php'; } ?>
<?php $__blaze->pushData(['variant' => $iconVariant,'class' => 'hidden [[data-viewable-open]>&]:block']); ?>
<?php _a041ddd088c74bd1200b4b5504aad1bd($__blaze, ['variant' => $iconVariant,'class' => 'hidden [[data-viewable-open]>&]:block'], [], ['variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
    <?php if (!function_exists('_408d2657c7bad4bbc1db0e4ba9209c3e')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/eye.blade.php', $__blaze->compiledPath.'/408d2657c7bad4bbc1db0e4ba9209c3e.php'); require $__blaze->compiledPath.'/408d2657c7bad4bbc1db0e4ba9209c3e.php'; } ?>
<?php $__blaze->pushData(['variant' => $iconVariant,'class' => 'block [[data-viewable-open]>&]:hidden']); ?>
<?php _408d2657c7bad4bbc1db0e4ba9209c3e($__blaze, ['variant' => $iconVariant,'class' => 'block [[data-viewable-open]>&]:hidden'], [], ['variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
<?php $__slots1c2056a1bf3bb0b63a18671dc5ed9823['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots1c2056a1bf3bb0b63a18671dc5ed9823); ?>
<?php _1c2056a1bf3bb0b63a18671dc5ed9823($__blaze, $__attrs1c2056a1bf3bb0b63a18671dc5ed9823, $__slots1c2056a1bf3bb0b63a18671dc5ed9823, ['attributes', 'size'], ['xData' => 'x-data', 'xOn:click' => 'x-on:click', 'xBind:dataViewableOpen' => 'x-bind:data-viewable-open', 'ariaLabel' => 'aria-label'], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack1c2056a1bf3bb0b63a18671dc5ed9823)) { $__slots1c2056a1bf3bb0b63a18671dc5ed9823 = array_pop($__slotsStack1c2056a1bf3bb0b63a18671dc5ed9823); } ?>
<?php if (! empty($__attrsStack1c2056a1bf3bb0b63a18671dc5ed9823)) { $__attrs1c2056a1bf3bb0b63a18671dc5ed9823 = array_pop($__attrsStack1c2056a1bf3bb0b63a18671dc5ed9823); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\input\viewable.blade.php ENDPATH**/ ?>