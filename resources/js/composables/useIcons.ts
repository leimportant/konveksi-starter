// useIcons.ts
import * as Icons from 'lucide-vue-next';

export function resolveIcon(name: string) {
  return Icons[name as keyof typeof Icons] || null;
}
