import { Bar, BarChart, CartesianGrid, LabelList, ResponsiveContainer, Tooltip, XAxis, YAxis, type TooltipContentProps } from 'recharts';
import type { NameType, ValueType } from 'recharts/types/component/DefaultTooltipContent';
import { Tarjeta } from '@/components/ui/tarjeta';
import type { Analiticas } from '@/lib/tipos';
import { cn, fecha, formato } from '@/lib/utils';

const MARCA = '#284fa8';
const MARCA_SUAVE = '#bcd2fb';
const TINTA = '#64748b';
const REJILLA = '#e2e8f0';
const nombreMes = new Intl.DateTimeFormat('es-PE', { month: 'short' });
const mesLargo = new Intl.DateTimeFormat('es-PE', { month: 'long', year: 'numeric' });
const aFecha = (mes: string) => new Date(`${mes}-15T12:00:00`);
const solesCorto = (v: number) => (v >= 1000 ? `S/ ${(v / 1000).toLocaleString('es-PE', { maximumFractionDigits: 1 })} mil` : `S/ ${v}`);

export function Panel({ titulo, detalle, children, className }: { titulo: string; detalle?: string; children: React.ReactNode; className?: string }) {
  return (
    <Tarjeta className={cn('flex flex-col p-5', className)}>
      <div className="mb-4">
        <h2 className="text-sm font-medium text-slate-900">{titulo}</h2>
        {detalle && <p className="text-xs text-slate-500">{detalle}</p>}
      </div>
      <div className="flex-1">{children}</div>
    </Tarjeta>
  );
}

function Vacio({ children }: { children: string }) {
  return <div className="flex h-full min-h-32 items-center justify-center rounded-lg border border-dashed border-slate-200 text-sm text-slate-400">{children}</div>;
}

function Globo({ titulo, linea, detalle }: { titulo: string; linea: string; detalle?: string }) {
  return (
    <div className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-lg shadow-slate-900/5">
      <p className="text-slate-500">{titulo}</p>
      <p className="text-sm font-medium text-slate-900 tabular-nums">{linea}</p>
      {detalle && <p className="text-slate-500">{detalle}</p>}
    </div>
  );
}

