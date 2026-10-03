<?php
if (!function_exists('_7ea6d629272c74cf8663b23a9fd68d4c')):
function _7ea6d629272c74cf8663b23a9fd68d4c($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$__awareDefaults = [ 'variant' ];
$variant = $__blaze->getConsumableData('variant');
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
$variant = $variant !== 'default' && Flux::componentExists('checkbox.variants.' . $variant)
    ? $variant
    : 'default';
?>

<?php $__resolved = $__blaze->resolve('flux::' . 'checkbox.variants.' . $variant); ?>
<?php $__delegatedData = $__blaze->unescapeAttributes($attributes->getAttributes()); ?>
<?php $__blaze->pushData($__delegatedData); ?>
<?php if ($__resolved !== false): ?>
<?php if (isset($__slots8e6df5022b57026f91a88d539e0a7dc8)) { $__slotsStack8e6df5022b57026f91a88d539e0a7dc8[] = $__slots8e6df5022b57026f91a88d539e0a7dc8; } ?>
<?php $__slots8e6df5022b57026f91a88d539e0a7dc8 = []; ?>
<?php ob_start(); ?><?php echo e($slot); ?><?php $__slots8e6df5022b57026f91a88d539e0a7dc8['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__slots8e6df5022b57026f91a88d539e0a7dc8 = array_merge($__blaze->mergedComponentSlots(), $__slots8e6df5022b57026f91a88d539e0a7dc8); ?>
<?php ('_' . $__resolved)($__blaze, $__delegatedData, $__slots8e6df5022b57026f91a88d539e0a7dc8, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack8e6df5022b57026f91a88d539e0a7dc8)) { $__slots8e6df5022b57026f91a88d539e0a7dc8 = array_pop($__slotsStack8e6df5022b57026f91a88d539e0a7dc8); } ?>
<?php else: ?>
<?php if (!Flux::componentExists($name = 'checkbox.variants.' . $variant)) throw new \Exception("Flux component [{$name}] does not exist."); ?><?php if (isset($component)) { $__componentOriginale96b33a22b0abf65a3b62dc1a6ecf912 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale96b33a22b0abf65a3b62dc1a6ecf912 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve([
    'view' => (app()->version() >= 12 ? hash('xxh128', 'flux') : md5('flux')) . '::' . 'checkbox.variants.' . $variant,
    'data' => $__env->getCurrentComponentData(),
] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::' . 'checkbox.variants.' . $variant); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes($attributes->getAttributes()); ?><?php echo e($slot); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale96b33a22b0abf65a3b62dc1a6ecf912)): ?>
<?php $attributes = $__attributesOriginale96b33a22b0abf65a3b62dc1a6ecf912; ?>
<?php unset($__attributesOriginale96b33a22b0abf65a3b62dc1a6ecf912); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale96b33a22b0abf65a3b62dc1a6ecf912)): ?>
<?php $component = $__componentOriginale96b33a22b0abf65a3b62dc1a6ecf912; ?>
<?php unset($__componentOriginale96b33a22b0abf65a3b62dc1a6ecf912); ?>
<?php endif; ?>
<?php endif; ?>
<?php $__blaze->popData(); ?>
<?php unset($__resolved, $__delegatedData) ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\checkbox\index.blade.php ENDPATH**/ ?>