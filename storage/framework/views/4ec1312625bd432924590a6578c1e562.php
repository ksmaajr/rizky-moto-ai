<?php
if (!function_exists('_4ec1312625bd432924590a6578c1e562')):
function _4ec1312625bd432924590a6578c1e562($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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

<?php $__resolved = $__blaze->resolve('flux::' . 'radio.group.variants.' . $variant); ?>
<?php $__delegatedData = $__blaze->unescapeAttributes($attributes->getAttributes()); ?>
<?php $__blaze->pushData($__delegatedData); ?>
<?php if ($__resolved !== false): ?>
<?php if (isset($__slotsbc7e314f6de596e6a79abc61d634bf39)) { $__slotsStackbc7e314f6de596e6a79abc61d634bf39[] = $__slotsbc7e314f6de596e6a79abc61d634bf39; } ?>
<?php $__slotsbc7e314f6de596e6a79abc61d634bf39 = []; ?>
<?php ob_start(); ?><?php echo e($slot); ?><?php $__slotsbc7e314f6de596e6a79abc61d634bf39['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__slotsbc7e314f6de596e6a79abc61d634bf39 = array_merge($__blaze->mergedComponentSlots(), $__slotsbc7e314f6de596e6a79abc61d634bf39); ?>
<?php ('_' . $__resolved)($__blaze, $__delegatedData, $__slotsbc7e314f6de596e6a79abc61d634bf39, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackbc7e314f6de596e6a79abc61d634bf39)) { $__slotsbc7e314f6de596e6a79abc61d634bf39 = array_pop($__slotsStackbc7e314f6de596e6a79abc61d634bf39); } ?>
<?php else: ?>
<?php if (!Flux::componentExists($name = 'radio.group.variants.' . $variant)) throw new \Exception("Flux component [{$name}] does not exist."); ?><?php if (isset($component)) { $__componentOriginalf6b376e50e9192a3af54df033b02996a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf6b376e50e9192a3af54df033b02996a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve([
    'view' => (app()->version() >= 12 ? hash('xxh128', 'flux') : md5('flux')) . '::' . 'radio.group.variants.' . $variant,
    'data' => $__env->getCurrentComponentData(),
] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::' . 'radio.group.variants.' . $variant); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes($attributes->getAttributes()); ?><?php echo e($slot); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf6b376e50e9192a3af54df033b02996a)): ?>
<?php $attributes = $__attributesOriginalf6b376e50e9192a3af54df033b02996a; ?>
<?php unset($__attributesOriginalf6b376e50e9192a3af54df033b02996a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf6b376e50e9192a3af54df033b02996a)): ?>
<?php $component = $__componentOriginalf6b376e50e9192a3af54df033b02996a; ?>
<?php unset($__componentOriginalf6b376e50e9192a3af54df033b02996a); ?>
<?php endif; ?>
<?php endif; ?>
<?php $__blaze->popData(); ?>
<?php unset($__resolved, $__delegatedData) ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\radio\group\index.blade.php ENDPATH**/ ?>