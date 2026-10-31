import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface Curso { id: number; nombre: string; grado: string }
export interface Periodo { id: number; nombre: string; fecha_inicio: string; fecha_fin: string }

export const useCursos = () => useQuery({ queryKey: ['cursos'], queryFn: () => api.get<{ data: Curso[] }>('/cursos').then((r) => r.data) });
export const usePeriodos = () => useQuery({ queryKey: ['periodos'], queryFn: () => api.get<{ data: Periodo[] }>('/periodos').then((r) => r.data) });
