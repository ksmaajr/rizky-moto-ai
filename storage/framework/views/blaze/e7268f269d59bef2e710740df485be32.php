<?php
if (!function_exists('__e7268f269d59bef2e710740df485be32')):
function __e7268f269d59bef2e710740df485be32($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'size' => 'base',
    'accent' => false,
    'level' => null,
];
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
$accent ??= $attributes['accent'] ?? $__defaults['accent']; unset($attributes['accent']);
$level ??= $attributes['level'] ?? $__defaults['level']; unset($attributes['level']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('font-medium')
    ->add(match ($accent) {
        true => 'text-[var(--color-accent-content)]',
        default => '[:where(&)]:text-zinc-800 [:where(&)]:dark:text-white',
    })
    ->add(match ($size) {
        '2xl' => 'text-4xl [&:has(+[data-flux-subheading])]:mb-2 [[data-flux-subheading]+&]:mt-2',
        'xl' => 'text-2xl [&:has(+[data-flux-subheading])]:mb-2 [[data-flux-subheading]+&]:mt-2',
        'lg' => 'text-base [&:has(+[data-flux-subheading])]:mb-2 [[data-flux-subheading]+&]:mt-2',
        default => 'text-sm [&:has(+[data-flux-subheading])]:mb-2 [[data-flux-subheading]+&]:mt-2',
    })
    ;
?>

<?php switch ((int) $level): case(1): ?>
        <h1 <?php echo e($attributes->class($classes)); ?> data-flux-heading><?php echo e($slot); ?></h1>

        <?php break; ?>
    <?php case(2): ?>
        <h2 <?php echo e($attributes->class($classes)); ?> data-flux-heading><?php echo e($slot); ?></h2>

        <?php break; ?>
    <?php case(3): ?>
        <h3 <?php echo e($attributes->class($classes)); ?> data-flux-heading><?php echo e($slot); ?></h3>

        <?php break; ?>
    <?php case(4): ?>
        <h4 <?php echo e($attributes->class($classes)); ?> data-flux-heading><?php echo e($slot); ?></h4>

        <?php break; ?>
    <?php default: ?>
        <div <?php echo e($attributes->class($classes)); ?> data-flux-heading><?php echo e($slot); ?></div>
<?php endswitch; ?>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/heading.blade.php ENDPATH**/ ?>