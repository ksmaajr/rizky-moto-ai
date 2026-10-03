<?php
if (!function_exists('_585a481952a841fa709afbdfc49ae6f5')):
function _585a481952a841fa709afbdfc49ae6f5($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
extract(Flux::forwardedAttributes($attributes, [
    'name',
    'descriptionTrailing',
    'description',
    'label',
    'badge',
]));
?>

<?php $descriptionTrailing = $descriptionTrailing ??= $attributes->pluck('description:trailing'); ?>

<?php
$__defaults = [
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'descriptionTrailing' => null,
    'description' => null,
    'label' => null,
    'badge' => null,
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$descriptionTrailing ??= $attributes['description-trailing'] ?? $attributes['descriptionTrailing'] ?? $__defaults['descriptionTrailing']; unset($attributes['descriptionTrailing'], $attributes['description-trailing']);
$description ??= $attributes['description'] ?? $__defaults['description']; unset($attributes['description']);
$label ??= $attributes['label'] ?? $__defaults['label']; unset($attributes['label']);
$badge ??= $attributes['badge'] ?? $__defaults['badge']; unset($attributes['badge']);
unset($__defaults);
?>

<?php if (isset($label) || isset($description) || isset($descriptionTrailing)): ?>
    <?php

        $fieldAttributes = Flux::attributesAfter('field:', $attributes, []);
        $labelAttributes = Flux::attributesAfter('label:', $attributes, ['badge' => $badge]);
        $descriptionAttributes = Flux::attributesAfter('description:', $attributes, []);
        $errorAttributes = Flux::attributesAfter('error:', $attributes, ['name' => $name]);
    ?>
    <?php if (!function_exists('_7938340a18942ee307e904d7ed2d0ec7')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/field.blade.php', $__blaze->compiledPath.'/7938340a18942ee307e904d7ed2d0ec7.php'); require $__blaze->compiledPath.'/7938340a18942ee307e904d7ed2d0ec7.php'; } ?>
<?php if (isset($__slots7938340a18942ee307e904d7ed2d0ec7)) { $__slotsStack7938340a18942ee307e904d7ed2d0ec7[] = $__slots7938340a18942ee307e904d7ed2d0ec7; } ?>
<?php if (isset($__attrs7938340a18942ee307e904d7ed2d0ec7)) { $__attrsStack7938340a18942ee307e904d7ed2d0ec7[] = $__attrs7938340a18942ee307e904d7ed2d0ec7; } ?>
<?php $__attrs7938340a18942ee307e904d7ed2d0ec7 = ['attributes' => $fieldAttributes]; ?>
<?php $__slots7938340a18942ee307e904d7ed2d0ec7 = []; ?>
<?php $__blaze->pushData($__attrs7938340a18942ee307e904d7ed2d0ec7); ?>
<?php ob_start(); ?>
        <?php if (isset($label)): ?>
            <?php if (!function_exists('_d8124eaca699a248e1fe30d46f83632c')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/label.blade.php', $__blaze->compiledPath.'/d8124eaca699a248e1fe30d46f83632c.php'); require $__blaze->compiledPath.'/d8124eaca699a248e1fe30d46f83632c.php'; } ?>
<?php if (isset($__slotsd8124eaca699a248e1fe30d46f83632c)) { $__slotsStackd8124eaca699a248e1fe30d46f83632c[] = $__slotsd8124eaca699a248e1fe30d46f83632c; } ?>
<?php if (isset($__attrsd8124eaca699a248e1fe30d46f83632c)) { $__attrsStackd8124eaca699a248e1fe30d46f83632c[] = $__attrsd8124eaca699a248e1fe30d46f83632c; } ?>
<?php $__attrsd8124eaca699a248e1fe30d46f83632c = ['attributes' => $labelAttributes]; ?>
<?php $__slotsd8124eaca699a248e1fe30d46f83632c = []; ?>
<?php $__blaze->pushData($__attrsd8124eaca699a248e1fe30d46f83632c); ?>
<?php ob_start(); ?><?php echo e($label); ?><?php $__slotsd8124eaca699a248e1fe30d46f83632c['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsd8124eaca699a248e1fe30d46f83632c); ?>
<?php _d8124eaca699a248e1fe30d46f83632c($__blaze, $__attrsd8124eaca699a248e1fe30d46f83632c, $__slotsd8124eaca699a248e1fe30d46f83632c, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackd8124eaca699a248e1fe30d46f83632c)) { $__slotsd8124eaca699a248e1fe30d46f83632c = array_pop($__slotsStackd8124eaca699a248e1fe30d46f83632c); } ?>
<?php if (! empty($__attrsStackd8124eaca699a248e1fe30d46f83632c)) { $__attrsd8124eaca699a248e1fe30d46f83632c = array_pop($__attrsStackd8124eaca699a248e1fe30d46f83632c); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>

        <?php if (isset($description)): ?>
            <?php if (!function_exists('_845c4496575c1837f8a18749e68aa330')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/description.blade.php', $__blaze->compiledPath.'/845c4496575c1837f8a18749e68aa330.php'); require $__blaze->compiledPath.'/845c4496575c1837f8a18749e68aa330.php'; } ?>
<?php if (isset($__slots845c4496575c1837f8a18749e68aa330)) { $__slotsStack845c4496575c1837f8a18749e68aa330[] = $__slots845c4496575c1837f8a18749e68aa330; } ?>
<?php if (isset($__attrs845c4496575c1837f8a18749e68aa330)) { $__attrsStack845c4496575c1837f8a18749e68aa330[] = $__attrs845c4496575c1837f8a18749e68aa330; } ?>
<?php $__attrs845c4496575c1837f8a18749e68aa330 = ['attributes' => $descriptionAttributes]; ?>
<?php $__slots845c4496575c1837f8a18749e68aa330 = []; ?>
<?php $__blaze->pushData($__attrs845c4496575c1837f8a18749e68aa330); ?>
<?php ob_start(); ?><?php echo e($description); ?><?php $__slots845c4496575c1837f8a18749e68aa330['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots845c4496575c1837f8a18749e68aa330); ?>
<?php _845c4496575c1837f8a18749e68aa330($__blaze, $__attrs845c4496575c1837f8a18749e68aa330, $__slots845c4496575c1837f8a18749e68aa330, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack845c4496575c1837f8a18749e68aa330)) { $__slots845c4496575c1837f8a18749e68aa330 = array_pop($__slotsStack845c4496575c1837f8a18749e68aa330); } ?>
<?php if (! empty($__attrsStack845c4496575c1837f8a18749e68aa330)) { $__attrs845c4496575c1837f8a18749e68aa330 = array_pop($__attrsStack845c4496575c1837f8a18749e68aa330); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>

        <?php echo e($slot); ?>


        
        <?php $__getScope = fn($scope = []) => $scope; ?><?php if (isset($scope)) $__scope = $scope; ?><?php $scope = $__getScope(scope: ['attributes' => $errorAttributes->getAttributes()]); ?>
        <?php if (!function_exists('_7888599f915327ed3f218c5e1d5ace9a')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/error.blade.php', $__blaze->compiledPath.'/7888599f915327ed3f218c5e1d5ace9a.php'); require $__blaze->compiledPath.'/7888599f915327ed3f218c5e1d5ace9a.php'; } ?>
<?php $__blaze->pushData(['attributes' => new \Illuminate\View\ComponentAttributeBag($scope['attributes'])]); ?>
<?php _7888599f915327ed3f218c5e1d5ace9a($__blaze, ['attributes' => new \Illuminate\View\ComponentAttributeBag($scope['attributes'])], [], ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
        <?php if (isset($__scope)) { $scope = $__scope; unset($__scope); } ?>

        <?php if (isset($descriptionTrailing)): ?>
            <?php if (!function_exists('_845c4496575c1837f8a18749e68aa330')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/description.blade.php', $__blaze->compiledPath.'/845c4496575c1837f8a18749e68aa330.php'); require $__blaze->compiledPath.'/845c4496575c1837f8a18749e68aa330.php'; } ?>
<?php if (isset($__slots845c4496575c1837f8a18749e68aa330)) { $__slotsStack845c4496575c1837f8a18749e68aa330[] = $__slots845c4496575c1837f8a18749e68aa330; } ?>
<?php if (isset($__attrs845c4496575c1837f8a18749e68aa330)) { $__attrsStack845c4496575c1837f8a18749e68aa330[] = $__attrs845c4496575c1837f8a18749e68aa330; } ?>
<?php $__attrs845c4496575c1837f8a18749e68aa330 = ['attributes' => $descriptionAttributes]; ?>
<?php $__slots845c4496575c1837f8a18749e68aa330 = []; ?>
<?php $__blaze->pushData($__attrs845c4496575c1837f8a18749e68aa330); ?>
<?php ob_start(); ?><?php echo e($descriptionTrailing); ?><?php $__slots845c4496575c1837f8a18749e68aa330['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots845c4496575c1837f8a18749e68aa330); ?>
<?php _845c4496575c1837f8a18749e68aa330($__blaze, $__attrs845c4496575c1837f8a18749e68aa330, $__slots845c4496575c1837f8a18749e68aa330, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack845c4496575c1837f8a18749e68aa330)) { $__slots845c4496575c1837f8a18749e68aa330 = array_pop($__slotsStack845c4496575c1837f8a18749e68aa330); } ?>
<?php if (! empty($__attrsStack845c4496575c1837f8a18749e68aa330)) { $__attrs845c4496575c1837f8a18749e68aa330 = array_pop($__attrsStack845c4496575c1837f8a18749e68aa330); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>
    <?php $__slots7938340a18942ee307e904d7ed2d0ec7['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots7938340a18942ee307e904d7ed2d0ec7); ?>
<?php _7938340a18942ee307e904d7ed2d0ec7($__blaze, $__attrs7938340a18942ee307e904d7ed2d0ec7, $__slots7938340a18942ee307e904d7ed2d0ec7, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack7938340a18942ee307e904d7ed2d0ec7)) { $__slots7938340a18942ee307e904d7ed2d0ec7 = array_pop($__slotsStack7938340a18942ee307e904d7ed2d0ec7); } ?>
<?php if (! empty($__attrsStack7938340a18942ee307e904d7ed2d0ec7)) { $__attrs7938340a18942ee307e904d7ed2d0ec7 = array_pop($__attrsStack7938340a18942ee307e904d7ed2d0ec7); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php echo e($slot); ?>

<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\with-field.blade.php ENDPATH**/ ?>