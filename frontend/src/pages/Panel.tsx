import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router';
import { AlertTriangle, ArrowRight, ClipboardPlus, GraduationCap, HandCoins, Wallet, type LucideIcon } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { Encabezado } from '@/components/Encabezado';
import { AlertaError } from '@/components/ui/alerta';
import { Tarjeta } from '@/components/ui/tarjeta';
import { api } from '@/lib/api';
import type { Analiticas, Metricas } from '@/lib/tipos';
import { formato } from '@/lib/utils';
import { AlumnosPorGrado, Cobranza, IngresosMensuales, IngresosPorConcepto, PagosRecientes, Panel as Bloque } from './panel/Graficos';

interface PropsMetrica {
  titulo: string;
  valor: string;
  icono: LucideIcon;
  tono: string;
  cargando: boolean;
}

function Metrica({ titulo, valor, icono: Icono, tono, cargando }: PropsMetrica) {
  return (
    <Tarjeta className="flex items-center gap-4 p-5">
      <div className={`flex size-11 shrink-0 items-center justify-center rounded-lg ${tono}`}>
        <Icono className="size-5" aria-hidden />
      </div>
      <div className="min-w-0">
        <p className="text-sm text-slate-500">{titulo}</p>
        {cargando ? (
          <div className="mt-1 h-7 w-24 animate-pulse rounded bg-slate-100" />
        ) : (
          <p className="text-2xl font-medium tracking-tight text-slate-900 tabular-nums">{valor}</p>
        )}
      </div>
    </Tarjeta>
  );
}

const MES = new Intl.DateTimeFormat('es-PE', { month: 'long' }).format(new Date());

export default function Panel() {
  const { usuario, puede } = useSesion();
  const { data, isPending, error } = useQuery({
    queryKey: ['panel', 'metricas'],
    queryFn: () => api.get<{ data: Metricas }>('/panel/metricas').then((r) => r.data),
  });
  const analiticas = useQuery({
    queryKey: ['panel', 'analiticas'],
    queryFn: () => api.get<{ data: Analiticas }>('/panel/analiticas').then((r) => r.data),
  });
  const a = analiticas.data;

  const accesos = [
    puede('p_matricular') && { ruta: '/matriculas/nueva', titulo: 'Nueva matrícula', detalle: 'Registrar estudiante y familia', icono: ClipboardPlus },
    puede('p_cobrar') && { ruta: '/pagos/nuevo', titulo: 'Registrar pago', detalle: 'Cobrar un concepto', icono: HandCoins },
    { ruta: '/estudiantes', titulo: 'Buscar estudiante', detalle: 'Ver ficha, familia y pagos', icono: GraduationCap },
  ].filter((a) => a !== false);

  return (
    <>
      <Encabezado titulo={`Hola, ${usuario?.nombres.split(' ')[0] ?? ''}`} descripcion="Resumen del colegio hoy." />
      {error && <AlertaError>{error.message}</AlertaError>}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Metrica titulo="Estudiantes activos" valor={formato.numero(data?.estudiantes_activos ?? 0)} icono={GraduationCap} tono="bg-marca-50 text-marca-600" cargando={isPending} />
        <Metrica titulo={`Ingresos de ${MES}`} valor={formato.soles(data?.ingresos_mes ?? 0)} icono={Wallet} tono="bg-emerald-50 text-emerald-600" cargando={isPending} />
        <Metrica titulo="Cuentas con deuda" valor={formato.numero(data?.deudas_pendientes ?? 0)} icono={AlertTriangle} tono="bg-amber-50 text-amber-600" cargando={isPending} />
      </div>

      {analiticas.error && <div className="mt-6"><AlertaError>{analiticas.error.message}</AlertaError></div>}
      {!a && analiticas.isPending && (
        <div className="mt-6 grid gap-4 lg:grid-cols-3">
          <div className="h-80 animate-pulse rounded-xl bg-white/70 lg:col-span-2" />
          <div className="h-80 animate-pulse rounded-xl bg-white/70" />
        </div>
      )}
      {a && (
        <>
          <div className="mt-6 grid gap-4 lg:grid-cols-3">
            <Bloque titulo="Ingresos" detalle="Últimos 6 meses" className="lg:col-span-2">
              <IngresosMensuales datos={a.ingresos_mensuales} />
            </Bloque>
            <Bloque titulo="Cobranza" detalle="Todas las cuentas abiertas">
              <Cobranza datos={a.cobranza} />
            </Bloque>
          </div>
          <div className="mt-4 grid gap-4 lg:grid-cols-3">
            <Bloque titulo="Alumnos por grado" detalle="Matrículas activas">
              <AlumnosPorGrado grados={a.por_grado} niveles={a.por_nivel} />
            </Bloque>
            <Bloque titulo="Ingresos por concepto" detalle={`En ${new Date().getFullYear()}`}>
              <IngresosPorConcepto datos={a.por_concepto} />
            </Bloque>
            <Bloque titulo="Últimos pagos">
              <PagosRecientes datos={a.recientes} />
            </Bloque>
          </div>
        </>
      )}

      <h2 className="mt-10 mb-3 text-sm font-medium text-slate-900">Accesos rápidos</h2>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {accesos.map(({ ruta, titulo, detalle, icono: Icono }) => (
          <Link key={ruta} to={ruta} className="group">
            <Tarjeta className="flex items-center gap-3 p-4 transition-colors group-hover:border-marca-500/50">
              <Icono className="size-5 text-marca-600" aria-hidden />
              <div className="min-w-0 flex-1">
                <p className="text-sm text-slate-900">{titulo}</p>
                <p className="text-xs text-slate-500">{detalle}</p>
              </div>
              <ArrowRight className="size-4 text-slate-400 transition-transform group-hover:translate-x-0.5" aria-hidden />
            </Tarjeta>
          </Link>
        ))}
      </div>
    </>
  );
}
