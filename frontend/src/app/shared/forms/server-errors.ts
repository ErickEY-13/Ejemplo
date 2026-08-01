import { FormGroup } from '@angular/forms';

import { ApiError } from '../../core/api/api.types';

/**
 * Vuelca los errores de validación de Laravel (422) sobre los controles del
 * formulario, para poder mostrarlos junto a cada campo.
 *
 * @returns los mensajes que no corresponden a ningún control del formulario.
 */
export function applyServerErrors(form: FormGroup, error: ApiError): string[] {
  const orphans: string[] = [];

  if (!error.errors) {
    return orphans;
  }

  for (const [field, messages] of Object.entries(error.errors)) {
    const control = form.get(field);
    const message = messages[0];

    if (control) {
      control.setErrors({ ...(control.errors ?? {}), server: message });
      control.markAsTouched();
    } else {
      orphans.push(message);
    }
  }

  return orphans;
}

/** Primer mensaje de error a mostrar para un control ya "tocado". */
export function firstErrorMessage(form: FormGroup, field: string, label: string): string | null {
  const control = form.get(field);

  if (!control || !control.errors || !(control.touched || control.dirty)) {
    return null;
  }

  const errors = control.errors;

  if (errors['server']) {
    return errors['server'] as string;
  }
  if (errors['required']) {
    return `Indica ${label}.`;
  }
  if (errors['email']) {
    return 'El correo electrónico no tiene un formato válido.';
  }
  if (errors['maxlength']) {
    return `${capitalize(label)} supera el máximo de ${errors['maxlength'].requiredLength} caracteres.`;
  }
  if (errors['minlength']) {
    return `${capitalize(label)} debe tener al menos ${errors['minlength'].requiredLength} caracteres.`;
  }
  if (errors['min']) {
    return `${capitalize(label)} no puede ser menor que ${errors['min'].min}.`;
  }
  if (errors['max']) {
    return `${capitalize(label)} no puede ser mayor que ${errors['max'].max}.`;
  }
  if (errors['pattern']) {
    return `${capitalize(label)} tiene un formato no válido.`;
  }

  return 'El valor introducido no es válido.';
}

function capitalize(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}
