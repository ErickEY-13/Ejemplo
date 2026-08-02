<?php

declare(strict_types=1);

namespace App\Modules\Persons\Observers;

use App\Models\Audit;
use App\Modules\Persons\Models\Person;

class PersonObserver
{
    public function created(Person $person): void
    {
        $this->log($person, 'created', [], $person->getAttributes());
    }

    public function updated(Person $person): void
    {
        // Ignoramos si no hay cambios reales
        if (! $person->wasChanged()) {
            return;
        }

        // dirty() obtiene solo los campos modificados
        $newValues = $person->getDirty();
        $oldValues = array_intersect_key($person->getOriginal(), $newValues);

        $this->log($person, 'updated', $oldValues, $newValues);
    }

    public function deleted(Person $person): void
    {
        $this->log($person, 'deleted', $person->getAttributes(), []);
    }

    public function restored(Person $person): void
    {
        $this->log($person, 'restored', [], $person->getAttributes());
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    private function log(Person $person, string $event, array $old, array $new): void
    {
        // Limpiamos fechas de auditoría que ensucian el log
        unset($old['updated_at'], $new['updated_at'], $old['deleted_at'], $new['deleted_at']);

        // Si después de limpiar no hay cambios a reportar, no guardamos log
        if ($event === 'updated' && empty($new)) {
            return;
        }

        Audit::create([
            'auditable_type' => Person::class,
            'auditable_id' => $person->id,
            'event' => $event,
            'old_values' => empty($old) ? null : $old,
            'new_values' => empty($new) ? null : $new,
            // 'user_id' => auth()->id(), // Cuando haya autenticación
            'created_at' => now(),
        ]);
    }
}
