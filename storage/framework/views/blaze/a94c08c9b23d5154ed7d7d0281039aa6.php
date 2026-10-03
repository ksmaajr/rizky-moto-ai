<?php
if (!function_exists('__a94c08c9b23d5154ed7d7d0281039aa6')):
function __a94c08c9b23d5154ed7d7d0281039aa6($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'inline' => false,
    'variant' => null,
    'color' => null,
    'size' => null,
];
$inline ??= $attributes['inline'] ?? $__defaults['inline']; unset($attributes['inline']);
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
$color ??= $attributes['color'] ?? $__defaults['color']; unset($attributes['color']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('[:where(&)]:font-normal')
    ->add(match ($size) {
        'xl' => 'text-lg',
        'lg' => 'text-base',
        default => '[:where(&)]:text-sm',
        'sm' => 'text-xs',
    })
    ->add($color ? match($color) {
        'red' => 'text-red-600 dark:text-red-400',
        'orange' => 'text-orange-600 dark:text-orange-400',
        'amber' => 'text-amber-600 dark:text-amber-500',
        'yellow' => 'text-yellow-600 dark:text-yellow-500',
        'lime' => 'text-lime-600 dark:text-lime-500',
        'green' => 'text-green-600 dark:text-green-500',
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'teal' => 'text-teal-600 dark:text-teal-400',
        'cyan' => 'text-cyan-600 dark:text-cyan-400',
        'sky' => 'text-sky-600 dark:text-sky-400',
        'blue' => 'text-blue-600 dark:text-blue-400',
        'indigo' => 'text-indigo-600 dark:text-indigo-400',
        'violet' => 'text-violet-600 dark:text-violet-400',
        'purple' => 'text-purple-600 dark:text-purple-400',
        'fuchsia' => 'text-fuchsia-600 dark:text-fuchsia-400',
        'pink' => 'text-pink-600 dark:text-pink-400',
        'rose' => 'text-rose-600 dark:text-rose-400',
    } : match ($variant) {
        'strong' => '[:where(&)]:text-zinc-800 [:where(&)]:dark:text-white',
        'subtle' => '[:where(&)]:text-zinc-400 [:where(&)]:dark:text-white/50',
        default => '[:where(&)]:text-zinc-500 [:where(&)]:dark:text-white/70',
    })
    ;
?>

<?php if ($inline) : ?><span <?php echo e($attributes->class($classes)); ?> data-flux-text <?php if($color): ?> color="<?php echo e($color); ?>" <?php endif; ?>><?php echo e($slot); ?></span><?php else: ?><p <?php echo e($attributes->class($classes)); ?> data-flux-text <?php if($color): ?> data-color="<?php echo e($color); ?>" <?php endif; ?>><?php echo e($slot); ?></p><?php endif; ?><?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/text.blade.php ENDPATH**/ ?>