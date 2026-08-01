import { FormControl, FormGroup } from '@angular/forms';
import { describe, expect, it } from 'vitest';

import { ApiError } from '../../core/api/api.types';
import { applyServerErrors, firstErrorMessage } from './server-errors';

function buildForm(): FormGroup {
  return new FormGroup({
    plate: new FormControl(''),
    brand: new FormControl(''),
  });
}

function validationError(errors: Record<string, string[]>): ApiError {
  return { status: 422, message: 'The given data was invalid.', errors };
}

describe('applyServerErrors', () => {
  it('vuelca los mensajes 422 sobre el control correspondiente', () => {
    const form = buildForm();

    const orphans = applyServerErrors(form, validationError({ plate: ['Placa duplicada.'] }));

    expect(orphans).toEqual([]);
    expect(form.get('plate')?.errors).toEqual({ server: 'Placa duplicada.' });
    expect(form.get('plate')?.touched).toBe(true);
  });

  it('devuelve los mensajes que no encajan en ningún control', () => {
    const form = buildForm();

    const orphans = applyServerErrors(form, validationError({ desconocido: ['Algo falló.'] }));

    expect(orphans).toEqual(['Algo falló.']);
  });

  it('no hace nada cuando el error no es de validación', () => {
    const form = buildForm();

    const orphans = applyServerErrors(form, { status: 500, message: 'Boom', errors: null });

    expect(orphans).toEqual([]);
    expect(form.get('plate')?.errors).toBeNull();
  });
});

describe('firstErrorMessage', () => {
  it('da prioridad al mensaje que viene del servidor', () => {
    const form = buildForm();
    applyServerErrors(form, validationError({ plate: ['Placa duplicada.'] }));

    expect(firstErrorMessage(form, 'plate', 'la placa')).toBe('Placa duplicada.');
  });

  it('calla mientras el usuario no haya tocado el campo', () => {
    const form = new FormGroup({ plate: new FormControl('') });
    form.get('plate')?.setErrors({ required: true });

    expect(firstErrorMessage(form, 'plate', 'la placa')).toBeNull();
  });

  it('traduce los validadores de Angular', () => {
    const form = new FormGroup({ plate: new FormControl('') });
    form.get('plate')?.setErrors({ required: true });
    form.get('plate')?.markAsTouched();

    expect(firstErrorMessage(form, 'plate', 'la placa')).toBe('Indica la placa.');
  });
});
