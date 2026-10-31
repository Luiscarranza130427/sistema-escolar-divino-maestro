import { useState, type FormEvent } from 'react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { EstadoFilas, Tabla, Td, Th } from '@/components/Tabla';
import { Campo } from '@/components/ui/campo';
import { Dialogo } from '@/components/ui/dialogo';
import { Boton, BotonIcono } from '@/components/ui/boton';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion } from '@/lib/hooks';
import { errorDe } from '@/lib/utils';
import { useCursos, type Curso } from './tipos';

export default function Cursos() {
  const cursos = useCursos();
  const [editando, setEditando] = useState<Curso | 'nuevo' | null>(null);
  const desactivar = useAccion((c: Curso) => api.delete(`/cursos/${c.id}`), { exito: 'Curso desactivado.', invalidar: [['cursos']] });

  return (
    <>
      <div className="mb-4 flex justify-end">
        <Boton onClick={() => setEditando('nuevo')}><Plus className="size-4" aria-hidden /> Nuevo curso</Boton>
      </div>
      <Tabla>
        <thead>
          <tr>
            <Th>Curso</Th>
            <Th>Grado</Th>
            <Th className="w-px"><span className="sr-only">Acciones</span></Th>
          </tr>
        </thead>
        <tbody>
          <EstadoFilas cargando={cursos.isPending} vacio={cursos.data?.length === 0} columnas={3} mensaje="Aún no hay cursos." />
          {cursos.data?.map((c) => (
            <tr key={c.id}>
              <Td className="text-slate-900">{c.nombre}</Td>
              <Td className="text-slate-600">{c.grado}</Td>
              <Td className="text-right whitespace-nowrap">
                <BotonIcono onClick={() => setEditando(c)} etiqueta={`Editar ${c.nombre}`}><Pencil /></BotonIcono>
                <BotonIcono peligro onClick={async () => (await confirmar({ titulo: 'Desactivar curso', texto: `${c.nombre} dejará de aparecer para registrar notas. Las notas ya registradas se conservan.`, accion: 'Desactivar', peligro: true })) && desactivar.mutate(c)} etiqueta={`Desactivar ${c.nombre}`}><Trash2 /></BotonIcono>
              </Td>
            </tr>
          ))}
        </tbody>
      </Tabla>
      {editando && <FormCurso curso={editando === 'nuevo' ? undefined : editando} alCerrar={() => setEditando(null)} />}
    </>
  );
}

function FormCurso({ curso, alCerrar }: { curso?: Curso; alCerrar: () => void }) {
  const [nombre, setNombre] = useState(curso?.nombre ?? '');
  const [grado, setGrado] = useState(curso?.grado ?? '');
  const guardar = useAccion(() => (curso ? api.put(`/cursos/${curso.id}`, { nombre, grado }) : api.post('/cursos', { nombre, grado })), {
    exito: curso ? 'Curso actualizado.' : 'Curso creado.',
    invalidar: [['cursos']],
    alTerminar: alCerrar,
  });
  const enviar = (e: FormEvent) => {
    e.preventDefault();
    guardar.mutate(undefined);
  };
  return (
    <Dialogo
      abierto
      alCerrar={alCerrar}
      titulo={curso ? 'Editar curso' : 'Nuevo curso'}
      ancho="sm"
      pie={
        <>
          <Boton variante="secundario" onClick={alCerrar}>Cancelar</Boton>
          <Boton type="submit" form="form-curso" cargando={guardar.isPending}>Guardar</Boton>
        </>
      }
    >
      <form id="form-curso" onSubmit={enviar} className="space-y-4">
        <Campo etiqueta="Nombre del curso" maxLength={100} placeholder="Matemática" value={nombre} onChange={(e) => setNombre(e.target.value)} error={errorDe(guardar.error, 'nombre')} />
        <Campo etiqueta="Grado" maxLength={50} placeholder="1.° de primaria" value={grado} onChange={(e) => setGrado(e.target.value)} error={errorDe(guardar.error, 'grado')} />
      </form>
    </Dialogo>
  );
}
