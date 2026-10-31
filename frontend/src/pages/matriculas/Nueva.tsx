import { useEffect, useState, type FormEvent } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { ArrowLeft, X } from 'lucide-react';
import { Autocompletar } from '@/components/Autocompletar';
import { Encabezado } from '@/components/Encabezado';
import { Seccion } from '@/components/Seccion';
import { AlertaError } from '@/components/ui/alerta';
import { Boton } from '@/components/ui/boton';
import { Campo } from '@/components/ui/campo';
import { Selector } from '@/components/ui/selector';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAnios, useGrados, useNiveles, useSecciones } from '@/lib/catalogos';
import { useAccion } from '@/lib/hooks';
import type { Persona } from '@/lib/tipos';
import { cn, errorDe, nombreCompleto } from '@/lib/utils';
import { FormContacto } from './FormContacto';
import { contactoVacio, desdeApi, tieneDatos, TIPOS, type DatosContacto, type DatosMatricula, type TipoContacto } from './formulario';

const buscarEstudiantes = (q: string) => api.get<{ data: Persona[] }>('/estudiantes/buscar', { q }).then((r) => r.data);
const datosMatricula = (id: number) => api.get<{ data: DatosMatricula }>(`/estudiantes/${id}/datos-matricula`).then((r) => r.data);

type Familia = Record<TipoContacto, DatosContacto>;
const familiaVacia = (): Familia => ({ padre: contactoVacio(), madre: contactoVacio(), emergencia: contactoVacio() });

function apoderadoDe(d: DatosMatricula): TipoContacto {
  return TIPOS.find((t) => d.familia[t]?.es_apoderado) ?? TIPOS.find((t) => d.familia[t]) ?? 'madre';
}

