<?php # [BlazeFolded]:{flux::label}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/label.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::description}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/description.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::field}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/field.blade.php}:{1789427708} ?>
<?php
if (!function_exists('_746d53b0b9a90ef92281dea647bc09bc')):
function _746d53b0b9a90ef92281dea647bc09bc($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
extract(Flux::forwardedattributes($attributes, [
    'name',
    'description',
    'label',
]));
?>

<?php
$__defaults = [
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'description' => null,
    'label' => null,
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$description ??= $attributes['description'] ?? $__defaults['description']; unset($attributes['description']);
$label ??= $attributes['label'] ?? $__defaults['label']; unset($attributes['label']);
unset($__defaults);
?>

<?php if ($label || $description): ?>
    <?php ob_start(); ?><ui-field class="min-w-0 [&amp;:not(:has([data-flux-field])):has([data-flux-control][disabled])&gt;[data-flux-label]]:opacity-50 [&amp;:has(&gt;[data-flux-radio-group][disabled])&gt;[data-flux-label]]:opacity-50 [&amp;:has(&gt;[data-flux-checkbox-group][disabled])&gt;[data-flux-label]]:opacity-50 grid gap-x-3 gap-y-1.5 has-[[data-flux-label]~[data-flux-control]]:grid-cols-[1fr_auto] has-[[data-flux-control]~[data-flux-label]]:grid-cols-[auto_1fr] [&amp;&gt;[data-flux-control]~[data-flux-description]]:row-start-2 [&amp;&gt;[data-flux-control]~[data-flux-description]]:col-start-2 [&amp;&gt;[data-flux-control]~[data-flux-error]]:col-span-2 [&amp;&gt;[data-flux-control]~[data-flux-error]]:mt-1 [&amp;&gt;[data-flux-label]~[data-flux-control]]:row-start-1 [&amp;&gt;[data-flux-label]~[data-flux-control]]:col-start-2" data-flux-field>
    <?php ob_start(); ?>
        <?php if ($label): ?>
            <?php ob_start(); ?><ui-label class="inline-flex items-center text-sm font-medium  [:where(&amp;)]:text-zinc-800 [:where(&amp;)]:dark:text-white [&amp;:has([data-flux-label-trailing])]:flex" data-flux-label>
    <?php ob_start(); ?><?php echo e($label); ?><?php echo trim(ob_get_clean()); ?>


    
    
    </ui-label>
<?php echo ltrim(ob_get_clean()); ?>
        <?php endif; ?>

        <?php if ($description): ?>
            <?php ob_start(); ?><ui-description class="text-sm text-zinc-500 dark:text-white/60" data-flux-description>
    <?php ob_start(); ?><?php echo e($description); ?><?php echo trim(ob_get_clean()); ?>

</ui-description>
<?php echo ltrim(ob_get_clean()); ?>
        <?php endif; ?>

        <?php echo e($slot); ?>


        <?php $__getScope = fn($scope = []) => $scope; ?><?php if (isset($scope)) $__scope = $scope; ?><?php $scope = $__getScope(scope: ['name' => $name]); ?>
            <?php if (!function_exists('_7888599f915327ed3f218c5e1d5ace9a')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/error.blade.php', $__blaze->compiledPath.'/7888599f915327ed3f218c5e1d5ace9a.php'); require $__blaze->compiledPath.'/7888599f915327ed3f218c5e1d5ace9a.php'; } ?>
<?php $__blaze->pushData(['name' => $scope['name']]); ?>
<?php _7888599f915327ed3f218c5e1d5ace9a($__blaze, ['name' => $scope['name']], [], ['name'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
        <?php if (isset($__scope)) { $scope = $__scope; unset($__scope); } ?>
    <?php echo trim(ob_get_clean()); ?>

</ui-field>
<?php echo ltrim(ob_get_clean()); ?>
<?php else: ?>
    <?php echo e($slot); ?>

<?php endif; ?>

<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\with-reversed-inline-field.blade.php ENDPATH**/ ?>