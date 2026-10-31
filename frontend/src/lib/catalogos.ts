import { useQuery } from '@tanstack/react-query';
import { api } from './api';

export interface Anio { id: number; anio: number }
export interface Nivel { id: number; nombre: string }
export interface Grado { id: number; nombre: string; numero: number; nivel_id: number; nivel: string }
export interface Seccion { grado_seccion_id: number; grado_id: number; grado: string; seccion_id: number; seccion: string }

const HORA = 60 * 60 * 1000;

export const useAnios = () =>
  useQuery({ queryKey: ['catalogos', 'anios'], queryFn: () => api.get<{ data: Anio[] }>('/catalogos/anios').then((r) => r.data), staleTime: HORA });

export const useNiveles = () =>
  useQuery({ queryKey: ['catalogos', 'niveles'], queryFn: () => api.get<{ data: Nivel[] }>('/catalogos/niveles').then((r) => r.data), staleTime: HORA });

export const useGrados = (nivelId?: string | number) =>
  useQuery({
    queryKey: ['catalogos', 'grados', String(nivelId ?? '')],
    queryFn: () => api.get<{ data: Grado[] }>('/catalogos/grados', { nivel_id: nivelId }).then((r) => r.data),
    enabled: !!nivelId,
    staleTime: HORA,
  });

export const useSecciones = (gradoId?: string | number) =>
  useQuery({
    queryKey: ['catalogos', 'secciones', String(gradoId ?? '')],
    queryFn: () => api.get<{ data: Seccion[] }>('/catalogos/secciones', { grado_id: gradoId }).then((r) => r.data),
    enabled: !!gradoId,
    staleTime: HORA,
  });
