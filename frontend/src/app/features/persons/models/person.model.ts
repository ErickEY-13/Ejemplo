import { SelectOption } from '../../../core/api/api.types';

/**
 * Espejo de `App\Modules\Persons\Http\Resources\PersonResource`.
 * Si cambia el recurso en el backend, hay que actualizar esta interfaz.
 */
export interface Person {
  id: number;
  document_type: string;
  document_type_label: string;
  document_number: string;
  first_name: string;
  last_name: string;
  full_name: string;
  birth_date: string | null;
  age: number | null;
  gender: string;
  gender_label: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  is_active: boolean;
  notes: string | null;
  deleted_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

/** Cuerpo que acepta POST/PATCH /api/persons. */
export interface PersonPayload {
  document_type: string;
  document_number: string;
  first_name: string;
  last_name: string;
  birth_date: string | null;
  gender: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  is_active: boolean;
  notes: string | null;
}

/** Parámetros admitidos por GET /api/persons. */
export interface PersonFilters {
  search: string;
  document_type: string;
  is_active: string;
  with_trashed: boolean;
  sort: string;
  direction: 'asc' | 'desc';
  per_page: number;
  page: number;
}

/** Catálogos que devuelve GET /api/persons/metadata. */
export interface PersonMetadata {
  document_types: SelectOption[];
  genders: SelectOption[];
  sortable: string[];
}

export const DEFAULT_PERSON_FILTERS: PersonFilters = {
  search: '',
  document_type: '',
  is_active: '',
  with_trashed: false,
  sort: 'created_at',
  direction: 'desc',
  per_page: 15,
  page: 1,
};
