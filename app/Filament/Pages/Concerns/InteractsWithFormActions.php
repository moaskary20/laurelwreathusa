<?php

namespace App\Filament\Pages\Concerns;

use Filament\Pages\Concerns\InteractsWithFormActions as FilamentInteractsWithFormActions;

/**
 * يمنع تكرار أزرار النموذج (حفظ/طباعة/عودة) إذا أُعيد استدعاء تخزين الإجراءات أكثر من مرة.
 */
trait InteractsWithFormActions
{
    use FilamentInteractsWithFormActions {
        bootedInteractsWithFormActions as filamentBootedInteractsWithFormActions;
    }

    public function bootedInteractsWithFormActions(): void
    {
        $this->cachedFormActions = [];
        $this->filamentBootedInteractsWithFormActions();
    }
}
