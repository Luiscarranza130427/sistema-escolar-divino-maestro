import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export function Tarjeta({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('rounded-xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgb(15_23_42/0.04)]', className)} {...props} />;
}
