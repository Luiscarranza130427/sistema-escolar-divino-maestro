import { useEffect, useRef, useState } from 'react';
import { Search, X } from 'lucide-react';

/** Caja de búsqueda que avisa el término tras una pausa al escribir. La tecla "/" la enfoca. */
export function Buscador({ valor, alCambiar, placeholder = 'Buscar…' }: { valor: string; alCambiar: (v: string) => void; placeholder?: string }) {
  const [texto, setTexto] = useState(valor);
  const entrada = useRef<HTMLInputElement>(null);

  useEffect(() => setTexto(valor), [valor]);
  useEffect(() => {
    if (texto === valor) return;
    const t = setTimeout(() => alCambiar(texto.trim()), 350);
    return () => clearTimeout(t);
  }, [texto, valor, alCambiar]);
  useEffect(() => {
    const atajo = (e: KeyboardEvent) => {
      if (e.key !== '/' || (e.target as HTMLElement).closest('input, textarea, select, [contenteditable]')) return;
      e.preventDefault();
      entrada.current?.focus();
    };
    document.addEventListener('keydown', atajo);
    return () => document.removeEventListener('keydown', atajo);
  }, []);

  return (
    <div className="relative w-full sm:w-80">
      <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" aria-hidden />
      <input
        ref={entrada}
        type="text"
        value={texto}
        onChange={(e) => setTexto(e.target.value)}
        onKeyDown={(e) => e.key === 'Escape' && setTexto('')}
        placeholder={placeholder}
        aria-label={placeholder}
        className="peer h-9 w-full rounded-full border border-slate-200 bg-white pr-9 pl-9 text-sm shadow-xs placeholder:text-slate-400 hover:border-slate-300 focus:border-marca-500 focus:ring-3 focus:ring-marca-100 focus:outline-none"
      />
      {texto ? (
        <button
          type="button"
          onClick={() => setTexto('')}
          className="absolute top-1/2 right-2 inline-flex size-6 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700"
          aria-label="Borrar búsqueda"
        >
          <X className="size-3.5" aria-hidden />
        </button>
      ) : (
        <kbd className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 rounded border border-slate-200 px-1.5 font-sans text-[11px] text-slate-400 peer-focus:hidden">/</kbd>
      )}
    </div>
  );
}
