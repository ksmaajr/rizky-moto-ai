<?php
if (!function_exists('_4946ba03c8d1e0583588bd39b3786615')):
function _4946ba03c8d1e0583588bd39b3786615($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$__awareDefaults = ['animate' => null];
$animate = $__blaze->getConsumableData('animate', $__awareDefaults['animate']);
unset($__awareDefaults);
?>

<?php
$__defaults = [
    'size' => 'base',
    'animate' => null,
];
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
$animate ??= $attributes['animate'] ?? $__defaults['animate']; unset($attributes['animate']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('[:where(&)]:w-full')
    ->add(match ($size) {
        'base' => '[:where(&)]:h-5 py-[3px]',
        'lg' => 'h-6 py-[2px]',
    })
    ->add(match ($animate) {
        'shimmer' => [
            'relative before:absolute before:inset-0 before:-translate-x-full',
            'overflow-hidden isolate',
            '[:where(&)]:[--flux-shimmer-color:white]',
            'dark:[:where(&)]:[--flux-shimmer-color:var(--color-zinc-900)]',
            'before:z-10 before:animate-[flux-shimmer_2s_infinite]',
            'before:bg-gradient-to-r before:from-transparent before:via-[var(--flux-shimmer-color)]/50 dark:before:via-[var(--flux-shimmer-color)]/50 before:to-transparent',
        ],
        'pulse' => 'animate-pulse',
        default => '',
    })
    ;
?>

<div <?php echo e($attributes->class($classes)); ?> data-flux-skeleton-line>
    <div class="h-full [:where(&)]:rounded [:where(&)]:bg-zinc-400/20"><?php echo e($slot); ?></div>
</div><?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\skeleton\line.blade.php ENDPATH**/ ?>