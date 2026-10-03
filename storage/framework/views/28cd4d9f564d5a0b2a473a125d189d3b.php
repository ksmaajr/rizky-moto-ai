<?php
if (!function_exists('_28cd4d9f564d5a0b2a473a125d189d3b')):
function _28cd4d9f564d5a0b2a473a125d189d3b($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'accent' => true,
    'label' => null,
];
$accent ??= $attributes['accent'] ?? $__defaults['accent']; unset($attributes['accent']);
$label ??= $attributes['label'] ?? $__defaults['label']; unset($attributes['label']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('flex whitespace-nowrap gap-2 items-center py-1 px-2 rounded-full text-sm font-medium leading-4')
    ->add('bg-zinc-800/6 dark:bg-white/10 hover:bg-zinc-800/10 dark:hover:bg-white/15 text-zinc-800 hover:text-zinc-800 dark:text-white/70 dark:hover:text-white')
    ->add(match ($accent) {
        true => 'data-checked:bg-(--color-accent) hover:data-checked:bg-(--color-accent)',
        false => 'data-checked:bg-zinc-800 dark:data-checked:bg-white',
    })
    ->add(match ($accent) {
        true => 'data-checked:text-(--color-accent-foreground) hover:data-checked:text-(--color-accent-foreground)',
        false => 'data-checked:text-white dark:data-checked:text-zinc-800',
    })
    ->add('[&[disabled]]:opacity-50 dark:[&[disabled]]:opacity-75 [&[disabled]]:cursor-default [&[disabled]]:pointer-events-none')
    ;
?>




<ui-checkbox <?php echo e($attributes->class($classes)); ?> data-flux-control data-flux-checkbox-pills tabindex="-1" data-flux-field>
    <?php echo e($slot->isNotEmpty() ? $slot : $label); ?>

</ui-checkbox>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\checkbox\variants\pills.blade.php ENDPATH**/ ?>