<?php
if (!function_exists('__cc20e98e931a19bf1e5df07a5aaf8e49')):
function __cc20e98e931a19bf1e5df07a5aaf8e49($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $iconTrailing ??= $attributes->pluck('icon:trailing'); ?>
<?php $iconVariant ??= $attributes->pluck('icon:variant'); ?>

<?php
$__awareDefaults = [ 'size' ];
$size = $__blaze->getConsumableData('size');
unset($__awareDefaults);
?>

<?php
$__defaults = [
    'iconTrailing' => null,
    'iconVariant' => null,
    'label' => null,
    'icon' => null,
    'size' => null,
];
$iconTrailing ??= $attributes['icon-trailing'] ?? $attributes['iconTrailing'] ?? $__defaults['iconTrailing']; unset($attributes['iconTrailing'], $attributes['icon-trailing']);
$iconVariant ??= $attributes['icon-variant'] ?? $attributes['iconVariant'] ?? $__defaults['iconVariant']; unset($attributes['iconVariant'], $attributes['icon-variant']);
$label ??= $attributes['label'] ?? $__defaults['label']; unset($attributes['label']);
$icon ??= $attributes['icon'] ?? $__defaults['icon']; unset($attributes['icon']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('flex whitespace-nowrap flex-1 justify-center items-center gap-2')
    ->add('rounded-md data-checked:shadow-xs')
    ->add('text-sm font-medium text-zinc-600 hover:text-zinc-800 dark:hover:text-white dark:text-white/70 data-checked:text-zinc-800 dark:data-checked:text-white')
    ->add('data-checked:bg-white dark:data-checked:bg-white/20')
    ->add('[&[disabled]]:opacity-50 dark:[&[disabled]]:opacity-75 [&[disabled]]:cursor-default [&[disabled]]:pointer-events-none')
    ->add(match ($size) {
        'sm' => 'px-3 text-sm',
        default => 'px-4',
    })
    ;

$iconVariant ??= 'mini';

$iconClasses = Flux::classes('text-zinc-500 dark:text-zinc-400 [ui-radio[data-checked]_&]:text-zinc-800 dark:[ui-radio[data-checked]_&]:text-white')
    // When using the outline icon variant, we need to size it down to match the default icon sizes...
    ->add($iconVariant === 'outline' ? 'size-5' : '')
    ;

?>



<ui-radio <?php echo e($attributes->class($classes)); ?> data-flux-control data-flux-radio-segmented tabindex="-1">
    <?php if (is_string($icon) && $icon !== ''): ?>
        <?php if (!function_exists('__5a41e952545281559923d327e8a7de08')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'); require $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'; } ?>
<?php $__blaze->pushData(['icon' => $icon,'variant' => $iconVariant,'class' => $iconClasses]); ?>
<?php __5a41e952545281559923d327e8a7de08($__blaze, ['icon' => $icon,'variant' => $iconVariant,'class' => $iconClasses], [], ['icon', 'variant'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
    <?php elseif ($icon): ?>
        <?php echo e($icon); ?>

    <?php endif; ?>

    <?php echo e($slot->isNotEmpty() ? $slot : $label); ?>


    <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
        <?php if (!function_exists('__5a41e952545281559923d327e8a7de08')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'); require $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'; } ?>
<?php $__blaze->pushData(['icon' => $iconTrailing,'variant' => 'micro']); ?>
<?php __5a41e952545281559923d327e8a7de08($__blaze, ['icon' => $iconTrailing,'variant' => 'micro'], [], ['icon'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
    <?php elseif ($iconTrailing): ?>
        <?php echo e($iconTrailing); ?>

    <?php endif; ?>
</ui-radio>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/radio/variants/segmented.blade.php ENDPATH**/ ?>