/** Barras de ingresos de los últimos 6 meses; el mes actual en color pleno. */
export function IngresosMensuales({ datos }: { datos: Analiticas['ingresos_mensuales'] }) {
  const total = datos.reduce((s, d) => s + d.total, 0);
  const ultimo = datos.length - 1;
  return (
    <div>
      <p className="mb-4 text-2xl font-medium tracking-tight text-slate-900 tabular-nums">
        {formato.soles(total)} <span className="text-sm font-normal text-slate-500">en 6 meses</span>
      </p>
      <div className="h-56">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={datos} margin={{ top: 4, right: 4, bottom: 0, left: 0 }} barCategoryGap="28%">
            <CartesianGrid vertical={false} stroke={REJILLA} strokeDasharray="3 3" />
            <XAxis dataKey="mes" tickFormatter={(m: string) => nombreMes.format(aFecha(m)).replace('.', '')} tick={{ fill: TINTA, fontSize: 12 }} axisLine={false} tickLine={false} />
            <YAxis tickFormatter={solesCorto} tick={{ fill: TINTA, fontSize: 12 }} axisLine={false} tickLine={false} width={64} />
            <Tooltip
              cursor={{ fill: '#f1f5f9' }}
              content={({ active, payload }: TooltipContentProps<ValueType, NameType>) => {
                const d = active ? (payload?.[0]?.payload as Analiticas['ingresos_mensuales'][number] | undefined) : undefined;
                return d ? <Globo titulo={mesLargo.format(aFecha(d.mes))} linea={formato.soles(d.total)} detalle={`${d.pagos} ${d.pagos === 1 ? 'pago' : 'pagos'}`} /> : null;
              }}
            />
            <Bar dataKey="total" radius={[4, 4, 0, 0]} maxBarSize={48} shape={(p: { x?: number; y?: number; width?: number; height?: number; index?: number }) => (
              <rect x={p.x} y={p.y} width={p.width} height={Math.max(p.height ?? 0, 0)} rx={4} fill={p.index === ultimo ? MARCA : MARCA_SUAVE} />
            )} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}

/** Porcentaje cobrado de todas las cuentas, con barra de avance. */
export function Cobranza({ datos }: { datos: Analiticas['cobranza'] }) {
  if (datos.total === 0) return <Vacio>Aún no hay cuentas por cobrar.</Vacio>;
  const porcentaje = Math.round((datos.pagado / datos.total) * 100);
  return (
    <div className="flex h-full flex-col justify-between gap-6">
      <div>
        <p className="text-4xl font-medium tracking-tight text-slate-900 tabular-nums">{porcentaje}%</p>
        <p className="text-sm text-slate-500">de lo facturado ya fue cobrado</p>
      </div>
      <div>
        <div className="flex h-2.5 gap-0.5 overflow-hidden rounded-full bg-slate-100" role="img" aria-label={`${porcentaje}% cobrado`}>
          <div className="rounded-full bg-marca-600" style={{ width: `${porcentaje}%` }} />
          {datos.saldo > 0 && <div className="flex-1 rounded-full bg-amber-300" />}
        </div>
        <dl className="mt-4 space-y-2 text-sm">
          <Fila color="bg-marca-600" texto="Cobrado" valor={formato.soles(datos.pagado)} />
          <Fila color="bg-amber-300" texto="Por cobrar" valor={formato.soles(datos.saldo)} />
          <div className="flex justify-between border-t border-slate-100 pt-2">
            <dt className="text-slate-500">Total facturado</dt>
            <dd className="font-medium text-slate-900 tabular-nums">{formato.soles(datos.total)}</dd>
          </div>
        </dl>
      </div>
    </div>
  );
}

function Fila({ color, texto, valor }: { color: string; texto: string; valor: string }) {
  return (
    <div className="flex items-center justify-between">
      <dt className="flex items-center gap-2 text-slate-600">
        <span className={cn('size-2.5 rounded-full', color)} aria-hidden /> {texto}
      </dt>
      <dd className="text-slate-900 tabular-nums">{valor}</dd>
    </div>
  );
}

/** Alumnos activos por grado (barras horizontales con el número al final); los niveles se resumen arriba. */
export function AlumnosPorGrado({ grados, niveles }: { grados: Analiticas['por_grado']; niveles: Analiticas['por_nivel'] }) {
  if (grados.length === 0) return <Vacio>Sin matrículas activas.</Vacio>;
  const datos = grados.map((g) => ({ ...g, etiqueta: niveles.length > 1 ? `${g.grado} · ${g.nivel}` : g.grado }));
  return (
    <div>
      <div className="mb-4 flex flex-wrap gap-2">
        {niveles.map((n) => (
          <span key={n.nivel} className="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">
            {n.nivel} <span className="font-medium text-slate-900 tabular-nums">{n.total}</span>
          </span>
        ))}
      </div>
      <div style={{ height: Math.max(datos.length * 36, 96) }}>
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={datos} layout="vertical" margin={{ top: 0, right: 32, bottom: 0, left: 0 }} barCategoryGap="30%">
            <XAxis type="number" hide allowDecimals={false} />
            <YAxis type="category" dataKey="etiqueta" width={130} tick={{ fill: TINTA, fontSize: 12 }} axisLine={false} tickLine={false} />
            <Tooltip
              cursor={{ fill: '#f1f5f9' }}
              content={({ active, payload }: TooltipContentProps<ValueType, NameType>) => {
                const d = active ? (payload?.[0]?.payload as (typeof datos)[number] | undefined) : undefined;
                return d ? <Globo titulo={`${d.grado} · ${d.nivel}`} linea={`${d.total} ${d.total === 1 ? 'alumno' : 'alumnos'}`} /> : null;
              }}
            />
            <Bar dataKey="total" fill={MARCA} radius={[0, 4, 4, 0]} maxBarSize={20}>
              <LabelList dataKey="total" position="right" style={{ fill: '#0f172a', fontSize: 12 }} />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}

/** Ingresos del año por concepto, como barras proporcionales al mayor. */
export function IngresosPorConcepto({ datos }: { datos: Analiticas['por_concepto'] }) {
  if (datos.length === 0) return <Vacio>Aún no hay pagos este año.</Vacio>;
  const mayor = Math.max(...datos.map((d) => d.total), 1);
  return (
    <ul className="space-y-3.5">
      {datos.map((d) => (
        <li key={d.concepto} title={`${d.pagos} ${d.pagos === 1 ? 'pago' : 'pagos'}`}>
          <div className="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
            <span className="truncate text-slate-700">{d.concepto}</span>
            <span className="shrink-0 text-slate-900 tabular-nums">{formato.soles(d.total)}</span>
          </div>
          <div className="h-1.5 rounded-full bg-slate-100">
            <div className="h-full rounded-full bg-marca-600" style={{ width: `${(d.total / mayor) * 100}%` }} />
          </div>
        </li>
      ))}
    </ul>
  );
}

/** Últimos pagos registrados. */
export function PagosRecientes({ datos }: { datos: Analiticas['recientes'] }) {
  if (datos.length === 0) return <Vacio>Sin pagos registrados.</Vacio>;
  return (
    <ul className="-my-2 divide-y divide-slate-100">
      {datos.map((p) => (
        <li key={p.id} className="flex items-center justify-between gap-3 py-2.5">
          <div className="min-w-0">
            <p className="truncate text-sm text-slate-900">{p.responsable}</p>
            <p className="truncate text-xs text-slate-500">
              {p.concepto} · {fecha(p.fecha_pago)}
            </p>
          </div>
          <span className="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-sm text-emerald-700 tabular-nums">+{formato.soles(p.monto)}</span>
        </li>
      ))}
    </ul>
  );
}
