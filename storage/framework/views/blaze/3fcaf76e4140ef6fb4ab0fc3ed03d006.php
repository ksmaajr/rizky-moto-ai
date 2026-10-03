<?php
if (!function_exists('__3fcaf76e4140ef6fb4ab0fc3ed03d006')):
function __3fcaf76e4140ef6fb4ab0fc3ed03d006($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'as' => null,
    'external' => null,
    'accent' => true,
    'variant' => null,
    'strong' => false,
];
$as ??= $attributes['as'] ?? $__defaults['as']; unset($attributes['as']);
$external ??= $attributes['external'] ?? $__defaults['external']; unset($attributes['external']);
$accent ??= $attributes['accent'] ?? $__defaults['accent']; unset($attributes['accent']);
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
$strong ??= $attributes['strong'] ?? $__defaults['strong']; unset($attributes['strong']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('inline font-medium')
    ->add('underline-offset-[6px] hover:decoration-current')
    ->add(match ($variant) {
        'ghost' => 'no-underline hover:underline',
        'subtle' => 'no-underline',
        default => 'underline',
    })
    ->add('[[data-color]>&]:text-inherit [[data-color]>&]:decoration-current/20 dark:[[data-color]>&]:decoration-current/50 [[data-color]>&]:hover:decoration-current')
    ->add(match ($variant) {
        'subtle' => 'text-zinc-500 dark:text-white/70 hover:text-zinc-800 dark:hover:text-white',
        default => match ($accent) {
            true => 'text-[var(--color-accent-content)] decoration-[color-mix(in_oklab,var(--color-accent-content),transparent_80%)]',
            false => 'text-zinc-800 dark:text-white decoration-zinc-800/20 dark:decoration-white/20',
        },
    })
    ;
?>

<?php if ($as !== 'button') : ?><a <?php echo e($attributes->class($classes)); ?> data-flux-link <?php if ($external) : ?>target="_blank"<?php endif; ?>><?php echo e($slot); ?></a><?php else : ?><button <?php echo e($attributes->merge(['class' => $classes, 'type' => 'button'])); ?> data-flux-link><?php echo e($slot); ?></button><?php endif; ?><?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/link.blade.php ENDPATH**/ ?>