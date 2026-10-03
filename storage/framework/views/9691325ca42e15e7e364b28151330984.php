<?php
if (!function_exists('_9691325ca42e15e7e364b28151330984')):
function _9691325ca42e15e7e364b28151330984($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$__awareDefaults = [ 'variant', 'indicator' ];
$variant = $__blaze->getConsumableData('variant');
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
$variant = $variant !== 'default' && Flux::componentExists('select.variants.' . $variant)
    ? 'custom'
    : 'default';
?>

<?php $__resolved = $__blaze->resolve('flux::' . 'select.option.variants.' . $variant); ?>
<?php $__delegatedData = $__blaze->unescapeAttributes($attributes->getAttributes()); ?>
<?php $__blaze->pushData($__delegatedData); ?>
<?php if ($__resolved !== false): ?>
<?php if (isset($__slots270aed46ead0eaa26bbb7430286841b3)) { $__slotsStack270aed46ead0eaa26bbb7430286841b3[] = $__slots270aed46ead0eaa26bbb7430286841b3; } ?>
<?php $__slots270aed46ead0eaa26bbb7430286841b3 = []; ?>
<?php ob_start(); ?><?php echo e($slot); ?><?php $__slots270aed46ead0eaa26bbb7430286841b3['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__slots270aed46ead0eaa26bbb7430286841b3 = array_merge($__blaze->mergedComponentSlots(), $__slots270aed46ead0eaa26bbb7430286841b3); ?>
<?php ('_' . $__resolved)($__blaze, $__delegatedData, $__slots270aed46ead0eaa26bbb7430286841b3, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack270aed46ead0eaa26bbb7430286841b3)) { $__slots270aed46ead0eaa26bbb7430286841b3 = array_pop($__slotsStack270aed46ead0eaa26bbb7430286841b3); } ?>
<?php else: ?>
<?php if (!Flux::componentExists($name = 'select.option.variants.' . $variant)) throw new \Exception("Flux component [{$name}] does not exist."); ?><?php if (isset($component)) { $__componentOriginal0c793dac3b0f3858ef7a5fdb4e001894 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c793dac3b0f3858ef7a5fdb4e001894 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve([
    'view' => (app()->version() >= 12 ? hash('xxh128', 'flux') : md5('flux')) . '::' . 'select.option.variants.' . $variant,
    'data' => $__env->getCurrentComponentData(),
] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::' . 'select.option.variants.' . $variant); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes($attributes->getAttributes()); ?><?php echo e($slot); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c793dac3b0f3858ef7a5fdb4e001894)): ?>
<?php $attributes = $__attributesOriginal0c793dac3b0f3858ef7a5fdb4e001894; ?>
<?php unset($__attributesOriginal0c793dac3b0f3858ef7a5fdb4e001894); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c793dac3b0f3858ef7a5fdb4e001894)): ?>
<?php $component = $__componentOriginal0c793dac3b0f3858ef7a5fdb4e001894; ?>
<?php unset($__componentOriginal0c793dac3b0f3858ef7a5fdb4e001894); ?>
<?php endif; ?>
<?php endif; ?>
<?php $__blaze->popData(); ?>
<?php unset($__resolved, $__delegatedData) ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\select\option\index.blade.php ENDPATH**/ ?>