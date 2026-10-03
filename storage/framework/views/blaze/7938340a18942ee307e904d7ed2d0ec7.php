<?php
if (!function_exists('__7938340a18942ee307e904d7ed2d0ec7')):
function __7938340a18942ee307e904d7ed2d0ec7($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'variant' => 'block',
];
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('min-w-0') // This is here to allow nested input elements like flux::input.file to truncate properly...
    ->add('[&:not(:has([data-flux-field])):has([data-flux-control][disabled])>[data-flux-label]]:opacity-50') // Dim labels for fields with no nested fields when a control is disabled...
    ->add('[&:has(>[data-flux-radio-group][disabled])>[data-flux-label]]:opacity-50') // Special case for radio groups because they are nested fields...
    ->add('[&:has(>[data-flux-checkbox-group][disabled])>[data-flux-label]]:opacity-50') // Special case for checkbox groups because they are nested fields...
    ->add(match ($variant) {
        default => 'block',
        'bare' => '[:where(&)]:block',
        'inline' => [
            'grid gap-x-3 gap-y-1.5',
            'has-[[data-flux-label]~[data-flux-control]]:grid-cols-[1fr_auto]',
            'has-[[data-flux-control]~[data-flux-label]]:grid-cols-[auto_1fr]',
            '[&>[data-flux-control]~[data-flux-description]]:row-start-2 [&>[data-flux-control]~[data-flux-description]]:col-start-2',
            '[&>[data-flux-control]~[data-flux-error]]:col-span-2 [&>[data-flux-control]~[data-flux-error]]:mt-1', // Position error messages...
            '[&>[data-flux-label]~[data-flux-control]]:row-start-1 [&>[data-flux-label]~[data-flux-control]]:col-start-2',
        ],
    })
    ->add(match ($variant) {
        default => [ // Adjust spacing around label...
            '*:data-flux-label:mb-3 [&>[data-flux-label]:has(+[data-flux-description])]:mb-2',
        ],
        'bare' => '',
        'inline' => '',
    })
    ->add(match ($variant) {
        default => [ // Adjust spacing around description...
            '[&>[data-flux-label]+[data-flux-description]]:mt-0',
            '[&>[data-flux-label]+[data-flux-description]]:mb-3',
            '[&>[data-flux-description]:first-child:has(+*)]:mb-3',
            '[&>*:not([data-flux-label])+[data-flux-description]]:mt-3',
        ],
        'bare' => '',
        'inline' => '',
    });
?>

<ui-field <?php echo e($attributes->class($classes)); ?> data-flux-field>
    <?php echo e($slot); ?>

</ui-field>
<?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/field.blade.php ENDPATH**/ ?>