export interface ZiggyProps {
  location: string;
  routes: Record<string, any>;
  defaults: Record<string, any>;
}

export interface PageProps {
  ziggy: ZiggyProps;
  [key: string]: unknown;
}
