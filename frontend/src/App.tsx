import { Navigate, Route, Routes } from 'react-router';
import { RutaPrivada } from '@/auth/RutaPrivada';
import Estructura from '@/layout/Estructura';
import Academico from '@/pages/academico/Academico';
import Asistentes from '@/pages/direccion/Asistentes';
import Auditoria from '@/pages/direccion/Auditoria';
import Perfil from '@/pages/perfil/Perfil';
import FichaEstudiante from '@/pages/estudiantes/Ficha';
import ListaEstudiantes from '@/pages/estudiantes/Lista';
import Login from '@/pages/Login';
import ListaMatriculas from '@/pages/matriculas/Lista';
import NuevaMatricula from '@/pages/matriculas/Nueva';
import Conceptos from '@/pages/pagos/Conceptos';
import ListaPagos from '@/pages/pagos/Lista';
import NuevoPago from '@/pages/pagos/Nuevo';
import Panel from '@/pages/Panel';
import FichaResponsable from '@/pages/responsables/Ficha';
import ListaResponsables from '@/pages/responsables/Lista';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        element={
          <RutaPrivada>
            <Estructura />
          </RutaPrivada>
        }
      >
        <Route index element={<Panel />} />
        <Route path="estudiantes" element={<ListaEstudiantes />} />
        <Route path="estudiantes/:id" element={<FichaEstudiante />} />
        <Route path="matriculas" element={<ListaMatriculas />} />
        <Route path="matriculas/nueva" element={<RutaPrivada permiso="p_matricular"><NuevaMatricula /></RutaPrivada>} />
        <Route path="responsables" element={<ListaResponsables />} />
        <Route path="responsables/:id" element={<FichaResponsable />} />
        <Route path="pagos" element={<ListaPagos />} />
        <Route path="pagos/nuevo" element={<RutaPrivada permiso="p_cobrar"><NuevoPago /></RutaPrivada>} />
        <Route path="conceptos" element={<RutaPrivada permiso="p_cobrar"><Conceptos /></RutaPrivada>} />
        <Route path="academico" element={<RutaPrivada permiso="p_matricular"><Academico /></RutaPrivada>} />
        <Route path="asistentes" element={<RutaPrivada soloDirector><Asistentes /></RutaPrivada>} />
        <Route path="auditoria" element={<RutaPrivada soloDirector><Auditoria /></RutaPrivada>} />
        <Route path="perfil" element={<Perfil />} />
        <Route path="cuenta" element={<Navigate to="/perfil" replace />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
