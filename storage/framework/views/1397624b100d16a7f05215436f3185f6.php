<?php
if (!function_exists('_1397624b100d16a7f05215436f3185f6')):
function _1397624b100d16a7f05215436f3185f6($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'variant' => 'default',
];
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
unset($__defaults);
?>

<?php $__resolved = $__blaze->resolve('flux::' . 'checkbox.group.variants.' . $variant); ?>
<?php $__delegatedData = $__blaze->unescapeAttributes($attributes->getAttributes()); ?>
<?php $__blaze->pushData($__delegatedData); ?>
<?php if ($__resolved !== false): ?>
<?php if (isset($__slotsa68338081a999623c5d5beadbbe7e044)) { $__slotsStacka68338081a999623c5d5beadbbe7e044[] = $__slotsa68338081a999623c5d5beadbbe7e044; } ?>
<?php $__slotsa68338081a999623c5d5beadbbe7e044 = []; ?>
<?php ob_start(); ?><?php echo e($slot); ?><?php $__slotsa68338081a999623c5d5beadbbe7e044['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__slotsa68338081a999623c5d5beadbbe7e044 = array_merge($__blaze->mergedComponentSlots(), $__slotsa68338081a999623c5d5beadbbe7e044); ?>
<?php ('_' . $__resolved)($__blaze, $__delegatedData, $__slotsa68338081a999623c5d5beadbbe7e044, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStacka68338081a999623c5d5beadbbe7e044)) { $__slotsa68338081a999623c5d5beadbbe7e044 = array_pop($__slotsStacka68338081a999623c5d5beadbbe7e044); } ?>
<?php else: ?>
<?php if (!Flux::componentExists($name = 'checkbox.group.variants.' . $variant)) throw new \Exception("Flux component [{$name}] does not exist."); ?><?php if (isset($component)) { $__componentOriginal4c7f570a6fa628b9904d8cf142be6c8b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4c7f570a6fa628b9904d8cf142be6c8b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve([
    'view' => (app()->version() >= 12 ? hash('xxh128', 'flux') : md5('flux')) . '::' . 'checkbox.group.variants.' . $variant,
    'data' => $__env->getCurrentComponentData(),
] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::' . 'checkbox.group.variants.' . $variant); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes($attributes->getAttributes()); ?><?php echo e($slot); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4c7f570a6fa628b9904d8cf142be6c8b)): ?>
<?php $attributes = $__attributesOriginal4c7f570a6fa628b9904d8cf142be6c8b; ?>
<?php unset($__attributesOriginal4c7f570a6fa628b9904d8cf142be6c8b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4c7f570a6fa628b9904d8cf142be6c8b)): ?>
<?php $component = $__componentOriginal4c7f570a6fa628b9904d8cf142be6c8b; ?>
<?php unset($__componentOriginal4c7f570a6fa628b9904d8cf142be6c8b); ?>
<?php endif; ?>
<?php endif; ?>
<?php $__blaze->popData(); ?>
<?php unset($__resolved, $__delegatedData) ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\checkbox\group\index.blade.php ENDPATH**/ ?>