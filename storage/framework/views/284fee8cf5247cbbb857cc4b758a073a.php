<?php # [BlazeFolded]:{flux::icon.chevron-down}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-down.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::icon.chevron-right}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-right.blade.php}:{1789427708} ?>
<?php
if (!function_exists('_284fee8cf5247cbbb857cc4b758a073a')):
function _284fee8cf5247cbbb857cc4b758a073a($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'expandable' => false,
    'expanded' => true,
    'heading' => null,
];
$expandable ??= $attributes['expandable'] ?? $__defaults['expandable']; unset($attributes['expandable']);
$expanded ??= $attributes['expanded'] ?? $__defaults['expanded']; unset($attributes['expanded']);
$heading ??= $attributes['heading'] ?? $__defaults['heading']; unset($attributes['heading']);
unset($__defaults);
?>

<?php if ($expandable && $heading): ?>
    <ui-disclosure <?php echo e($attributes->class('group/disclosure')); ?> <?php if($expanded === true): ?> open <?php endif; ?> data-flux-navlist-group>
        <button type="button" class="w-full h-10 lg:h-8 flex items-center group/disclosure-button mb-[2px] rounded-lg hover:bg-zinc-800/5 dark:hover:bg-white/[7%] text-zinc-500 hover:text-zinc-800 dark:text-white/80 dark:hover:text-white">
            <div class="ps-3 pe-4">
                <?php ob_start(); ?><svg class="shrink-0 [:where(&amp;)]:size-6 size-3! hidden group-data-open/disclosure-button:block" data-flux-icon xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
</svg>

        <?php echo ltrim(ob_get_clean()); ?>
                <?php ob_start(); ?><svg class="shrink-0 [:where(&amp;)]:size-6 size-3! block group-data-open/disclosure-button:hidden rtl:rotate-180" data-flux-icon xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
</svg>

        <?php echo ltrim(ob_get_clean()); ?>
            </div>

            <span class="text-sm font-medium leading-none"><?php echo e($heading); ?></span>
        </button>

        <div class="relative hidden data-open:block space-y-[2px] ps-7" <?php if($expanded === true): ?> data-open <?php endif; ?>>
            <div class="absolute inset-y-[3px] w-px bg-zinc-200 dark:bg-white/30 start-0 ms-4"></div>

            <?php echo e($slot); ?>

        </div>
    </ui-disclosure>
<?php elseif ($heading): ?>
    <div <?php echo e($attributes->class('block space-y-[2px]')); ?>>
        <div class="px-3 py-2">
            <div class="text-sm text-zinc-400 font-medium leading-none"><?php echo e($heading); ?></div>
        </div>

        <div>
            <?php echo e($slot); ?>

        </div>
    </div>
<?php else: ?>
    <div <?php echo e($attributes->class('block space-y-[2px]')); ?>>
        <?php echo e($slot); ?>

    </div>
<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\navlist\group.blade.php ENDPATH**/ ?>