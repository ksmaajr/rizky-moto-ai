<?php # [BlazeFolded]:{flux::icon}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php}:{1789427708} ?>
<?php
if (!function_exists('_839e3d88b09291beb199cea61e19dfb9')):
function _839e3d88b09291beb199cea61e19dfb9($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;

if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $tooltipPosition = $tooltipPosition ??= $attributes->pluck('tooltip:position'); ?>
<?php $tooltipKbd = $tooltipKbd ??= $attributes->pluck('tooltip:kbd'); ?>
<?php $tooltip = $tooltip ??= $attributes->pluck('tooltip'); ?>

<?php
$__defaults = [
    'tooltipPosition' => 'right',
    'placeholder' => __('Search...'),
    'tooltipKbd' => null,
    'tooltip' => null,
    'kbd' => null,
];
$tooltipPosition ??= $attributes['tooltip-position'] ?? $attributes['tooltipPosition'] ?? $__defaults['tooltipPosition']; unset($attributes['tooltipPosition'], $attributes['tooltip-position']);
$placeholder ??= $attributes['placeholder'] ?? $__defaults['placeholder']; unset($attributes['placeholder']);
$tooltipKbd ??= $attributes['tooltip-kbd'] ?? $attributes['tooltipKbd'] ?? $__defaults['tooltipKbd']; unset($attributes['tooltipKbd'], $attributes['tooltip-kbd']);
$tooltip ??= $attributes['tooltip'] ?? $__defaults['tooltip']; unset($attributes['tooltip']);
$kbd ??= $attributes['kbd'] ?? $__defaults['kbd']; unset($attributes['kbd']);
unset($__defaults);
?>

<?php
$tooltip = $tooltip ?? $placeholder;

$tooltipKbd ??= $kbd;

$tooltipClasses = Flux::classes()
    ->add('w-full')
    ->add('in-data-flux-sidebar-header:in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:hidden')
    ;

$classes = Flux::classes()
    ->add('h-10 py-2 px-3 w-full rounded-lg disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm leading-[1.375rem] bg-zinc-800/5 dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 placeholder-zinc-500 disabled:placeholder-zinc-400 dark:text-zinc-200 dark:placeholder-white/60 dark:disabled:placeholder-white/40 border-0 relative flex items-center gap-3')
    ->add('in-data-flux-sidebar-on-mobile:h-10 in-data-flux-sidebar-collapsed-desktop:px-3')
    ->add('in-data-flux-sidebar-header:in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:hidden')
    ;
?>

<?php if (!function_exists('_dfd2184989fa5e11a0c987d1c0b86139')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/index.blade.php', $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'); require $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'; } ?>
<?php if (isset($__slotsdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__slotsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php if (isset($__attrsdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__attrsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php $__attrsdfd2184989fa5e11a0c987d1c0b86139 = ['position' => $tooltipPosition,'class' => $tooltipClasses]; ?>
<?php $__slotsdfd2184989fa5e11a0c987d1c0b86139 = []; ?>
<?php $__blaze->pushData($__attrsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php ob_start(); ?>
    <button
        <?php echo e($attributes->class($classes)); ?>

        type="button"
        data-flux-sidebar-search
    >
        <div class="flex items-center justify-center text-xs text-zinc-400/75 start-0">
            <?php ob_start(); ?><svg class="shrink-0 [:where(&amp;)]:size-6 size-4" data-flux-icon xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
</svg>

        <?php echo ltrim(ob_get_clean()); ?>
        </div>

        <div class="in-data-flux-sidebar-collapsed-desktop:hidden block self-center text-start flex-1 font-medium text-zinc-400 dark:text-white/40">
            <?php echo e($placeholder); ?>

        </div>

        <?php if ($kbd): ?>
            <div class="in-data-flux-sidebar-collapsed-desktop:hidden absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 pe-4 end-0">
                <?php echo e($kbd); ?>

            </div>
        <?php endif; ?>
    </button>

    <?php if (!function_exists('_3f2666fb7f87cdbef97ed140f283fa79')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/content.blade.php', $__blaze->compiledPath.'/3f2666fb7f87cdbef97ed140f283fa79.php'); require $__blaze->compiledPath.'/3f2666fb7f87cdbef97ed140f283fa79.php'; } ?>
<?php if (isset($__slots3f2666fb7f87cdbef97ed140f283fa79)) { $__slotsStack3f2666fb7f87cdbef97ed140f283fa79[] = $__slots3f2666fb7f87cdbef97ed140f283fa79; } ?>
<?php if (isset($__attrs3f2666fb7f87cdbef97ed140f283fa79)) { $__attrsStack3f2666fb7f87cdbef97ed140f283fa79[] = $__attrs3f2666fb7f87cdbef97ed140f283fa79; } ?>
<?php $__attrs3f2666fb7f87cdbef97ed140f283fa79 = ['kbd' => $tooltipKbd,'class' => 'not-in-data-flux-sidebar-collapsed-desktop:hidden cursor-default']; ?>
<?php $__slots3f2666fb7f87cdbef97ed140f283fa79 = []; ?>
<?php $__blaze->pushData($__attrs3f2666fb7f87cdbef97ed140f283fa79); ?>
<?php ob_start(); ?>
        <?php echo e($tooltip); ?>

    <?php $__slots3f2666fb7f87cdbef97ed140f283fa79['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots3f2666fb7f87cdbef97ed140f283fa79); ?>
<?php _3f2666fb7f87cdbef97ed140f283fa79($__blaze, $__attrs3f2666fb7f87cdbef97ed140f283fa79, $__slots3f2666fb7f87cdbef97ed140f283fa79, ['kbd'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack3f2666fb7f87cdbef97ed140f283fa79)) { $__slots3f2666fb7f87cdbef97ed140f283fa79 = array_pop($__slotsStack3f2666fb7f87cdbef97ed140f283fa79); } ?>
<?php if (! empty($__attrsStack3f2666fb7f87cdbef97ed140f283fa79)) { $__attrs3f2666fb7f87cdbef97ed140f283fa79 = array_pop($__attrsStack3f2666fb7f87cdbef97ed140f283fa79); } ?>
<?php $__blaze->popData(); ?>
<?php $__slotsdfd2184989fa5e11a0c987d1c0b86139['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php _dfd2184989fa5e11a0c987d1c0b86139($__blaze, $__attrsdfd2184989fa5e11a0c987d1c0b86139, $__slotsdfd2184989fa5e11a0c987d1c0b86139, ['position', 'class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__slotsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php if (! empty($__attrsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__attrsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php $__blaze->popData(); ?><?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\sidebar\search.blade.php ENDPATH**/ ?>