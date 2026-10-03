<?php
if (!function_exists('_65d0c2f17589af3960467ed271b155be')):
function _65d0c2f17589af3960467ed271b155be($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;

if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php
$classes = Flux::classes()
    ->add('shrink-0 size-[1.125rem] rounded-full')
    ->add('text-sm text-zinc-700 dark:text-zinc-800')
    ->add('shadow-xs [ui-radio[disabled]_&]:opacity-75 [ui-radio[data-checked][disabled]_&]:opacity-50 [ui-radio[disabled]_&]:shadow-none [ui-radio[data-checked]_&]:shadow-none')
    ->add('flex justify-center items-center [ui-radio[data-checked]_&>div]:block')
    ->add([
        'border',
        'border-zinc-300 dark:border-white/10',
        '[ui-radio[disabled]_&]:border-zinc-200 dark:[ui-radio[disabled]_&]:border-white/5',
        '[ui-radio[data-checked]_&]:border-transparent data-indeterminate:border-transparent',
        '[ui-radio[data-checked]_&]:[ui-radio[disabled]_&]:border-transparent data-indeterminate:border-transparent',
        '[print-color-adjust:exact]',
    ])
    ->add([
        'bg-white dark:bg-white/10',
        '[ui-radio[data-checked]_&]:bg-[var(--color-accent)]',
        'hover:[ui-radio[data-checked]_&]:bg-(--color-accent)',
        'focus:[ui-radio[data-checked]_&]:bg-(--color-accent)',
    ])
    ;
?>

<div <?php echo e($attributes->class($classes)); ?> data-flux-radio-indicator>
    <div class="hidden size-2 rounded-full bg-[var(--color-accent-foreground)]"></div>
</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\radio\indicator.blade.php ENDPATH**/ ?>