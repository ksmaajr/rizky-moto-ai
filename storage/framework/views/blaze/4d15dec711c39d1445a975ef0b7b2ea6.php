<?php
if (!function_exists('__4d15dec711c39d1445a975ef0b7b2ea6')):
function __4d15dec711c39d1445a975ef0b7b2ea6($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;

if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php
$__defaults = [
    'orientation' => null,
    'vertical' => false,
    'variant' => null,
    'faint' => false,
    'text' => null,
];
$orientation ??= $attributes['orientation'] ?? $__defaults['orientation']; unset($attributes['orientation']);
$vertical ??= $attributes['vertical'] ?? $__defaults['vertical']; unset($attributes['vertical']);
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
$faint ??= $attributes['faint'] ?? $__defaults['faint']; unset($attributes['faint']);
$text ??= $attributes['text'] ?? $__defaults['text']; unset($attributes['text']);
unset($__defaults);
?>

<?php
$orientation ??= $vertical ? 'vertical' : 'horizontal';

$classes = Flux::classes('border-0 [print-color-adjust:exact]')
    ->add(match ($variant) {
        'subtle' => 'bg-zinc-800/5 dark:bg-white/10',
        default => 'bg-zinc-800/15 dark:bg-white/20',
    })
    ->add(match ($orientation) {
        'horizontal' => 'h-px w-full',
        'vertical' => 'self-stretch self-center w-px',
    })
    ;
?>

<?php if ($text): ?>
    <div data-orientation="<?php echo e($orientation); ?>" role="none" <?php echo e($attributes->class('flex items-center w-full')); ?> data-flux-separator>
        <div class="<?php echo e($classes); ?> grow"></div>

        <span class="shrink mx-6 font-medium text-sm text-zinc-500 dark:text-zinc-300 whitespace-nowrap"><?php echo e($text); ?></span>

        <div class="<?php echo e($classes); ?> grow"></div>
    </div>
<?php else: ?>
    <div data-orientation="<?php echo e($orientation); ?>" role="none" <?php echo e($attributes->class($classes, 'shrink-0')); ?> data-flux-separator></div>
<?php endif; ?>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/separator.blade.php ENDPATH**/ ?>