export default function NuevaMatricula() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const anios = useAnios();
  const niveles = useNiveles();

  const [modo, setModo] = useState<'nuevo' | 'existente'>(params.get('estudiante_id') ? 'existente' : 'nuevo');
  const [existenteId, setExistenteId] = useState<number | null>(Number(params.get('estudiante_id')) || null);
  const [estudiante, setEstudiante] = useState({ dni: '', nombres: '', apellido_paterno: '', apellido_materno: '' });
  const [hermano, setHermano] = useState<Persona | null>(null);
  const [familia, setFamilia] = useState<Familia>(familiaVacia);
  const [apoderado, setApoderado] = useState<TipoContacto>('madre');
  const [anio, setAnio] = useState('');
  const [nivelId, setNivelId] = useState('');
  const [gradoId, setGradoId] = useState('');
  const [gradoSeccionId, setGradoSeccionId] = useState('');
  const grados = useGrados(nivelId);
  const secciones = useSecciones(gradoId);

  // La familia se precarga del estudiante elegido o, si es nuevo, del hermano.
  const origenFamilia = modo === 'existente' ? existenteId : (hermano?.id ?? null);
  const precarga = useQuery({ queryKey: ['datos-matricula', origenFamilia], queryFn: () => datosMatricula(origenFamilia!), enabled: !!origenFamilia });
  useEffect(() => {
    if (!precarga.data) return;
    const { familia: f } = precarga.data;
    setFamilia({ padre: desdeApi(f.padre), madre: desdeApi(f.madre), emergencia: desdeApi(f.emergencia) });
    setApoderado(apoderadoDe(precarga.data));
  }, [precarga.data]);
  useEffect(() => {
    if (!anio && anios.data?.length) setAnio(String(anios.data[0].anio));
  }, [anio, anios.data]);

  const registrar = useAccion(
    () => {
      const contactos = Object.fromEntries(TIPOS.filter((t) => tieneDatos(familia[t])).map((t) => [t, familia[t]]));
      return api.post<{ data: { id: number } }>('/matriculas', {
        ...(modo === 'existente' ? { estudiante_id: existenteId } : { estudiante, hermano_id: hermano?.id ?? null }),
        ...contactos,
        apoderado_principal: apoderado,
        anio: Number(anio),
        grado_seccion_id: Number(gradoSeccionId) || null,
      });
    },
    {
      exito: 'Matrícula registrada.',
      invalidar: [['matriculas'], ['estudiantes'], ['estudiante'], ['panel']],
      alTerminar: () => navigate(modo === 'existente' && existenteId ? `/estudiantes/${existenteId}` : '/matriculas'),
    },
  );
  const err = registrar.error;
  const conCampos = !!err && Object.keys(err.campos).length > 0;

  const cambiarModo = (m: 'nuevo' | 'existente') => {
    setModo(m);
    setExistenteId(null);
    setHermano(null);
    setFamilia(familiaVacia());
    registrar.reset();
  };
  const campoEstudiante = (clave: keyof typeof estudiante) => ({
    value: estudiante[clave],
    onChange: (e: { target: { value: string } }) => setEstudiante((d) => ({ ...d, [clave]: e.target.value })),
    error: errorDe(err, `estudiante.${clave}`),
  });
  const enviar = async (e: FormEvent) => {
    e.preventDefault();
    if (await confirmar({ titulo: 'Registrar matrícula', texto: `Se matriculará al estudiante para el año ${anio} con los datos de familia ingresados.`, accion: 'Registrar' })) registrar.mutate(undefined);
  };
  const autocompletarEstudiante = {
    clave: 'estudiantes',
    buscar: buscarEstudiantes,
    idDe: (p: Persona) => p.id,
    textoDe: (p: Persona) => nombreCompleto(p, true),
    detalleDe: (p: Persona) => `DNI ${p.dni}`,
  };

  return (
    <form onSubmit={enviar} noValidate>
      <Link to="/matriculas" className="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <ArrowLeft className="size-4" aria-hidden /> Matrículas
      </Link>
      <Encabezado titulo="Nueva matrícula" descripcion="Registra al estudiante, su familia y el grado en el que estudiará." />

      {conCampos && (
        <div className="mb-4">
          <AlertaError>{err.message} Revisa los campos marcados.</AlertaError>
        </div>
      )}

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <Seccion titulo="Estudiante">
            <div className="mb-4 inline-flex rounded-lg bg-slate-100 p-1 text-sm" role="tablist">
              {(['nuevo', 'existente'] as const).map((m) => (
                <button
                  key={m}
                  type="button"
                  role="tab"
                  aria-selected={modo === m}
                  onClick={() => cambiarModo(m)}
                  className={cn('rounded-md px-3 py-1.5 font-medium', modo === m ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-700')}
                >
                  {m === 'nuevo' ? 'Estudiante nuevo' : 'Ya registrado'}
                </button>
              ))}
            </div>

            {modo === 'existente' ? (
              existenteId ? (
                <Elegido
                  texto={precarga.data ? nombreCompleto(precarga.data.estudiante) : 'Cargando…'}
                  detalle={precarga.data ? `DNI ${precarga.data.estudiante.dni}` : undefined}
                  alQuitar={() => cambiarModo('existente')}
                />
              ) : (
                <Autocompletar
                  etiqueta="Buscar estudiante"
                  placeholder="DNI o apellidos"
                  {...autocompletarEstudiante}
                  alElegir={(p) => setExistenteId(p.id)}
                  error={errorDe(err, 'estudiante_id') ?? errorDe(err, 'estudiante')}
                />
              )
            ) : (
              <div className="space-y-4">
                <div className="grid gap-3 sm:grid-cols-2">
                  <Campo etiqueta="DNI" inputMode="numeric" maxLength={8} {...campoEstudiante('dni')} />
                  <div className="hidden sm:block" />
                  <div className="sm:col-span-2">
                    <Campo etiqueta="Nombres" {...campoEstudiante('nombres')} />
                  </div>
                  <Campo etiqueta="Apellido paterno" {...campoEstudiante('apellido_paterno')} />
                  <Campo etiqueta="Apellido materno" {...campoEstudiante('apellido_materno')} />
                </div>
                {errorDe(err, 'estudiante') && <p className="text-sm text-red-600">{errorDe(err, 'estudiante')}</p>}
                <div className="border-t border-slate-100 pt-4">
                  {hermano ? (
                    <Elegido texto={`Hermano: ${nombreCompleto(hermano)}`} detalle="La familia se tomó de su ficha." alQuitar={() => setHermano(null)} />
                  ) : (
                    <Autocompletar
                      etiqueta="¿Tiene un hermano en el colegio? (opcional)"
                      placeholder="Busca al hermano para copiar la familia"
                      {...autocompletarEstudiante}
                      alElegir={setHermano}
                      error={errorDe(err, 'hermano_id')}
                    />
                  )}
                </div>
              </div>
            )}
          </Seccion>

          <Seccion titulo="Familia">
            {precarga.isFetching && <p className="mb-3 text-sm text-slate-500">Cargando datos de la familia…</p>}
            <div className="space-y-4">
              {TIPOS.map((t) => (
                <FormContacto
                  key={t}
                  tipo={t}
                  datos={familia[t]}
                  alCambiar={(d) => setFamilia((f) => ({ ...f, [t]: d }))}
                  apoderado={apoderado === t}
                  alMarcarApoderado={() => setApoderado(t)}
                  error={err}
                />
              ))}
            </div>
            {errorDe(err, 'apoderado_principal') && <p className="mt-3 text-sm text-red-600">{errorDe(err, 'apoderado_principal')}</p>}
          </Seccion>
        </div>

        <div className="space-y-6">
          <Seccion titulo="Grado y sección">
            <div className="space-y-4">
              <Selector etiqueta="Año" value={anio} onChange={(e) => setAnio(e.target.value)} error={errorDe(err, 'anio')}>
                {anios.data?.map((a) => <option key={a.id} value={a.anio}>{a.anio}</option>)}
              </Selector>
              <Selector
                etiqueta="Nivel"
                value={nivelId}
                onChange={(e) => {
                  setNivelId(e.target.value);
                  setGradoId('');
                  setGradoSeccionId('');
                }}
              >
                <option value="">Elige el nivel</option>
                {niveles.data?.map((n) => <option key={n.id} value={n.id}>{n.nombre}</option>)}
              </Selector>
              <Selector
                etiqueta="Grado"
                value={gradoId}
                disabled={!nivelId}
                onChange={(e) => {
                  setGradoId(e.target.value);
                  setGradoSeccionId('');
                }}
              >
                <option value="">Elige el grado</option>
                {grados.data?.map((g) => <option key={g.id} value={g.id}>{g.nombre}</option>)}
              </Selector>
              <Selector
                etiqueta="Sección"
                value={gradoSeccionId}
                disabled={!gradoId}
                onChange={(e) => setGradoSeccionId(e.target.value)}
                error={errorDe(err, 'grado_seccion_id')}
              >
                <option value="">Elige la sección</option>
                {secciones.data?.map((s) => <option key={s.grado_seccion_id} value={s.grado_seccion_id}>Sección {s.seccion}</option>)}
              </Selector>
            </div>
          </Seccion>
          <Boton type="submit" className="w-full" cargando={registrar.isPending}>
            Registrar matrícula
          </Boton>
        </div>
      </div>
    </form>
  );
}

function Elegido({ texto, detalle, alQuitar }: { texto: string; detalle?: string; alQuitar: () => void }) {
  return (
    <div className="flex items-center justify-between gap-3 rounded-lg border border-marca-200 bg-marca-50 px-3 py-2.5 text-sm">
      <div>
        <p className="text-slate-900">{texto}</p>
        {detalle && <p className="text-slate-500">{detalle}</p>}
      </div>
      <button type="button" onClick={alQuitar} className="rounded-lg p-1 text-slate-500 hover:bg-white" aria-label="Quitar selección">
        <X className="size-4" />
      </button>
    </div>
  );
}
