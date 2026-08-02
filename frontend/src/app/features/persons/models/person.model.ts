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
  marital_status: string | null;
  marital_status_label: string | null;
  education_level: string | null;
  education_level_label: string | null;
  children_count: number | null;
  email: string | null;
  phone: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  address: string | null;
  ruc: string | null;
  pension_system: string | null;
  pension_system_label: string | null;
  site: string | null;
  site_label: string | null;
  area: string | null;
  area_label: string | null;
  position: string | null;
  contract_type: string | null;
  contract_type_label: string | null;
  hire_date: string | null;
  work_shift: string | null;
  work_shift_label: string | null;
  is_active: boolean;
  notes: string | null;
  photo_url: string | null;
  documents: PersonDocument[];
  deleted_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

/** Documento adjunto de una persona. */
export interface PersonDocument {
  id: number;
  type: string;
  type_label: string;
  original_name: string;
  file_url: string;
  mime_type: string;
  size_bytes: number;
  created_at: string | null;
}

/** Registro de auditoría (Timeline). */
export interface Audit {
  id: number;
  event: 'created' | 'updated' | 'deleted' | 'restored';
  old_values: Record<string, any> | null;
  new_values: Record<string, any> | null;
  author_name: string;
  created_at: string;
}

/** Cuerpo que acepta POST/PATCH /api/persons. */
export interface PersonPayload {
  document_type: string;
  document_number: string;
  first_name: string;
  last_name: string;
  birth_date: string | null;
  gender: string;
  marital_status: string | null;
  education_level: string | null;
  children_count: number | null;
  email: string | null;
  phone: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  address: string | null;
  ruc: string | null;
  pension_system: string | null;
  site: string | null;
  area: string | null;
  position: string | null;
  contract_type: string | null;
  hire_date: string | null;
  work_shift: string | null;
  is_active: boolean;
  notes: string | null;
}

/** Parámetros admitidos por GET /api/persons. */
export interface PersonFilters {
  search: string;
  document_type: string;
  area: string;
  contract_type: string;
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
  marital_statuses: SelectOption[];
  education_levels: SelectOption[];
  areas: SelectOption[];
  contract_types: SelectOption[];
  work_shifts: SelectOption[];
  pension_systems: SelectOption[];
  sites: SelectOption[];
  document_types_attach: SelectOption[];
  sortable: string[];
}

export const DEFAULT_PERSON_FILTERS: PersonFilters = {
  search: '',
  document_type: '',
  area: '',
  contract_type: '',
  is_active: '',
  with_trashed: false,
  sort: 'created_at',
  direction: 'desc',
  per_page: 15,
  page: 1,
};
