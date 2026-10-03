<?php
if (!function_exists('_80a956eb7b851cb09e5c5c986297e109')):
function _80a956eb7b851cb09e5c5c986297e109($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$__awareDefaults = [ 'variant', 'size', 'indicator' ];
$variant = $__blaze->getConsumableData('variant');
$size = $__blaze->getConsumableData('size');
$indicator = $__blaze->getConsumableData('indicator');
unset($__awareDefaults);
?>

<?php
$__defaults = [
    'variant' => 'default',
];
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
unset($__defaults);
?>

<?php
// This prevents variants picked up by `@aware()` from other wrapping components like flux::modal from being used here...
$variant = $variant !== 'default' && Flux::componentExists('radio.variants.' . $variant)
    ? $variant
    : 'default';
?>

<?php $__resolved = $__blaze->resolve('flux::' . 'radio.variants.' . $variant); ?>
<?php $__delegatedData = $__blaze->unescapeAttributes($attributes->getAttributes()); ?>
<?php $__blaze->pushData($__delegatedData); ?>
<?php if ($__resolved !== false): ?>
<?php if (isset($__slotsf8c87abf09e99b80a7402be5146c697b)) { $__slotsStackf8c87abf09e99b80a7402be5146c697b[] = $__slotsf8c87abf09e99b80a7402be5146c697b; } ?>
<?php $__slotsf8c87abf09e99b80a7402be5146c697b = []; ?>
<?php ob_start(); ?><?php echo e($slot); ?><?php $__slotsf8c87abf09e99b80a7402be5146c697b['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__slotsf8c87abf09e99b80a7402be5146c697b = array_merge($__blaze->mergedComponentSlots(), $__slotsf8c87abf09e99b80a7402be5146c697b); ?>
<?php ('_' . $__resolved)($__blaze, $__delegatedData, $__slotsf8c87abf09e99b80a7402be5146c697b, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackf8c87abf09e99b80a7402be5146c697b)) { $__slotsf8c87abf09e99b80a7402be5146c697b = array_pop($__slotsStackf8c87abf09e99b80a7402be5146c697b); } ?>
<?php else: ?>
<?php if (!Flux::componentExists($name = 'radio.variants.' . $variant)) throw new \Exception("Flux component [{$name}] does not exist."); ?><?php if (isset($component)) { $__componentOriginal265f91d25e15cdf8789a0aea59dc939c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal265f91d25e15cdf8789a0aea59dc939c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve([
    'view' => (app()->version() >= 12 ? hash('xxh128', 'flux') : md5('flux')) . '::' . 'radio.variants.' . $variant,
    'data' => $__env->getCurrentComponentData(),
] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::' . 'radio.variants.' . $variant); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes($attributes->getAttributes()); ?><?php echo e($slot); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal265f91d25e15cdf8789a0aea59dc939c)): ?>
<?php $attributes = $__attributesOriginal265f91d25e15cdf8789a0aea59dc939c; ?>
<?php unset($__attributesOriginal265f91d25e15cdf8789a0aea59dc939c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal265f91d25e15cdf8789a0aea59dc939c)): ?>
<?php $component = $__componentOriginal265f91d25e15cdf8789a0aea59dc939c; ?>
<?php unset($__componentOriginal265f91d25e15cdf8789a0aea59dc939c); ?>
<?php endif; ?>
<?php endif; ?>
<?php $__blaze->popData(); ?>
<?php unset($__resolved, $__delegatedData) ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\radio\index.blade.php ENDPATH**/ ?>