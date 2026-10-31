import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Plus } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { BarraFiltros } from '@/components/BarraFiltros';
import { Buscador } from '@/components/Buscador';
import { Encabezado } from '@/components/Encabezado';
import { EstadoFilas, Paginador, Tabla, Td, Th } from '@/components/Tabla';
import { AlertaError } from '@/components/ui/alerta';
import { Insignia } from '@/components/ui/insignia';
import { BotonEnlace } from '@/components/ui/boton';
import { api, type Pagina } from '@/lib/api';
import { useFiltrosUrl } from '@/lib/hooks';
import { fecha, formato } from '@/lib/utils';

interface PagoFila {
  id: number;
  concepto: string;
  fecha_pago: string;
  abono_hoy: string;
  entregado_por: string | null;
  estado: string;
  observacion: string | null;
  total_cuenta: string;
  saldo_cuenta: string;
  estudiante: string;
  apoderado: string | null;
}

export default function ListaPagos() {
  const { puede } = useSesion();
  const { filtros, pagina, cambiar, hayFiltros, limpiar } = useFiltrosUrl(['q'] as const);
  const consulta = useQuery({
    queryKey: ['pagos', filtros, pagina],
    queryFn: () => api.get<Pagina<PagoFila>>('/pagos', { ...filtros, pagina }),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <Encabezado
        titulo="Pagos"
        descripcion="Abonos recibidos y saldos pendientes." total={consulta.data?.meta.total}
        acciones={
          puede('p_cobrar') && (
            <BotonEnlace to="/pagos/nuevo">
              <Plus aria-hidden /> Registrar pago
            </BotonEnlace>
          )
        }
      />

      <BarraFiltros activos={hayFiltros} alLimpiar={limpiar}>
        <Buscador valor={filtros.q} alCambiar={(q) => cambiar({ q })} placeholder="Buscar alumno, apoderado o concepto" />
      </BarraFiltros>

      {consulta.error && <AlertaError>{consulta.error.message}</AlertaError>}

      <Tabla pie={<Paginador meta={consulta.data?.meta} alCambiar={(p) => cambiar({ pagina: p })} />}>
        <thead>
          <tr>
            <Th>Fecha</Th>
            <Th>Estudiante</Th>
            <Th>Concepto</Th>
            <Th className="text-right">Abono</Th>
            <Th className="text-right">Saldo</Th>
            <Th>Estado</Th>
          </tr>
        </thead>
        <tbody>
          <EstadoFilas cargando={consulta.isPending} vacio={consulta.data?.data.length === 0} columnas={6} mensaje="No hay pagos registrados." />
          {consulta.data?.data.map((p) => (
            <tr key={p.id} className="hover:bg-slate-50">
              <Td className="whitespace-nowrap text-slate-600">{fecha(p.fecha_pago)}</Td>
              <Td>
                <span className="text-slate-900">{p.estudiante}</span>
                {p.entregado_por && <span className="block text-xs text-slate-500">Entregó: {p.entregado_por}</span>}
              </Td>
              <Td className="text-slate-600">{p.concepto}</Td>
              <Td className="text-right tabular-nums text-slate-900">{formato.soles(p.abono_hoy)}</Td>
              <Td className="text-right tabular-nums text-slate-600">{formato.soles(p.saldo_cuenta)}</Td>
              <Td><Insignia tono={p.estado === 'Pagado' ? 'verde' : 'ambar'}>{p.estado}</Insignia></Td>
            </tr>
          ))}
        </tbody>
      </Tabla>
    </>
  );
}
