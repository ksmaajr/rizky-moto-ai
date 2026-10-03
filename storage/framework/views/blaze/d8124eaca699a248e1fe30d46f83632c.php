<?php
if (!function_exists('__d8124eaca699a248e1fe30d46f83632c')):
function __d8124eaca699a248e1fe30d46f83632c($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $srOnly = $srOnly ??= $attributes->pluck('sr-only'); ?>

<?php
$__defaults = [
    'badge' => null,
    'aside' => null,
    'trailing' => null,
    'srOnly' => null,
];
$badge ??= $attributes['badge'] ?? $__defaults['badge']; unset($attributes['badge']);
$aside ??= $attributes['aside'] ?? $__defaults['aside']; unset($attributes['aside']);
$trailing ??= $attributes['trailing'] ?? $__defaults['trailing']; unset($attributes['trailing']);
$srOnly ??= $attributes['sr-only'] ?? $attributes['srOnly'] ?? $__defaults['srOnly']; unset($attributes['srOnly'], $attributes['sr-only']);
unset($__defaults);
?>

<?php
    $classes = Flux::classes()
        ->add('inline-flex items-center')
        ->add('text-sm font-medium')
        ->add($srOnly ? 'sr-only' : '')
        ->add('[:where(&)]:text-zinc-800 [:where(&)]:dark:text-white')
        ->add('[&:has([data-flux-label-trailing])]:flex')
        ;
?>

<ui-label <?php echo e($attributes->class($classes)); ?> data-flux-label>
    <?php echo e($slot); ?>


    <?php if (is_string($badge)): ?>
        <span class="ms-1.5 text-zinc-800/70 text-xs bg-zinc-800/5 px-1.5 py-1 -my-1 rounded-[4px] dark:bg-white/10 dark:text-zinc-300" aria-hidden="true">
            <?php echo e($badge); ?>

        </span>
    <?php elseif ($badge): ?>
        <span class="ms-1.5" aria-hidden="true">
            <?php echo e($badge); ?>

        </span>
    <?php endif; ?>

    <?php if ($aside): ?>
        <span class="ms-1.5 text-zinc-800/70 text-xs bg-zinc-800/5 px-1.5 py-1 -my-1 rounded-[4px] dark:bg-white/10 dark:text-zinc-300" aria-hidden="true">
            <?php echo e($aside); ?>

        </span>
    <?php endif; ?>

    <?php if ($trailing): ?>
        <div class="ml-auto" data-flux-label-trailing>
            <?php echo e($trailing); ?>

        </div>
    <?php endif; ?>
</ui-label>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/label.blade.php ENDPATH**/ ?>