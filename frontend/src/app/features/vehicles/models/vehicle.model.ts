import { SelectOption } from '../../../core/api/api.types';

/**
 * Espejo de `App\Modules\Vehicles\Http\Resources\VehicleResource`.
 * Si cambia el recurso en el backend, hay que actualizar esta interfaz.
 */
export interface Vehicle {
  id: number;
  plate: string;
  brand: string;
  model: string;
  description: string;
  year: number;
  type: string;
  type_label: string;
  fuel_type: string;
  fuel_type_label: string;
  color: string | null;
  photo_url: string | null;
  vin: string | null;
  engine_number: string | null;
  mileage: number;
  is_active: boolean;
  notes: string | null;
  deleted_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

/** Cuerpo que acepta POST/PATCH /api/vehicles. */
export interface VehiclePayload {
  plate: string;
  brand: string;
  model: string;
  year: number;
  type: string;
  fuel_type: string;
  color: string | null;
  vin: string | null;
  engine_number: string | null;
  mileage: number;
  is_active: boolean;
  notes: string | null;
}

/** Parámetros admitidos por GET /api/vehicles. */
export interface VehicleFilters {
  search: string;
  type: string;
  fuel_type: string;
  brand: string;
  year_from: string;
  year_to: string;
  is_active: string;
  with_trashed: boolean;
  sort: string;
  direction: 'asc' | 'desc';
  per_page: number;
  page: number;
}

/** Catálogos que devuelve GET /api/vehicles/metadata. */
export interface VehicleMetadata {
  types: SelectOption[];
  fuel_types: SelectOption[];
  brands: string[];
  sortable: string[];
  year_range: { min: number; max: number };
}

export const DEFAULT_VEHICLE_FILTERS: VehicleFilters = {
  search: '',
  type: '',
  fuel_type: '',
  brand: '',
  year_from: '',
  year_to: '',
  is_active: '',
  with_trashed: false,
  sort: 'created_at',
  direction: 'desc',
  per_page: 15,
  page: 1,
};
