<?php
if (!function_exists('__8c05ebbe26191f2ba5fec026e05ca535')):
function __8c05ebbe26191f2ba5fec026e05ca535($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'shortcut' => null,
    'name' => null,
];
$shortcut ??= $attributes['shortcut'] ?? $__defaults['shortcut']; unset($attributes['shortcut']);
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
unset($__defaults);
?>

<div
    <?php echo e($attributes->class('contents')); ?>

    x-data
    x-on:click="$el.querySelector('button[disabled]') || $dispatch('modal-show', { name: '<?php echo e($name); ?>' })"
    <?php if($shortcut): ?>
        x-on:keydown.<?php echo e($shortcut); ?>.document="$event.preventDefault(); $dispatch('modal-show', { name: '<?php echo e($name); ?>' })"
    <?php endif; ?>
    data-flux-modal-trigger
>
    <?php echo e($slot); ?>

</div>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/modal/trigger.blade.php ENDPATH**/ ?>