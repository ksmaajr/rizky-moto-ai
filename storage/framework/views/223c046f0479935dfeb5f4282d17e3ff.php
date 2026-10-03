<?php # [BlazeFolded]:{flux::legend}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/legend.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::description}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/description.blade.php}:{1789427708} ?>
<?php
if (!function_exists('_223c046f0479935dfeb5f4282d17e3ff')):
function _223c046f0479935dfeb5f4282d17e3ff($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'legend' => null,
    'description' => null,
];
$legend ??= $attributes['legend'] ?? $__defaults['legend']; unset($attributes['legend']);
$description ??= $attributes['description'] ?? $__defaults['description']; unset($attributes['description']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('min-w-0') // This is here to allow nested select elements like flux::select listbox variant to truncate properly...
    ->add('[&[disabled]_[data-flux-label]]:opacity-50') // Dim labels when the fieldset is disabled...
    ->add('[&[disabled]_[data-flux-legend]]:opacity-50') // Dim legend when the fieldset is disabled...

    // Adjust spacing between fields...
    ->add('*:data-flux-field:mb-3')

    // Adjust spacing between fields...
    ->add('*:data-flux-field:mb-3')
    ->add('[&>[data-flux-field]:has(>[data-flux-description])]:mb-4')
    ->add('[&>[data-flux-field]:last-child]:mb-0!')

    // Adjust spacing below legend...
    ->add('[&>[data-flux-legend]]:mb-4')
    ->add('[&>[data-flux-legend]:has(+[data-flux-description])]:mb-2')

    // Adjust spacing below description...
    ->add('[&>[data-flux-legend]+[data-flux-description]]:mb-4')
    ;
?>

<fieldset <?php echo e($attributes->class($classes)); ?> data-flux-fieldset>
    <?php if ($legend): ?>
        <?php ob_start(); ?><ui-legend class="text-base font-medium text-zinc-800 dark:text-white" data-flux-legend>
    <?php ob_start(); ?><?php echo e($legend); ?><?php echo trim(ob_get_clean()); ?>

</ui-legend>
<?php echo ltrim(ob_get_clean()); ?>
    <?php endif; ?>

    <?php if ($description): ?>
        <?php ob_start(); ?><ui-description class="text-sm text-zinc-500 dark:text-white/60" data-flux-description>
    <?php ob_start(); ?><?php echo e($description); ?><?php echo trim(ob_get_clean()); ?>

</ui-description>
<?php echo ltrim(ob_get_clean()); ?>
    <?php endif; ?>

    <?php echo e($slot); ?>

</fieldset>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\fieldset.blade.php ENDPATH**/ ?>