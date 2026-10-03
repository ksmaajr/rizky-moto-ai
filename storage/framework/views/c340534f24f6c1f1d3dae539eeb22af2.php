<?php
if (!function_exists('_c340534f24f6c1f1d3dae539eeb22af2')):
function _c340534f24f6c1f1d3dae539eeb22af2($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $logoDark ??= $attributes->pluck('logo:dark'); ?>

<?php
$__defaults = [
    'name' => null,
    'logo' => null,
    'logoDark' => null,
    'alt' => null,
    'href' => '/',
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$logo ??= $attributes['logo'] ?? $__defaults['logo']; unset($attributes['logo']);
$logoDark ??= $attributes['logo-dark'] ?? $attributes['logoDark'] ?? $__defaults['logoDark']; unset($attributes['logoDark'], $attributes['logo-dark']);
$alt ??= $attributes['alt'] ?? $__defaults['alt']; unset($attributes['alt']);
$href ??= $attributes['href'] ?? $__defaults['href']; unset($attributes['href']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('h-10 flex items-center me-4')
    ;

$textClasses = Flux::classes()
    ->add('text-sm font-medium truncate [:where(&)]:text-zinc-800 dark:[:where(&)]:text-zinc-100')
    ;
?>

<?php if ($name): ?>
    <a href="<?php echo e($href); ?>" <?php echo e($attributes->class([ $classes, 'gap-2' ])); ?> data-flux-brand>
        <?php if ($logo instanceof \Illuminate\View\ComponentSlot): ?>
            <div <?php echo e($logo->attributes->class('flex items-center justify-center [:where(&)]:h-6 [:where(&)]:min-w-6 [:where(&)]:rounded-sm overflow-hidden shrink-0')); ?>>
                <?php echo e($logo); ?>

            </div>
        <?php else: ?>
            <div class="flex items-center justify-center h-6 rounded-sm overflow-hidden shrink-0">
                <?php if ($logoDark): ?>
                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($alt); ?>" class="h-6 dark:hidden" />
                    <img src="<?php echo e($logoDark); ?>" alt="<?php echo e($alt); ?>" class="h-6 hidden dark:block" />
                <?php elseif ($logo): ?>
                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($alt); ?>" class="h-6" />
                <?php else: ?>
                    <?php echo e($slot); ?>

                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="<?php echo e($textClasses); ?>"><?php echo e($name); ?></div>
    </a>
<?php else: ?>
    <a href="<?php echo e($href); ?>" <?php echo e($attributes->class($classes)); ?> data-flux-brand>
        <?php if ($logo instanceof \Illuminate\View\ComponentSlot): ?>
            <div <?php echo e($logo->attributes->class('flex items-center justify-center [:where(&)]:h-6 [:where(&)]:min-w-6 [:where(&)]:rounded-sm overflow-hidden shrink-0')); ?>>
                <?php echo e($logo); ?>

            </div>
        <?php else: ?>
            <div class="flex items-center justify-center h-6 rounded-sm overflow-hidden shrink-0">
                <?php if ($logoDark): ?>
                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($alt); ?>" class="h-6 dark:hidden" />
                    <img src="<?php echo e($logoDark); ?>" alt="<?php echo e($alt); ?>" class="h-6 hidden dark:block" />
                <?php elseif ($logo): ?>
                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($alt); ?>" class="h-6" />
                <?php else: ?>
                    <?php echo e($slot); ?>

                <?php endif; ?>
            </div>
        <?php endif; ?>
    </a>
<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\brand.blade.php ENDPATH**/ ?>