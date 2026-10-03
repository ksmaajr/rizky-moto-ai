<?php
if (!function_exists('_1f775c4e84f64f93420d79acfec1e294')):
function _1f775c4e84f64f93420d79acfec1e294($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$variant = $variant !== 'default' && Flux::componentExists('select.variants.' . $variant)
    ? 'custom'
    : 'default';
?>

<?php $__resolved = $__blaze->resolve('flux::' . 'select.group.variants.' . $variant); ?>
<?php $__delegatedData = $__blaze->unescapeAttributes($attributes->getAttributes()); ?>
<?php $__blaze->pushData($__delegatedData); ?>
<?php if ($__resolved !== false): ?>
<?php if (isset($__slots7eedd4066000a83756b67412e15f43f0)) { $__slotsStack7eedd4066000a83756b67412e15f43f0[] = $__slots7eedd4066000a83756b67412e15f43f0; } ?>
<?php $__slots7eedd4066000a83756b67412e15f43f0 = []; ?>
<?php ob_start(); ?><?php echo e($slot); ?><?php $__slots7eedd4066000a83756b67412e15f43f0['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__slots7eedd4066000a83756b67412e15f43f0 = array_merge($__blaze->mergedComponentSlots(), $__slots7eedd4066000a83756b67412e15f43f0); ?>
<?php ('_' . $__resolved)($__blaze, $__delegatedData, $__slots7eedd4066000a83756b67412e15f43f0, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack7eedd4066000a83756b67412e15f43f0)) { $__slots7eedd4066000a83756b67412e15f43f0 = array_pop($__slotsStack7eedd4066000a83756b67412e15f43f0); } ?>
<?php else: ?>
<?php if (!Flux::componentExists($name = 'select.group.variants.' . $variant)) throw new \Exception("Flux component [{$name}] does not exist."); ?><?php if (isset($component)) { $__componentOriginal57fefc6d3df0e1ba12c5f6e15dec7a96 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal57fefc6d3df0e1ba12c5f6e15dec7a96 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve([
    'view' => (app()->version() >= 12 ? hash('xxh128', 'flux') : md5('flux')) . '::' . 'select.group.variants.' . $variant,
    'data' => $__env->getCurrentComponentData(),
] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::' . 'select.group.variants.' . $variant); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes($attributes->getAttributes()); ?><?php echo e($slot); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal57fefc6d3df0e1ba12c5f6e15dec7a96)): ?>
<?php $attributes = $__attributesOriginal57fefc6d3df0e1ba12c5f6e15dec7a96; ?>
<?php unset($__attributesOriginal57fefc6d3df0e1ba12c5f6e15dec7a96); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal57fefc6d3df0e1ba12c5f6e15dec7a96)): ?>
<?php $component = $__componentOriginal57fefc6d3df0e1ba12c5f6e15dec7a96; ?>
<?php unset($__componentOriginal57fefc6d3df0e1ba12c5f6e15dec7a96); ?>
<?php endif; ?>
<?php endif; ?>
<?php $__blaze->popData(); ?>
<?php unset($__resolved, $__delegatedData) ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\select\group\index.blade.php ENDPATH**/ ?>