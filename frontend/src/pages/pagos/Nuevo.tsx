import { useEffect, useState, type FormEvent } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router';
import { ArrowLeft, X } from 'lucide-react';
import { Autocompletar } from '@/components/Autocompletar';
import { Encabezado } from '@/components/Encabezado';
import { Datos, Seccion } from '@/components/Seccion';
import { AlertaError } from '@/components/ui/alerta';
import { Boton } from '@/components/ui/boton';
import { Campo } from '@/components/ui/campo';
import { Selector } from '@/components/ui/selector';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion } from '@/lib/hooks';
import { errorDe, formato, hoy } from '@/lib/utils';
import { TIPO_COBRO, type Concepto } from './tipos';

interface EstudianteCobro { matricula_id: number; estudiante_id: number; dni: string; nombre: string }
interface Saldo { monto_total: string; monto_pagado: string; saldo_restante: string; cuenta_existente: boolean }

const buscarEstudiantes = (q: string) => api.get<{ data: EstudianteCobro[] }>('/pagos/estudiantes', { q }).then((r) => r.data);

export default function NuevoPago() {
  const navigate = useNavigate();
  const [estudiante, setEstudiante] = useState<EstudianteCobro | null>(null);
  const [conceptoId, setConceptoId] = useState('');
  const [abono, setAbono] = useState('');
  const [entregadoPor, setEntregadoPor] = useState('');
  const [fechaPago, setFechaPago] = useState(hoy());
  const [observacion, setObservacion] = useState('');

  const conceptos = useQuery({ queryKey: ['conceptos', 'activos'], queryFn: () => api.get<{ data: Concepto[] }>('/conceptos-pago').then((r) => r.data) });
  const apoderado = useQuery({
    queryKey: ['pagos', 'apoderado', estudiante?.matricula_id],
    queryFn: () => api.get<{ data: { id: number; dni: string; nombre: string } | null }>(`/pagos/matriculas/${estudiante!.matricula_id}/apoderado`).then((r) => r.data),
    enabled: !!estudiante,
  });
  const saldo = useQuery({
    queryKey: ['pagos', 'saldo', estudiante?.matricula_id, conceptoId],
    queryFn: () => api.get<{ data: Saldo }>('/pagos/saldo', { matricula_id: estudiante!.matricula_id, concepto_id: conceptoId }).then((r) => r.data),
    enabled: !!estudiante && !!conceptoId,
  });
  useEffect(() => {
    if (apoderado.data) setEntregadoPor((actual) => actual || apoderado.data!.nombre);
  }, [apoderado.data]);

  const registrar = useAccion(
    () =>
      api.post<{ data: { id: number; saldo_restante: string; cancelado: boolean } }>('/pagos', {
        matricula_id: estudiante?.matricula_id ?? null,
        concepto_id: Number(conceptoId) || null,
        abono_hoy: abono,
        entregado_por: entregadoPor,
        fecha_pago: fechaPago,
        observacion: observacion || null,
      }),
    {
      exito: (r) => (r.data.cancelado ? 'Pago registrado. La cuenta quedó cancelada.' : `Pago registrado. Saldo pendiente: ${formato.soles(r.data.saldo_restante)}.`),
      invalidar: [['pagos'], ['panel'], ['estudiante'], ['responsable']],
      alTerminar: () => navigate('/pagos'),
    },
  );
  const err = registrar.error;
  const concepto = conceptos.data?.find((c) => String(c.id) === conceptoId);
  const enviar = async (e: FormEvent) => {
    e.preventDefault();
    const monto = Number(abono.replace(',', '.'));
    const resumen = estudiante && concepto && monto > 0 ? `${formato.soles(monto)} por «${concepto.descripcion}» de ${estudiante.nombre}.` : 'Revisa los datos del pago.';
    if (await confirmar({ titulo: 'Registrar pago', texto: resumen, accion: 'Registrar' })) registrar.mutate(undefined);
  };

  return (
    <form onSubmit={enviar} noValidate className="mx-auto max-w-3xl">
      <Link to="/pagos" className="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <ArrowLeft className="size-4" aria-hidden /> Pagos
      </Link>
      <Encabezado titulo="Registrar pago" descripcion="Busca al estudiante, elige el concepto y registra el abono de hoy." />
      {err && Object.keys(err.campos).length > 0 && (
        <div className="mb-4">
          <AlertaError>{err.message}</AlertaError>
        </div>
      )}

      <div className="space-y-6">
        <Seccion titulo="Estudiante y concepto">
          <div className="space-y-4">
            {estudiante ? (
              <div className="flex items-center justify-between gap-3 rounded-lg border border-marca-200 bg-marca-50 px-3 py-2.5 text-sm">
                <div>
                  <p className="text-slate-900">{estudiante.nombre}</p>
                  <p className="text-slate-500">
                    DNI {estudiante.dni}
                    {apoderado.data && ` · Apoderado: ${apoderado.data.nombre}`}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    setEstudiante(null);
                    setEntregadoPor('');
                  }}
                  className="rounded-lg p-1 text-slate-500 hover:bg-white"
                  aria-label="Cambiar estudiante"
                >
                  <X className="size-4" />
                </button>
              </div>
            ) : (
              <Autocompletar
                etiqueta="Estudiante matriculado"
                placeholder="DNI o apellidos"
                clave="pagos-estudiantes"
                buscar={buscarEstudiantes}
                idDe={(e) => e.matricula_id}
                textoDe={(e) => e.nombre}
                detalleDe={(e) => `DNI ${e.dni}`}
                alElegir={setEstudiante}
                error={errorDe(err, 'matricula_id')}
              />
            )}
            <Selector etiqueta="Concepto" value={conceptoId} onChange={(e) => setConceptoId(e.target.value)} error={errorDe(err, 'concepto_id')}>
              <option value="">Elige el concepto</option>
              {conceptos.data?.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.descripcion} · {formato.soles(c.monto_default)} ({TIPO_COBRO[c.tipo_cobro].toLowerCase()})
                </option>
              ))}
            </Selector>
            {concepto?.tipo_cobro === 'familia' && <p className="text-sm text-slate-500">Se cobra una vez por familia: el saldo se comparte entre hermanos.</p>}
            {saldo.data && (
              <div className="rounded-lg bg-slate-50 p-4">
                <Datos
                  items={[
                    ['Total', formato.soles(saldo.data.monto_total)],
                    ['Pagado', formato.soles(saldo.data.monto_pagado)],
                    ['Saldo pendiente', <span className="text-marca-700">{formato.soles(saldo.data.saldo_restante)}</span>],
                  ]}
                />
              </div>
            )}
          </div>
        </Seccion>

        <Seccion titulo="Abono">
          <div className="grid gap-4 sm:grid-cols-2">
            <Campo etiqueta="Monto que entrega hoy (S/)" inputMode="decimal" placeholder="0.00" value={abono} onChange={(e) => setAbono(e.target.value)} error={errorDe(err, 'abono_hoy')} />
            <Campo etiqueta="Fecha de pago" type="date" value={fechaPago} onChange={(e) => setFechaPago(e.target.value)} error={errorDe(err, 'fecha_pago')} />
            <div className="sm:col-span-2">
              <Campo etiqueta="Entregado por" value={entregadoPor} onChange={(e) => setEntregadoPor(e.target.value)} error={errorDe(err, 'entregado_por')} />
            </div>
            <div className="sm:col-span-2">
              <Campo etiqueta="Observación (opcional)" maxLength={250} value={observacion} onChange={(e) => setObservacion(e.target.value)} error={errorDe(err, 'observacion')} />
            </div>
          </div>
        </Seccion>
        <div className="flex justify-end">
          <Boton type="submit" cargando={registrar.isPending}>Registrar pago</Boton>
        </div>
      </div>
    </form>
  );
}
