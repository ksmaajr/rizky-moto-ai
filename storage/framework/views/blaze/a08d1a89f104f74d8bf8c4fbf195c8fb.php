<?php
if (!function_exists('__a08d1a89f104f74d8bf8c4fbf195c8fb')):
function __a08d1a89f104f74d8bf8c4fbf195c8fb($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
extract(Flux::forwardedAttributes($attributes, [
    'tooltipPosition',
    'tooltipKbd',
    'tooltip',
]));
?>

<?php $tooltipPosition = $tooltipPosition ??= $attributes->pluck('tooltip:position'); ?>
<?php $tooltipKbd = $tooltipKbd ??= $attributes->pluck('tooltip:kbd'); ?>
<?php $tooltip = $tooltip ??= $attributes->pluck('tooltip'); ?>

<?php
$__defaults = [
    'tooltipPosition' => 'top',
    'tooltipKbd' => null,
    'tooltip' => null,
];
$tooltipPosition ??= $attributes['tooltip-position'] ?? $attributes['tooltipPosition'] ?? $__defaults['tooltipPosition']; unset($attributes['tooltipPosition'], $attributes['tooltip-position']);
$tooltipKbd ??= $attributes['tooltip-kbd'] ?? $attributes['tooltipKbd'] ?? $__defaults['tooltipKbd']; unset($attributes['tooltipKbd'], $attributes['tooltip-kbd']);
$tooltip ??= $attributes['tooltip'] ?? $__defaults['tooltip']; unset($attributes['tooltip']);
unset($__defaults);
?>

<?php if ($tooltip): ?>
    <?php if (!function_exists('__dfd2184989fa5e11a0c987d1c0b86139')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/index.blade.php', $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'); require $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'; } ?>
<?php if (isset($__slotsdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__slotsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php if (isset($__attrsdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__attrsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php $__attrsdfd2184989fa5e11a0c987d1c0b86139 = ['class' => 'inline-flex','content' => $tooltip,'position' => $tooltipPosition,'kbd' => $tooltipKbd]; ?>
<?php $__slotsdfd2184989fa5e11a0c987d1c0b86139 = []; ?>
<?php $__blaze->pushData($__attrsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php ob_start(); ?>
        <?php echo e($slot); ?>

    <?php $__slotsdfd2184989fa5e11a0c987d1c0b86139['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slotsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php __dfd2184989fa5e11a0c987d1c0b86139($__blaze, $__attrsdfd2184989fa5e11a0c987d1c0b86139, $__slotsdfd2184989fa5e11a0c987d1c0b86139, ['content', 'position', 'kbd'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__slotsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php if (! empty($__attrsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__attrsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php echo e($slot); ?>

<?php endif; ?>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-tooltip.blade.php ENDPATH**/ ?>